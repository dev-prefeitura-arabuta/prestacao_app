import { File } from 'expo-file-system';
import { ApiError, apiUploadAttachment, isNetworkError } from '../api';
import { OFFLINE_SUPPORTED, deleteQuietly, ensureDir, readJsonFile, writeJsonFile } from './fileStore';

/**
 * Fila de anexos aguardando conexão.
 *
 * Cada envio pendente ocupa dois arquivos na pasta privada do app:
 *   upload-queue/<id>.json  → dados do envio (prestação, item, nome, tentativas…)
 *   upload-queue/<id>.<ext> → cópia da foto/PDF (a pasta temporária da câmera pode ser limpa pelo sistema)
 *
 * O <id> também vai para o servidor (client_upload_id): se a conexão cair depois que o arquivo
 * chegou, o reenvio devolve o anexo já gravado em vez de duplicá-lo.
 */

const QUEUE_DIR = 'upload-queue';

export type PendingStatus = 'pending' | 'failed';

export interface PendingUpload {
  id: string;
  userId: number;
  accountabilityId: number;
  accountabilityTitle: string;
  itemNumber: number;
  name: string;
  mimeType: string;
  /** Nome da cópia local do arquivo dentro de upload-queue/ */
  dataFile: string;
  createdAt: string;
  attempts: number;
  /** pending = tenta de novo sozinho; failed = recusado pelo servidor, aguarda o usuário remover */
  status: PendingStatus;
  lastError?: string;
}

export interface FlushResult {
  sent: number;
  failed: number;
  remaining: number;
  /** Token expirado: a fila continua salva e será enviada após novo login. */
  authExpired: boolean;
}

// ---------- notificação das telas ----------

type Listener = () => void;
const listeners = new Set<Listener>();

export function subscribeUploadQueue(listener: Listener): () => void {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

function notify(): void {
  listeners.forEach((l) => l());
}

// ---------- arquivos ----------

function queueDir() {
  return ensureDir(QUEUE_DIR);
}

function metaFile(id: string): File {
  return new File(queueDir(), `${id}.json`);
}

/** Identificador de um envio (usado na tentativa direta e, se ela falhar, na fila). */
export function newUploadId(): string {
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}

function extensionOf(name: string, mimeType: string): string {
  const fromName = name.includes('.') ? name.split('.').pop()!.toLowerCase() : '';
  if (/^[a-z0-9]{1,5}$/.test(fromName)) return fromName;
  if (mimeType === 'application/pdf') return 'pdf';
  if (mimeType === 'image/png') return 'png';
  if (mimeType === 'application/zip') return 'zip';
  return 'jpg';
}

async function save(entry: PendingUpload): Promise<void> {
  await writeJsonFile(metaFile(entry.id), entry);
}

// ---------- API pública ----------

/** Envios pendentes do usuário (mais antigos primeiro). */
export async function listPendingUploads(userId: number): Promise<PendingUpload[]> {
  if (!OFFLINE_SUPPORTED) return [];
  const entries: PendingUpload[] = [];
  for (const item of queueDir().list()) {
    if (!(item instanceof File) || !item.name.endsWith('.json')) continue;
    const entry = await readJsonFile<PendingUpload>(item);
    if (entry && entry.userId === userId) entries.push(entry);
  }
  return entries.sort((a, b) => a.createdAt.localeCompare(b.createdAt));
}

/** Copia o arquivo para a pasta do app e coloca na fila. */
export async function enqueueUpload(params: {
  /** Reaproveita o identificador da tentativa direta que falhou */
  id?: string;
  userId: number;
  accountabilityId: number;
  accountabilityTitle: string;
  itemNumber: number;
  file: { uri: string; name: string; mimeType?: string };
}): Promise<PendingUpload> {
  if (!OFFLINE_SUPPORTED) {
    throw new Error('Envio offline não disponível nesta plataforma.');
  }

  const id = params.id || newUploadId();
  const mimeType = params.file.mimeType || 'image/jpeg';
  const dataFile = `${id}.${extensionOf(params.file.name, mimeType)}`;

  await new File(params.file.uri).copy(new File(queueDir(), dataFile));

  const entry: PendingUpload = {
    id,
    userId: params.userId,
    accountabilityId: params.accountabilityId,
    accountabilityTitle: params.accountabilityTitle,
    itemNumber: params.itemNumber,
    name: params.file.name,
    mimeType,
    dataFile,
    createdAt: new Date().toISOString(),
    attempts: 0,
    status: 'pending',
  };
  await save(entry);
  notify();
  return entry;
}

/** Remove da fila e apaga a cópia local do arquivo. */
export function removePendingUpload(entry: PendingUpload): void {
  deleteQuietly(new File(queueDir(), entry.dataFile));
  deleteQuietly(metaFile(entry.id));
  notify();
}

/** Remove todos os envios do usuário (logout com descarte). */
export async function clearPendingUploads(userId: number): Promise<void> {
  for (const entry of await listPendingUploads(userId)) {
    removePendingUpload(entry);
  }
}

/** Uri local do arquivo (para miniatura na tela do item). */
export function pendingUploadUri(entry: PendingUpload): string {
  return new File(queueDir(), entry.dataFile).uri;
}

let flushing: Promise<FlushResult> | null = null;

/**
 * Tenta enviar os pendentes, um por vez. Para ao primeiro sinal de falta de conexão.
 * Chamadas simultâneas reaproveitam o mesmo envio em andamento.
 */
export function flushUploadQueue(token: string, userId: number): Promise<FlushResult> {
  if (!flushing) {
    flushing = doFlush(token, userId).finally(() => {
      flushing = null;
    });
  }
  return flushing;
}

async function doFlush(token: string, userId: number): Promise<FlushResult> {
  const result: FlushResult = { sent: 0, failed: 0, remaining: 0, authExpired: false };
  const pending = (await listPendingUploads(userId)).filter((e) => e.status === 'pending');

  for (let i = 0; i < pending.length; i++) {
    const entry = pending[i];
    const local = new File(queueDir(), entry.dataFile);

    if (!local.exists) {
      await save({ ...entry, status: 'failed', lastError: 'O arquivo salvo no aparelho não foi encontrado.' });
      result.failed++;
      continue;
    }

    try {
      await apiUploadAttachment(
        token,
        entry.accountabilityId,
        entry.itemNumber,
        { uri: local.uri, name: entry.name, mimeType: entry.mimeType },
        entry.id
      );
      removePendingUpload(entry);
      result.sent++;
    } catch (e) {
      const status = e instanceof ApiError ? e.status : 0;
      const message = e instanceof Error ? e.message : 'Falha ao enviar.';

      if (isNetworkError(e)) {
        // Sem conexão: mantém este e os próximos para a próxima tentativa
        await save({ ...entry, attempts: entry.attempts + 1, lastError: message });
        result.remaining += pending.length - i;
        break;
      }
      if (status === 401) {
        await save({ ...entry, lastError: 'Sessão expirada. Faça login novamente para enviar.' });
        result.authExpired = true;
        result.remaining += pending.length - i;
        break;
      }
      if (status >= 500 || status === 408 || status === 429) {
        // Problema temporário do servidor: tenta de novo depois
        await save({ ...entry, attempts: entry.attempts + 1, lastError: message });
        result.remaining++;
        continue;
      }
      // Recusa definitiva (item confirmado, tipo inválido, prestação excluída…): não insiste
      await save({ ...entry, attempts: entry.attempts + 1, status: 'failed', lastError: message });
      result.failed++;
    }
  }

  notify();
  return result;
}
