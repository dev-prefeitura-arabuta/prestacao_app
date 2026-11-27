import { useCallback, useEffect, useRef, useState } from 'react';
import { AppState } from 'react-native';
import {
  FlushResult,
  PendingUpload,
  enqueueUpload,
  flushUploadQueue,
  listPendingUploads,
  removePendingUpload,
  subscribeUploadQueue,
} from '../offline/uploadQueue';
import { OFFLINE_SUPPORTED } from '../offline/fileStore';

export interface UploadQueueState {
  supported: boolean;
  items: PendingUpload[];
  flushing: boolean;
  /** Envios da prestação (e opcionalmente de um item) ainda na fila. */
  pendingFor: (accountabilityId: number, itemNumber?: number) => PendingUpload[];
  enqueue: typeof enqueueUpload;
  remove: (entry: PendingUpload) => void;
  /** Envio manual ("Enviar agora"), com indicador de carregamento. */
  flush: () => Promise<FlushResult | null>;
}

const AUTO_RETRY_MS = 30000;

/**
 * Fila de anexos offline do usuário logado. Enquanto houver pendentes, tenta enviar
 * a cada 30s e sempre que o app volta ao primeiro plano. `onSent` é chamado quando
 * algum arquivo chega ao servidor (para recarregar a tela aberta).
 */
export function useUploadQueue(
  token: string | null,
  userId: number | null,
  onSent?: (result: FlushResult) => void
): UploadQueueState {
  const [items, setItems] = useState<PendingUpload[]>([]);
  const [flushing, setFlushing] = useState(false);
  const onSentRef = useRef(onSent);

  useEffect(() => {
    onSentRef.current = onSent;
  });

  // Lê a fila do disco agora e a cada alteração
  useEffect(() => {
    let cancelled = false;
    const load = () => {
      const pending = OFFLINE_SUPPORTED && userId !== null ? listPendingUploads(userId) : Promise.resolve([]);
      pending
        .then((list) => {
          if (!cancelled) setItems(list);
        })
        .catch(() => {});
    };
    load();
    const unsubscribe = subscribeUploadQueue(load);
    return () => {
      cancelled = true;
      unsubscribe();
    };
  }, [userId]);

  /** Envia os pendentes sem mexer em estado de tela (usado nas tentativas automáticas). */
  const sendPending = useCallback(async (): Promise<FlushResult | null> => {
    if (!OFFLINE_SUPPORTED || !token || userId === null) return null;
    try {
      const result = await flushUploadQueue(token, userId);
      if (result.sent > 0) onSentRef.current?.(result);
      return result;
    } catch {
      return null;
    }
  }, [token, userId]);

  const flush = useCallback(async (): Promise<FlushResult | null> => {
    setFlushing(true);
    try {
      return await sendPending();
    } finally {
      setFlushing(false);
    }
  }, [sendPending]);

  const hasPending = items.some((e) => e.status === 'pending');

  // Tentativas automáticas enquanto houver pendentes
  useEffect(() => {
    if (!hasPending || !token) return;
    sendPending();
    const timer = setInterval(sendPending, AUTO_RETRY_MS);
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'active') sendPending();
    });
    return () => {
      clearInterval(timer);
      sub.remove();
    };
  }, [hasPending, token, sendPending]);

  const pendingFor = useCallback(
    (accountabilityId: number, itemNumber?: number) =>
      items.filter(
        (e) => e.accountabilityId === accountabilityId && (itemNumber === undefined || e.itemNumber === itemNumber)
      ),
    [items]
  );

  return {
    supported: OFFLINE_SUPPORTED,
    items,
    flushing,
    pendingFor,
    enqueue: enqueueUpload,
    remove: removePendingUpload,
    flush,
  };
}
