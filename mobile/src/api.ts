import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const TOKEN_KEY = '@prestacao_token';
const USER_KEY = '@prestacao_user';
const API_URL_KEY = '@prestacao_api_url';
const WA_QUEUE_KEY = '@prestacao_wa_retry_queue';

export const DEFAULT_API_URL = 'http://192.168.11.86:8000';

/**
 * Erro de chamada à API. `network = true` quando não houve resposta do servidor
 * (sem internet, servidor fora do ar ou tempo esgotado) — nesses casos vale tentar de novo depois.
 */
export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number = 0,
    public readonly network: boolean = false
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

export const OFFLINE_MESSAGE = 'Sem conexão com o servidor. Verifique sua internet.';

/** Verdadeiro quando a falha foi de conexão (não uma recusa do servidor). */
export function isNetworkError(e: unknown): boolean {
  if (e instanceof ApiError) return e.network;
  // fetch do React Native lança TypeError("Network request failed") sem conexão
  return e instanceof TypeError;
}

/** fetch que converte falha de conexão em ApiError(network). */
async function request(url: string, init?: RequestInit): Promise<Response> {
  try {
    return await fetch(url, init);
  } catch {
    throw new ApiError(OFFLINE_MESSAGE, 0, true);
  }
}

async function readJson(res: Response, fallbackMsg: string): Promise<any> {
  try {
    return await res.json();
  } catch {
    throw new ApiError(`${fallbackMsg} (HTTP ${res.status})`, res.status);
  }
}

export interface MobileUser {
  id: number;
  name: string;
  email: string;
  whatsapp: string;
  role: string;
  /** Conta de desenvolvedor: no app só acessa o próprio perfil. */
  is_developer?: boolean;
  avatar_url?: string | null;
}

export interface AccountabilitySummary {
  id: number;
  title: string;
  description: string;
  deadline: string;
  is_paused: boolean;
  attachments_count: number;
  filled_items: number;
  total_items: number;
  progress: number;
}

export interface ChecklistItem {
  item_number: number;
  title: string;
  status: string;
  rejection_reason: string;
  attachments: Array<{
    id: number;
    original_name: string;
    file_ext: string;
    file_size: number;
    created_at: string;
  }>;
  assigned_users?: Array<{
    id: number;
    name: string;
    whatsapp: string;
  }>;
}

export interface AccountabilityDetail {
  id: number;
  title: string;
  description: string;
  deadline: string;
  all_items_filled?: boolean;
  can_submit?: boolean;
  admin_contacts?: Array<{ id: number; name: string; whatsapp: string }>;
  /** Motivos prontos de rejeição (somente para o admin). */
  rejection_presets?: string[];
  items: ChecklistItem[];
}

export interface ReviewQueueRow {
  accountability_id: number;
  accountability_title: string;
  is_paused: boolean;
  deadline: string | null;
  item_number: number;
  item_title: string;
  attachments_count: number;
  uploaders: string[];
  sent_at: string | null;
}

export interface PendingPersonItem {
  accountability_id: number;
  accountability_title: string;
  deadline: string | null;
  overdue: boolean;
  item_number: number;
  item_title: string;
  status: 'pendente' | 'rejeitado' | 'prazo_vencido';
  has_files: boolean;
}

export interface PendingPersonRow {
  user: { id: number; name: string; whatsapp: string; has_whatsapp: boolean };
  overdue_count: number;
  items: PendingPersonItem[];
  reminder_text: string;
}

export async function getApiUrl(): Promise<string> {
  const saved = (await AsyncStorage.getItem(API_URL_KEY))?.trim().replace(/\/+$/, '');
  if (
    !saved ||
    saved === 'http://localhost:8000' ||
    saved === 'http://10.0.2.2:8000' ||
    saved === 'http://192.168.11.4:8000' ||
    saved === 'https://prestacao.sidelabs.com.br'
  ) {
    await AsyncStorage.setItem(API_URL_KEY, DEFAULT_API_URL);
    return DEFAULT_API_URL;
  }
  return saved;
}

export async function setApiUrl(url: string): Promise<void> {
  await AsyncStorage.setItem(API_URL_KEY, url.trim().replace(/\/+$/, ''));
}

