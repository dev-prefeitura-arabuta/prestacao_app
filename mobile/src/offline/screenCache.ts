import { File } from 'expo-file-system';
import { isNetworkError } from '../api';
import { OFFLINE_SUPPORTED, ensureDir, readJsonFile, safeFileName, writeJsonFile } from './fileStore';

/**
 * Última versão de cada tela (lista e detalhes das prestações), gravada em arquivos .json,
 * para o usuário conseguir navegar e anexar documentos sem internet.
 */

const CACHE_DIR = 'offline-cache';

interface CacheEntry<T> {
  savedAt: string;
  data: T;
}

export interface Cached<T> {
  data: T;
  /** Data/hora (ISO) em que os dados foram salvos; null quando vieram do servidor agora. */
  offlineSince: string | null;
}

function cacheFile(userId: number, key: string): File {
  return new File(ensureDir(CACHE_DIR), `u${userId}_${safeFileName(key)}.json`);
}

/**
 * Busca no servidor e guarda a resposta. Sem conexão, devolve a última cópia salva
 * (com `offlineSince` preenchido). Outros erros, ou sem cópia salva, são repassados.
 */
export async function loadWithCache<T>(userId: number, key: string, loader: () => Promise<T>): Promise<Cached<T>> {
  try {
    const data = await loader();
    if (OFFLINE_SUPPORTED) {
      writeJsonFile(cacheFile(userId, key), { savedAt: new Date().toISOString(), data } as CacheEntry<T>).catch(() => {});
    }
    return { data, offlineSince: null };
  } catch (e) {
    if (OFFLINE_SUPPORTED && isNetworkError(e)) {
      const cached = await readJsonFile<CacheEntry<T>>(cacheFile(userId, key));
      if (cached) {
        return { data: cached.data, offlineSince: cached.savedAt };
      }
    }
    throw e;
  }
}

/** Apaga todas as telas salvas (usado no logout). */
export function clearScreenCache(): void {
  if (!OFFLINE_SUPPORTED) return;
  try {
    for (const entry of ensureDir(CACHE_DIR).list()) {
      entry.delete();
    }
  } catch {
    // nada salvo
  }
}

export function formatOfflineSince(iso: string): string {
  const d = new Date(iso);
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)} às ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
