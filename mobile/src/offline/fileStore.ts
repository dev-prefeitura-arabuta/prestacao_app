import { Platform } from 'react-native';
import { Directory, File, Paths } from 'expo-file-system';

/**
 * Armazenamento offline em arquivos comuns dentro da pasta privada do app (Paths.document).
 * Não usa banco de dados: cada registro é um arquivo .json. Tudo é apagado ao desinstalar
 * o app ou limpar seus dados, e outros apps não têm acesso.
 */

/** No navegador (Expo web) não há pasta privada: os recursos offline ficam desligados. */
export const OFFLINE_SUPPORTED = Platform.OS !== 'web';

export function ensureDir(name: string): Directory {
  const dir = new Directory(Paths.document, name);
  if (!dir.exists) {
    dir.create({ intermediates: true, idempotent: true });
  }
  return dir;
}

export async function readJsonFile<T>(file: File): Promise<T | null> {
  try {
    if (!file.exists) return null;
    return JSON.parse(await file.text()) as T;
  } catch {
    // Arquivo corrompido (ex.: app fechado no meio da gravação): ignora
    return null;
  }
}

export async function writeJsonFile(file: File, data: unknown): Promise<void> {
  await file.write(JSON.stringify(data));
}

export function deleteQuietly(file: File): void {
  try {
    if (file.exists) file.delete();
  } catch {
    // já removido
  }
}

/** Nome seguro para arquivo a partir de uma chave qualquer. */
export function safeFileName(key: string): string {
  return key.replace(/[^A-Za-z0-9_-]/g, '_');
}