export async function getStoredSession(): Promise<{ token: string; user: MobileUser } | null> {
  const [token, rawUser] = await Promise.all([
    AsyncStorage.getItem(TOKEN_KEY),
    AsyncStorage.getItem(USER_KEY),
  ]);
  if (!token || !rawUser) return null;
  try {
    return { token, user: JSON.parse(rawUser) as MobileUser };
  } catch {
    return null;
  }
}

export async function clearSession(): Promise<void> {
  await AsyncStorage.multiRemove([TOKEN_KEY, USER_KEY]);
}

export interface QuickLoginUser {
  id: number;
  name: string;
  email: string;
  role: string;
}

export async function apiGetQuickLoginUsers(baseUrl: string): Promise<QuickLoginUser[]> {
  const cleanUrl = baseUrl.trim().replace(/\/+$/, '');
  const res = await fetch(`${cleanUrl}/api/mobile/quick-users`, {
    headers: { Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    return [];
  }
  return data.data || [];
}

export async function apiLogin(
  baseUrl: string,
  email: string,
  password: string
): Promise<{ token: string; user: MobileUser }> {
  const cleanUrl = baseUrl.trim().replace(/\/+$/, '');
  await setApiUrl(cleanUrl);

  const res = await fetch(`${cleanUrl}/api/mobile/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  });

  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Falha ao fazer login.');
  }

  await AsyncStorage.setItem(TOKEN_KEY, data.token);
  await AsyncStorage.setItem(USER_KEY, JSON.stringify(data.user));
  return { token: data.token, user: data.user };
}

export async function apiForgotPassword(baseUrl: string, email: string): Promise<string> {
  const cleanUrl = baseUrl.trim().replace(/\/+$/, '');
  await setApiUrl(cleanUrl);
  const res = await fetch(`${cleanUrl}/api/mobile/forgot-password`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email }),
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Não foi possível enviar o código.');
  }
  return data.message || 'Código enviado!';
}

export async function apiVerifyResetCode(baseUrl: string, email: string, code: string): Promise<string> {
  const cleanUrl = baseUrl.trim().replace(/\/+$/, '');
  const res = await fetch(`${cleanUrl}/api/mobile/reset-password/verify`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, code }),
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Código inválido.');
  }
  return data.message || 'Código validado!';
}

export async function apiResetPassword(
  baseUrl: string,
  email: string,
  code: string,
  password: string,
  passwordConfirmation: string
): Promise<string> {
  const cleanUrl = baseUrl.trim().replace(/\/+$/, '');
  const res = await fetch(`${cleanUrl}/api/mobile/reset-password`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({
      email,
      code,
      password,
      password_confirmation: passwordConfirmation,
    }),
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Não foi possível redefinir a senha.');
  }
  return data.message || 'Senha redefinida com sucesso!';
}

export async function apiGetAccountabilities(token: string): Promise<AccountabilitySummary[]> {
  const baseUrl = await getApiUrl();
  const res = await request(`${baseUrl}/api/mobile/accountabilities`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await readJson(res, 'Erro ao carregar prestações de contas');
  if (!res.ok || !data.success) {
    throw new ApiError(data.error || 'Erro ao carregar prestações de contas.', res.status);
  }
  return data.data || [];
}

export async function apiCreateAccountability(
  token: string,
  payload: { title: string; description?: string; deadline?: string; enabled_items?: number[] }
): Promise<{ id: number; message: string }> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/accountabilities`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao criar prestação de contas.');
  }
  return {
    id: data.data?.id,
    message: data.message || 'Prestação de contas criada com sucesso!',
  };
}

export async function apiGetAccountabilityDetail(
  token: string,
  id: number
): Promise<AccountabilityDetail> {
  const baseUrl = await getApiUrl();
  const res = await request(`${baseUrl}/api/mobile/accountabilities/${id}`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await readJson(res, 'Erro ao carregar detalhes da prestação');
  if (!res.ok || !data.success) {
    throw new ApiError(data.error || 'Erro ao carregar detalhes da prestação.', res.status);
  }
  return data.data;
}

async function parseJsonSafe(res: Response, fallbackMsg: string): Promise<any> {
  const raw = await res.text();
  try {
    return JSON.parse(raw);
  } catch {
    if (res.status === 413) {
      throw new Error('A imagem é muito grande para o servidor (HTTP 413). Tente uma foto menor.');
    }
    throw new Error(`${fallbackMsg} (HTTP ${res.status})`);
  }
}

async function uploadMultipart(
  url: string,
  token: string,
  formData: FormData,
  fallbackMsg: string
): Promise<any> {
  if (Platform.OS === 'web') {
    const res = await request(url, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      body: formData,
    });
    const data = await parseJsonSafe(res, fallbackMsg);
    if (!res.ok || !data?.success) {
      throw new ApiError(data?.error || `${fallbackMsg} (HTTP ${res.status})`, res.status);
    }
    return data;
  }

  // No Android/iOS (Expo SDK 58), expo/winter/fetch rejeita { uri, name, type } no FormData
  // ("Unsupported FormDataPart implementation"). XMLHttpRequest usa o NetworkingModule nativo do RN.
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url);
    xhr.setRequestHeader('Authorization', `Bearer ${token}`);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.timeout = 60000;

    xhr.onload = () => {
      const raw = xhr.responseText || '';
      let data: any = null;
      try {
        data = JSON.parse(raw);
      } catch {
        if (xhr.status === 413) {
          reject(new ApiError('A imagem é muito grande para o servidor (HTTP 413). Tente uma foto menor.', 413));
          return;
        }
        // status 0 = a resposta não chegou (conexão caiu durante o envio)
        reject(new ApiError(`${fallbackMsg} (HTTP ${xhr.status})`, xhr.status, xhr.status === 0));
        return;
      }
      if (xhr.status < 200 || xhr.status >= 300 || !data?.success) {
        reject(new ApiError(data?.error || `${fallbackMsg} (HTTP ${xhr.status})`, xhr.status));
        return;
      }
      resolve(data);
    };

    xhr.onerror = () => {
      reject(new ApiError('Falha de conexão ao enviar o arquivo. Verifique sua internet.', 0, true));
    };

    xhr.ontimeout = () => {
      reject(new ApiError('Tempo limite excedido ao enviar o arquivo.', 0, true));
    };

    xhr.send(formData);
  });
}

