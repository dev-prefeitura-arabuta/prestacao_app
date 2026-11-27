import { useEffect, useState } from 'react';
import { Alert } from 'react-native';
import { PendingWaReminder, flushWaRetryQueue, getWaRetryQueue, saveWaRetryQueue } from '../api';

export interface WaQueueState {
  queue: PendingWaReminder[];
  setQueue: (q: PendingWaReminder[]) => void;
  flushing: boolean;
  flush: (silent?: boolean) => Promise<void>;
  clear: () => Promise<void>;
}

const AUTO_RETRY_MS = 45000;

/**
 * Fila local (AsyncStorage) de cobranças de WhatsApp que não foram entregues.
 * Enquanto houver itens e o usuário for admin, tenta reenviar automaticamente a cada 45s.
 */
export function useWaRetryQueue(token: string | null, isAdmin: boolean): WaQueueState {
  const [queue, setQueue] = useState<PendingWaReminder[]>([]);
  const [flushing, setFlushing] = useState(false);

  useEffect(() => {
    getWaRetryQueue().then(setQueue).catch(() => {});
  }, []);

  useEffect(() => {
    if (!token || !isAdmin || queue.length === 0) return;
    const timer = setInterval(() => {
      flushWaRetryQueue(token)
        .then((res) => setQueue(res.queue))
        .catch(() => {});
    }, AUTO_RETRY_MS);
    return () => clearInterval(timer);
  }, [token, isAdmin, queue.length]);

  async function flush(silent = false) {
    if (!token || flushing) return;
    setFlushing(true);
    try {
      const res = await flushWaRetryQueue(token);
      setQueue(res.queue);
      if (silent) return;
      if (res.sent > 0 && res.remaining === 0) {
        Alert.alert('Pacote Enviado!', `${res.sent} cobrança(s) enviada(s) com status 200 e cache limpo.`);
      } else if (res.sent > 0) {
        Alert.alert(
          'Envio Parcial',
          `${res.sent} cobrança(s) enviada(s) e ${res.remaining} ainda aguardando o WhatsApp voltar.`
        );
      } else {
        Alert.alert('WhatsApp ainda indisponível', `${res.remaining} cobrança(s) mantida(s) em cache para nova tentativa.`);
      }
    } finally {
      setFlushing(false);
    }
  }

  async function clear() {
    await saveWaRetryQueue([]);
    setQueue([]);
  }

  return { queue, setQueue, flushing, flush, clear };
}
