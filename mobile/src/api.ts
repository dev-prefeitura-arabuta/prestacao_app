import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const TOKEN_KEY = '@prestacao_token';
const USER_KEY = '@prestacao_user';
const API_URL_KEY = '@prestacao_api_url';

export const DEFAULT_API_URL = 'http://192.168.11.4:8000';

export interface MobileUser {
  id: number;
  name: string;
  email: string;
  whatsapp: string;
  role: string;
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
}

export interface AccountabilityDetail {
  id: number;
  title: string;
  description: string;
  deadline: string;
  all_items_filled?: boolean;
  can_submit?: boolean;
  items: ChecklistItem[];
}

export async function getApiUrl(): Promise<string> {
  const saved = (await AsyncStorage.getItem(API_URL_KEY))?.trim().replace(/\/+$/, '');
  if (!saved || saved === 'http://localhost:8000' || saved === 'http://10.0.2.2:8000') {
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
  const res = await fetch(`${baseUrl}/api/mobile/accountabilities`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao carregar prestações de contas.');
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
  const res = await fetch(`${baseUrl}/api/mobile/accountabilities/${id}`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao carregar detalhes da prestação.');
  }
  return data.data;
}

export async function apiUploadAttachment(
  token: string,
  accountabilityId: number,
  itemNumber: number,
  file: { uri: string; name: string; mimeType?: string }
): Promise<void> {
  const baseUrl = await getApiUrl();
  const formData = new FormData();
  if (Platform.OS === 'web') {
    const blobRes = await fetch(file.uri);
    const blob = await blobRes.blob();
    formData.append('file', blob, file.name);
  } else {
    formData.append('file', {
      uri: file.uri,
      name: file.name,
      type: file.mimeType || 'application/octet-stream',
    } as unknown as Blob);
  }

  const res = await fetch(
    `${baseUrl}/api/mobile/accountabilities/${accountabilityId}/upload/${itemNumber}`,
    {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      body: formData,
    }
  );
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao enviar arquivo.');
  }
}

export async function apiSubmitAccountability(
  token: string,
  accountabilityId: number
): Promise<string> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/accountabilities/${accountabilityId}/submit`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Não foi possível confirmar o envio.');
  }
  return data.message || 'Envio confirmado com sucesso!';
}

export async function apiDeleteAttachment(
  token: string,
  attachmentId: number
): Promise<string> {
  const baseUrl = await getApiUrl();
  const res = await fetch(`${baseUrl}/api/mobile/attachments/${attachmentId}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    },
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao remover anexo.');
  }
  return data.message || 'Anexo removido com sucesso!';
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

  const res = await fetch(`${baseUrl}/api/mobile/profile`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    },
    body: formData,
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Erro ao atualizar perfil.');
  }
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