export async function apiUploadAttachment(
  token: string,
  accountabilityId: number,
  itemNumber: number,
  file: { uri: string; name: string; mimeType?: string },
  /** Identificador da fila offline: o servidor ignora reenvios com o mesmo valor. */
  clientUploadId?: string
): Promise<{ duplicate: boolean }> {
  const baseUrl = await getApiUrl();
  const formData = new FormData();
  const safeMime = file.mimeType || 'image/jpeg';
  const safeName = file.name && file.name.includes('.') ? file.name : `anexo_${Date.now()}.jpg`;

  if (clientUploadId) {
    formData.append('client_upload_id', clientUploadId);
  }

  if (Platform.OS === 'web') {
    const blobRes = await fetch(file.uri);
    const blob = await blobRes.blob();
    formData.append('file', blob, safeName);
  } else {
    formData.append('file', {
      uri: file.uri,
      name: safeName,
      type: safeMime,
    } as unknown as Blob);
  }

  const data = await uploadMultipart(
    `${baseUrl}/api/mobile/accountabilities/${accountabilityId}/upload/${itemNumber}`,
    token,
    formData,
    'Erro ao enviar arquivo'
  );
  return { duplicate: !!data?.duplicate };
}

export async function apiSubmitAccountability(
  token: string,
  accountabilityId: number,
  notifyAdmin = true
): Promise<{
  message: string;
  notified_via_api: boolean;
  admin_wa_links: Array<{ id: number; name: string; phone: string; url: string }>;
}> {
  const baseUrl = await getApiUrl();
  const res = await request(`${baseUrl}/api/mobile/accountabilities/${accountabilityId}/submit`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ notify_admin: notifyAdmin }),
  });
  const data = await readJson(res, 'Não foi possível confirmar o envio');
  if (!res.ok || !data.success) {
    throw new ApiError(data.error || 'Não foi possível confirmar o envio.', res.status);
  }
  return {
    message: data.message || 'Envio confirmado com sucesso!',
    notified_via_api: !!data.notified_via_api,
    admin_wa_links: data.admin_wa_links || [],
  };
}

export async function apiDeleteAttachment(
  token: string,
  attachmentId: number
): Promise<string> {
  const baseUrl = await getApiUrl();
  const res = await request(`${baseUrl}/api/mobile/attachments/${attachmentId}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    },
  });
  const data = await readJson(res, 'Erro ao remover anexo');
  if (!res.ok || !data.success) {
    throw new ApiError(data.error || 'Erro ao remover anexo.', res.status);
  }
  return data.message || 'Anexo removido com sucesso!';
}

export interface PendingWaReminder {
  id: string;
  accountability_id: number;
  accountability_title: string;
  user_id: number;
  user_name: string;
  item_number?: number;
  custom_message?: string;
  created_at: string;
  attempts: number;
  last_error?: string;
}

export async function getWaRetryQueue(): Promise<PendingWaReminder[]> {
  try {
    const raw = await AsyncStorage.getItem(WA_QUEUE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

export async function saveWaRetryQueue(list: PendingWaReminder[]): Promise<void> {
  if (!list.length) {
    await AsyncStorage.removeItem(WA_QUEUE_KEY);
    return;
  }
  await AsyncStorage.setItem(WA_QUEUE_KEY, JSON.stringify(list));
}

export async function enqueueWaReminder(
  item: Omit<PendingWaReminder, 'id' | 'created_at' | 'attempts'>
): Promise<PendingWaReminder[]> {
  const current = await getWaRetryQueue();
  const next: PendingWaReminder[] = [
    ...current,
    {
      ...item,
      id: `${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
      created_at: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
      attempts: 1,
    },
  ];
  await saveWaRetryQueue(next);
  return next;
}

export async function apiTrySendWaReminder(
  token: string,
  accountabilityId: number,
  payload: { user_id: number; item_number?: number; custom_message?: string }
): Promise<{ delivered: boolean; status: number; message: string; wa_url: string }> {
  const baseUrl = await getApiUrl();
  try {
    const res = await fetch(
      `${baseUrl}/api/mobile/accountabilities/${accountabilityId}/remind-whatsapp`,
      {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify(payload),
      }
    );
    const data = await parseJsonSafe(res, 'Falha na API WhatsApp');
    const delivered = res.status === 200 && !!data?.success && !!data?.sent_via_api;
    return {
      delivered,
      status: res.status,
      message: data?.message || data?.error || `HTTP ${res.status}`,
      wa_url: data?.wa_url || '',
    };
  } catch (e: unknown) {
    return {
      delivered: false,
      status: 0,
      message: e instanceof Error ? e.message : 'Sem conexão com o servidor.',
      wa_url: '',
    };
  }
}

export async function flushWaRetryQueue(
  token: string
): Promise<{ sent: number; remaining: number; queue: PendingWaReminder[] }> {
  const current = await getWaRetryQueue();
  if (!current.length) {
    return { sent: 0, remaining: 0, queue: [] };
  }

  let sent = 0;
  const stillPending: PendingWaReminder[] = [];

  for (const item of current) {
    const res = await apiTrySendWaReminder(token, item.accountability_id, {
      user_id: item.user_id,
      item_number: item.item_number,
      custom_message: item.custom_message,
    });
    if (res.delivered) {
      sent++;
    } else {
      stillPending.push({
        ...item,
        attempts: item.attempts + 1,
        last_error: res.message,
      });
    }
  }

  await saveWaRetryQueue(stillPending);
  return { sent, remaining: stillPending.length, queue: stillPending };
}

export async function apiRemindWhatsapp(
  token: string,
  accountabilityId: number,
  payload: { user_id: number; item_number?: number; custom_message?: string }
): Promise<{ sent_via_api: boolean; message: string; wa_url: string }> {
  const baseUrl = await getApiUrl();
  const res = await fetch(
    `${baseUrl}/api/mobile/accountabilities/${accountabilityId}/remind-whatsapp`,
    {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(payload),
    }
  );
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao enviar cobrança por WhatsApp.');
  }
  return {
    sent_via_api: !!data.sent_via_api,
    message: data.message || 'Cobrança processada!',
    wa_url: data.wa_url || '',
  };
}

export async function apiGetAnnualHistory(
  token: string
): Promise<Record<string, Array<{ id: number; year: number; title: string; deadline: string }>>> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/annual-history`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao carregar histórico anual.');
  }
  return data.years || {};
}

export interface SpreadsheetSummaryRow {
  id: number;
  title: string;
  attachments_count: number;
  entries_count: number;
  receitas: number;
  aportes: number;
  despesas: number;
  saldo: number;
}

export interface AuditLogRow {
  id: number;
  accountability_id: number;
  user_name: string;
  action: string;
  created_at: string;
}

export interface SettingsSummaryData {
  smtp_active: boolean;
  smtp_host: string;
  whatsapp_active: boolean;
  whatsapp_url: string;
  ai_provider: string;
  ai_model: string;
  ai_enabled: boolean;
}

export async function apiGetSpreadsheetSummary(token: string): Promise<SpreadsheetSummaryRow[]> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/spreadsheet`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao carregar planilha.');
  }
  return data.data || [];
}

export async function apiGetAuditHistory(token: string): Promise<AuditLogRow[]> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/audit-history`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao carregar auditoria.');
  }
  return data.data || [];
}

export async function apiGetUsers(token: string): Promise<MobileUser[]> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/users`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao carregar usuários.');
  }
  return data.data || [];
}

export interface EditableUserDetail {
  id: number;
  name: string;
  email: string;
  whatsapp: string;
  role: 'admin' | 'user';
  /** Administrador principal: sempre administrador (o servidor recusa a troca de perfil). */
  is_primary_admin?: boolean;
  allowed_items: number[];
  all_items: Array<{ item_number: number; title: string }>;
}

export async function apiGetUserDetail(token: string, id: number): Promise<EditableUserDetail> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/users/${id}`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao carregar dados do usuário.');
  }
  return data.data;
}

export async function apiUpdateUser(
  token: string,
  id: number,
  payload: {
    name: string;
    email: string;
    whatsapp: string;
    role: 'admin' | 'user';
    password?: string;
    allowed_items: number[];
  }
): Promise<string> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/users/${id}`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao atualizar usuário.');
  }
  return data.message || 'Usuário e permissões atualizados com sucesso!';
}

export async function apiGetSettingsSummary(token: string): Promise<SettingsSummaryData> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/settings-summary`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao carregar configurações.');
  }
  return data.data;
}

export async function saveStoredUser(user: MobileUser): Promise<void> {
  await AsyncStorage.setItem(USER_KEY, JSON.stringify(user));
}

export async function apiGetMe(token: string): Promise<MobileUser> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/me`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao carregar perfil.');
  }
  await saveStoredUser(data.user);
  return data.user;
}

export async function apiUpdateProfile(
  token: string,
  payload: {
    name: string;
    email: string;
    whatsapp: string;
    new_password?: string;
    confirm_password?: string;
    avatar?: { uri: string; name: string; mimeType?: string } | null;
  }
): Promise<{ message: string; user: MobileUser }> {
  const baseUrl = await getApiUrl();
  const formData = new FormData();
  formData.append('name', payload.name);
  formData.append('email', payload.email);
  formData.append('whatsapp', payload.whatsapp);
  if (payload.new_password) {
    formData.append('new_password', payload.new_password);
    formData.append('confirm_password', payload.confirm_password || '');
  }
  if (payload.avatar) {
    if (Platform.OS === 'web') {
      const blobRes = await fetch(payload.avatar.uri);
      const blob = await blobRes.blob();
      formData.append('avatar', blob, payload.avatar.name || 'avatar.jpg');
    } else {
      formData.append('avatar', {
        uri: payload.avatar.uri,
        name: payload.avatar.name || 'avatar.jpg',
        type: payload.avatar.mimeType || 'image/jpeg',
      } as unknown as Blob);
    }
  }

  const data = await uploadMultipart(
    `${baseUrl}/api/mobile/profile`,
    token,
    formData,
    'Erro ao atualizar perfil'
  );
  await saveStoredUser(data.user);
  return { message: data.message || 'Perfil atualizado com sucesso!', user: data.user };
}

export async function apiRemoveAvatar(token: string): Promise<{ message: string; user: MobileUser }> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/profile/avatar`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao remover foto.');
  }
  await saveStoredUser(data.user);
  return { message: data.message || 'Foto removida com sucesso!', user: data.user };
}

export interface AdminNotificationItem {
  id: number;
  accountability_id: number | null;
  actor_name: string;
  title: string;
  message: string;
  is_read: boolean;
  created_at: string;
}

export async function apiGetNotifications(token: string): Promise<{
  enabled: boolean;
  unread_count: number;
  items: AdminNotificationItem[];
}> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/notifications`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    return { enabled: true, unread_count: 0, items: [] };
  }
  return {
    enabled: !!data.enabled,
    unread_count: Number(data.unread_count || 0),
    items: data.items || [],
  };
}

export async function apiToggleNotifications(token: string, enabled: boolean): Promise<boolean> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/notifications/toggle`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ enabled }),
  });
  const data = await res.json();
  return data?.success ? !!data.enabled : enabled;
}

export async function apiMarkNotificationRead(token: string, id: number): Promise<void> {
  const baseUrl = await getApiUrl();
  await fetch(`${baseUrl}/api/mobile/notifications/${id}/read`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  }).catch(() => {});
}

export async function apiMarkAllNotificationsRead(token: string): Promise<void> {
  const baseUrl = await getApiUrl();
  await fetch(`${baseUrl}/api/mobile/notifications/read-all`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  }).catch(() => {});
}

// ---------- Avaliação pelo administrador ----------

async function authedJson(token: string, path: string, init: RequestInit, fallbackMsg: string): Promise<any> {
  const baseUrl = await getApiUrl();
  const res = await request(`${baseUrl}${path}`, {
    ...init,
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      ...(init.body ? { 'Content-Type': 'application/json' } : {}),
    },
  });
  const data = await readJson(res, fallbackMsg);
  if (!res.ok || !data.success) {
    throw new ApiError(data.error || fallbackMsg, res.status);
  }
  return data;
}

/** Confirma ou rejeita (com motivo) um item. Na rejeição, o servidor avisa quem enviou. */
export async function apiSetItemStatus(
  token: string,
  accountabilityId: number,
  itemNumber: number,
  status: 'confirmado' | 'rejeitado',
  rejectionReason = ''
): Promise<{ message: string; status: string; notified: number }> {
  const data = await authedJson(
    token,
    `/api/mobile/accountabilities/${accountabilityId}/items/${itemNumber}/status`,
    { method: 'POST', body: JSON.stringify({ status, rejection_reason: rejectionReason }) },
    'Não foi possível atualizar a situação do item.'
  );
  return { message: data.message, status: data.status, notified: Number(data.notified || 0) };
}

/** Confirma de uma vez todos os itens enviados da prestação. */
export async function apiConfirmAllSent(token: string, accountabilityId: number): Promise<{ confirmed: number; message: string }> {
  const data = await authedJson(
    token,
    `/api/mobile/accountabilities/${accountabilityId}/confirm-sent`,
    { method: 'POST', body: JSON.stringify({}) },
    'Não foi possível confirmar os itens.'
  );
  return { confirmed: Number(data.confirmed || 0), message: data.message };
}

export async function apiGetReviewQueue(token: string): Promise<ReviewQueueRow[]> {
  const data = await authedJson(token, '/api/mobile/review-queue', { method: 'GET' }, 'Erro ao carregar itens para avaliar.');
  return data.data || [];
}

export async function apiGetPendingByUser(token: string): Promise<PendingPersonRow[]> {
  const data = await authedJson(token, '/api/mobile/pending-by-user', { method: 'GET' }, 'Erro ao carregar pendências.');
  return data.data || [];
}
