import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Image,
  Linking,
  Modal,
  Platform,
  RefreshControl,
  SafeAreaView,
  ScrollView,
  StatusBar as RNStatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { StatusBar } from 'expo-status-bar';
import * as DocumentPicker from 'expo-document-picker';
import * as ImagePicker from 'expo-image-picker';
import { FontAwesome5 } from '@expo/vector-icons';
import {
  AccountabilityDetail,
  AccountabilitySummary,
  AuditLogRow,
  DEFAULT_API_URL,
  EditableUserDetail,
  MobileUser,
  QuickLoginUser,
  SettingsSummaryData,
  SpreadsheetSummaryRow,
  apiForgotPassword,
  apiCreateAccountability,
  apiDeleteAttachment,
  apiGetAccountabilities,
  apiGetAccountabilityDetail,
  apiGetAnnualHistory,
  apiGetAuditHistory,
  apiGetMe,
  apiGetQuickLoginUsers,
  apiGetSettingsSummary,
  apiGetSpreadsheetSummary,
  apiGetUserDetail,
  apiGetUsers,
  apiLogin,
  apiRemindWhatsapp,
  apiRemoveAvatar,
  apiResetPassword,
  apiSubmitAccountability,
  apiUpdateProfile,
  apiUpdateUser,
  apiUploadAttachment,
  apiVerifyResetCode,
  clearSession,
  getApiUrl,
  getStoredSession,
} from './src/api';

type GuestScreen = 'home' | 'login' | 'forgot';
type AppScreen =
  | 'menu'
  | 'accountabilities'
  | 'spreadsheet'
  | 'annual_history'
  | 'audit'
  | 'users'
  | 'settings'
  | 'profile';

const COLORS = {
  bg: '#f8fafc',
  bgSoft: '#f1f5f9',
  card: '#ffffff',
  border: '#e2e8f0',
  navy: '#0f172a',
  shieldDark: '#072c5e',
  shieldBlue: '#0b4ea2',
  orange: '#dd4814',
  orangeBright: '#f97316',
  orangeSoft: '#fff7ed',
  orangeBorder: '#fed7aa',
  orangeText: '#c2410c',
  text: '#1e293b',
  muted: '#64748b',
  success: '#16a34a',
};

const STATUS_STYLE: Record<string, { label: string; bg: string; fg: string }> = {
  pendente: { label: 'Pendente', bg: '#fef3c7', fg: '#92400e' },
  enviado: { label: 'Enviado', bg: '#dbeafe', fg: '#1d4ed8' },
  confirmado: { label: 'Confirmado', bg: '#dcfce7', fg: '#15803d' },
  rejeitado: { label: 'Rejeitado', bg: '#fee2e2', fg: '#b91c1c' },
  prazo_vencido: { label: 'Prazo vencido', bg: '#fee2e2', fg: '#991b1b' },
};

const ALL_CHECKLIST_ITEMS: Array<{ item_number: number; title: string }> = [
  { item_number: 1, title: 'Balancete de prestação de contas - receita/despesa/saldos' },
  { item_number: 2, title: 'Cópias das notas de empenho' },
  { item_number: 3, title: 'Cópias de notas fiscais' },
  { item_number: 4, title: 'Comprovante de pagamento de encargos tributários incidentes sobre cada etapa executada' },
  { item_number: 5, title: 'Ordem de serviço' },
  { item_number: 6, title: 'Medição do período completo' },
  { item_number: 7, title: 'Laudo técnico de engenharia - relatório simplificado com descrição das etapas executadas' },
  { item_number: 8, title: 'Acervo fotográfico atual do objeto' },
  { item_number: 9, title: 'Relação de bens adquiridos/produzidos/construídos' },
  { item_number: 10, title: 'Anotação de Responsabilidade Técnica (ART) de execução e fiscalização' },
  { item_number: 11, title: 'Aporte da contrapartida - comprovado no extrato bancário da conta específica' },
  { item_number: 12, title: 'Execução da contrapartida - quando em bens e serviços' },
  { item_number: 13, title: 'Extrato bancário com movimentação completa do período' },
  { item_number: 14, title: 'Extrato bancário completo da aplicação financeira' },
  { item_number: 15, title: 'Conciliação bancária (se houver)' },
  { item_number: 16, title: 'Identificação oficial da marca do Governo do Estado - placa de obras' },
  { item_number: 17, title: 'Cadastro Nacional de Obras (CNO) - até 30 (trinta) dias do início da obra' },
  { item_number: 18, title: 'Parecer do órgão de controle interno municipal' },
  { item_number: 19, title: 'Certidão Negativa de Débitos (CND) da obra - final de obras' },
  { item_number: 20, title: 'Comprovante de devolução de saldos' },
  { item_number: 21, title: 'Comprovante de recolhimento de rendimento de recursos' },
  { item_number: 22, title: 'Relatório de execução físico-financeira' },
  { item_number: 23, title: 'Termo de aceitação definitiva do objeto' },
  { item_number: 24, title: 'Termo de encerramento da execução do objeto - fim da execução dos recursos, para consolidação do Termo de Compromisso' },
];
const ALL_CHECKLIST_NUMS = ALL_CHECKLIST_ITEMS.map((i) => i.item_number);

function formatBRL(val: number): string {
  return 'R$ ' + Number(val || 0).toFixed(2).replace('.', ',');
}

function formatFileSize(bytes: number): string {
  if (!bytes || bytes < 1024) return `${bytes || 0} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function formatDateMask(raw: string): string {
  const digits = raw.replace(/\D/g, '').slice(0, 8);
  if (digits.length <= 2) return digits;
  if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
  return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
}

function formatDateFromOffset(daysFromToday: number): string {
  const d = new Date();
  d.setDate(d.getDate() + daysFromToday);
  const dd = String(d.getDate()).padStart(2, '0');
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const yyyy = d.getFullYear();
  return `${dd}/${mm}/${yyyy}`;
}

function formatEndOfMonth(): string {
  const now = new Date();
  const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  const dd = String(last.getDate()).padStart(2, '0');
  const mm = String(last.getMonth() + 1).padStart(2, '0');
  const yyyy = last.getFullYear();
  return `${dd}/${mm}/${yyyy}`;
}

const BRAND_SHIELD_IMG = require('./assets/brand-shield.png');
const SISTEMA_LOGO_IMG = require('./assets/sistema-logo.png');

function BrandShieldIcon({ size = 28 }: { size?: number }) {
  return (
    <Image
      source={BRAND_SHIELD_IMG}
      style={{ width: size, height: size, resizeMode: 'contain' }}
    />
  );
}

function DesktopLogo({ height = 78 }: { apiUrl?: string; height?: number }) {
  return (
    <Image
      source={SISTEMA_LOGO_IMG}
      style={{ width: Math.round(height * 2.7), maxWidth: '100%', height, resizeMode: 'contain' }}
    />
  );
}

export default function App() {
  const [booting, setBooting] = useState(true);
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<MobileUser | null>(null);
  const [guestScreen, setGuestScreen] = useState<GuestScreen>('home');

  // Login form
  const [apiUrl, setApiUrlState] = useState(DEFAULT_API_URL);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('123456');
  const [loginLoading, setLoginLoading] = useState(false);
  const [quickUsers, setQuickUsers] = useState<QuickLoginUser[]>([]);
  const [quickDropdownOpen, setQuickDropdownOpen] = useState(false);

  // Esqueci minha senha (3 etapas: 1=e-mail, 2=código 6 dígitos, 3=nova senha)
  const [resetStep, setResetStep] = useState<1 | 2 | 3>(1);
  const [resetEmail, setResetEmail] = useState('');
  const [resetCode, setResetCode] = useState('');
  const [resetNewPass, setResetNewPass] = useState('');
  const [resetConfirmPass, setResetConfirmPass] = useState('');
  const [resetLoading, setResetLoading] = useState(false);
  const [resetFeedback, setResetFeedback] = useState<{ type: 'ok' | 'err'; text: string } | null>(null);

  // Mobile Menu Navigation (replaces desktop sidebar)
  const [screen, setScreen] = useState<AppScreen>('menu');
  const [selectedDetail, setSelectedDetail] = useState<AccountabilityDetail | null>(null);
  const [selectedHistoryYear, setSelectedHistoryYear] = useState<string | null>(null);
  const [annualMenuExpanded, setAnnualMenuExpanded] = useState(false);

  // Data
  const [accountabilities, setAccountabilities] = useState<AccountabilitySummary[]>([]);
  const [spreadsheetRows, setSpreadsheetRows] = useState<SpreadsheetSummaryRow[]>([]);
  const [historyYears, setHistoryYears] = useState<
    Record<string, Array<{ id: number; year: number; title: string; deadline: string }>>
  >({});
  const [auditLogs, setAuditLogs] = useState<AuditLogRow[]>([]);
  const [usersList, setUsersList] = useState<MobileUser[]>([]);
  const [settingsData, setSettingsData] = useState<SettingsSummaryData | null>(null);

  const [refreshing, setRefreshing] = useState(false);
  const [uploadingItem, setUploadingItem] = useState<number | null>(null);
  const [submittingAcc, setSubmittingAcc] = useState(false);
  const [selectedItemNumber, setSelectedItemNumber] = useState<number | null>(null);
  const [previewImage, setPreviewImage] = useState<{ uri: string; name: string } | null>(null);
  const [deletingAttId, setDeletingAttId] = useState<number | null>(null);

  // Modal Nova Prestação de Contas (Admin)
  const [newAccModalOpen, setNewAccModalOpen] = useState(false);
  const [newAccTitle, setNewAccTitle] = useState('');
  const [newAccDesc, setNewAccDesc] = useState('');
  const [newAccDeadline, setNewAccDeadline] = useState('');
  const [newAccEnabledItems, setNewAccEnabledItems] = useState<number[]>(ALL_CHECKLIST_NUMS);
  const [newAccSaving, setNewAccSaving] = useState(false);

  // Edição de Usuário e Permissões (Admin)
  const [editingUser, setEditingUser] = useState<EditableUserDetail | null>(null);
  const [editUserName, setEditUserName] = useState('');
  const [editUserEmail, setEditUserEmail] = useState('');
  const [editUserWhatsapp, setEditUserWhatsapp] = useState('');
  const [editUserRole, setEditUserRole] = useState<'admin' | 'user'>('user');
  const [editUserPassword, setEditUserPassword] = useState('');
  const [editUserAllowedItems, setEditUserAllowedItems] = useState<number[]>([]);
  const [editUserSaving, setEditUserSaving] = useState(false);

  // Cobrança / Lembrete por WhatsApp (Admin)
  const [waModalOpen, setWaModalOpen] = useState(false);
  const [waTargetItemNum, setWaTargetItemNum] = useState<number | null>(null);
  const [waCustomMsg, setWaCustomMsg] = useState('');
  const [waSendingUserId, setWaSendingUserId] = useState<number | null>(null);

  // Meu Perfil (formulário detalhado)
  const [profileName, setProfileName] = useState('');
  const [profileEmail, setProfileEmail] = useState('');
  const [profileWhatsapp, setProfileWhatsapp] = useState('');
  const [profileNewPassword, setProfileNewPassword] = useState('');
  const [profileConfirmPassword, setProfileConfirmPassword] = useState('');
  const [profileAvatarPick, setProfileAvatarPick] = useState<{
    uri: string;
    name: string;
    mimeType?: string;
  } | null>(null);
  const [profileSaving, setProfileSaving] = useState(false);
  const [profileFeedback, setProfileFeedback] = useState<{ type: 'ok' | 'err'; text: string } | null>(null);
  const [avatarImgError, setAvatarImgError] = useState(false);

  const isAdmin = user?.role === 'admin';

  useEffect(() => {
    (async () => {
      const savedUrl = await getApiUrl();
      setApiUrlState(savedUrl);
      const session = await getStoredSession();
      if (session) {
        setToken(session.token);
        setUser(session.user);
        setScreen(session.user.role === 'admin' ? 'menu' : 'accountabilities');
      }
      setBooting(false);
    })();
  }, []);

  useEffect(() => {
    if (token) {
      loadScreenData(token, screen);
    }
  }, [token, screen]);

  useEffect(() => {
    if (!token && guestScreen === 'login') {
      apiGetQuickLoginUsers(apiUrl)
        .then((list) => setQuickUsers(list))
        .catch(() => {});
    }
  }, [token, guestScreen, apiUrl]);

  async function loadScreenData(authToken: string, target: AppScreen) {
    if (target === 'profile') {
      if (user) {
        setProfileName(user.name || '');
        setProfileEmail(user.email || '');
        setProfileWhatsapp(user.whatsapp || '');
      }
      setProfileNewPassword('');
      setProfileConfirmPassword('');
      setProfileAvatarPick(null);
      setProfileFeedback(null);
      setAvatarImgError(false);
      try {
        const fresh = await apiGetMe(authToken);
        setUser(fresh);
        setProfileName(fresh.name || '');
        setProfileEmail(fresh.email || '');
        setProfileWhatsapp(fresh.whatsapp || '');
      } catch {
        // mantém os dados em cache se offline
      }
      return;
    }
    setRefreshing(true);
    try {
      if (!isAdmin && target !== 'accountabilities') {
        setScreen('accountabilities');
        setAccountabilities(await apiGetAccountabilities(authToken));
      } else if (target === 'menu' && isAdmin) {
        // Carrega anos do histórico anual para o submenu abre/fecha no menu mobile
        const years = await apiGetAnnualHistory(authToken);
        setHistoryYears(years);
      } else if (target === 'accountabilities') {
        setAccountabilities(await apiGetAccountabilities(authToken));
      } else if (target === 'spreadsheet' && isAdmin) {
        setSpreadsheetRows(await apiGetSpreadsheetSummary(authToken));
      } else if (target === 'annual_history' && isAdmin) {
        setHistoryYears(await apiGetAnnualHistory(authToken));
      } else if (target === 'audit' && isAdmin) {
        setAuditLogs(await apiGetAuditHistory(authToken));
      } else if (target === 'users' && isAdmin) {
        setUsersList(await apiGetUsers(authToken));
      } else if (target === 'settings' && isAdmin) {
        setSettingsData(await apiGetSettingsSummary(authToken));
      }
    } catch (e: unknown) {
      Alert.alert('Aviso', e instanceof Error ? e.message : 'Erro ao conectar na API.');
    } finally {
      setRefreshing(false);
    }
  }

  async function handleLogin() {
    if (!email.trim() || !password) {
      Alert.alert('Campos obrigatórios', 'Informe seu e-mail e senha.');
      return;
    }
    setLoginLoading(true);
    try {
      const res = await apiLogin(apiUrl, email.trim(), password);
      setToken(res.token);
      setUser(res.user);
      setPassword('123456');
      setScreen(res.user.role === 'admin' ? 'menu' : 'accountabilities');
    } catch (e: unknown) {
      Alert.alert('Erro no login', e instanceof Error ? e.message : 'Não foi possível conectar.');
    } finally {
      setLoginLoading(false);
    }
  }

  async function handlePickProfilePhoto(source: 'camera' | 'gallery') {
    try {
      if (source === 'camera') {
        const perm = await ImagePicker.requestCameraPermissionsAsync();
        if (!perm.granted) {
          Alert.alert('Permissão necessária', 'Permita o acesso à câmera para tirar uma foto.');
          return;
        }
      } else {
        const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
        if (!perm.granted) {
          Alert.alert('Permissão necessária', 'Permita o acesso à galeria para escolher uma foto.');
          return;
        }
      }

      const opts: ImagePicker.ImagePickerOptions = {
        mediaTypes: ['images'],
        allowsEditing: true,
        aspect: [1, 1],
        quality: 0.72,
      };

      const result =
        source === 'camera'
          ? await ImagePicker.launchCameraAsync(opts)
          : await ImagePicker.launchImageLibraryAsync(opts);

      if (result.canceled || !result.assets?.length) return;
      const asset = result.assets[0];
      const extFromMime = asset.mimeType === 'image/png' ? 'png' : asset.mimeType === 'image/webp' ? 'webp' : 'jpg';
      const fallbackName = `foto_perfil_${Date.now()}.${extFromMime}`;
      setProfileAvatarPick({
        uri: asset.uri,
        name: asset.fileName || fallbackName,
        mimeType: asset.mimeType || 'image/jpeg',
      });
      setAvatarImgError(false);
      setProfileFeedback(null);
    } catch (e: unknown) {
      Alert.alert('Erro', e instanceof Error ? e.message : 'Não foi possível capturar ou selecionar a imagem.');
    }
  }

  async function handleRemoveProfilePhoto() {
    if (profileAvatarPick) {
      setProfileAvatarPick(null);
      return;
    }
    if (!token || !user?.avatar_url) return;
    setProfileSaving(true);
    setProfileFeedback(null);
    try {
      const res = await apiRemoveAvatar(token);
      setUser(res.user);
      setProfileFeedback({ type: 'ok', text: res.message });
    } catch (e: unknown) {
      setProfileFeedback({
        type: 'err',
        text: e instanceof Error ? e.message : 'Erro ao remover foto.',
      });
    } finally {
      setProfileSaving(false);
    }
  }

  async function handleSaveProfile() {
    if (!token) return;
    const cleanName = profileName.trim();
    const cleanEmail = profileEmail.trim();
    const cleanWhatsapp = profileWhatsapp.trim();

    if (cleanName.length < 3) {
      setProfileFeedback({ type: 'err', text: 'O nome deve ter pelo menos 3 caracteres.' });
      return;
    }
    if (!cleanEmail || !cleanEmail.includes('@')) {
      setProfileFeedback({ type: 'err', text: 'Informe um e-mail válido.' });
      return;
    }
    if (profileNewPassword.length > 0 && profileNewPassword.length < 6) {
      setProfileFeedback({ type: 'err', text: 'A nova senha deve ter pelo menos 6 caracteres.' });
      return;
    }
    if (profileNewPassword !== profileConfirmPassword) {
      setProfileFeedback({ type: 'err', text: 'A confirmação de senha não confere com a nova senha.' });
      return;
    }

    setProfileSaving(true);
    setProfileFeedback(null);
    try {
      const res = await apiUpdateProfile(token, {
        name: cleanName,
        email: cleanEmail,
        whatsapp: cleanWhatsapp,
        new_password: profileNewPassword || undefined,
        confirm_password: profileConfirmPassword || undefined,
        avatar: profileAvatarPick,
      });
      setUser(res.user);
      setProfileAvatarPick(null);
      setProfileNewPassword('');
      setProfileConfirmPassword('');
      setAvatarImgError(false);
      setProfileFeedback({ type: 'ok', text: res.message });
    } catch (e: unknown) {
      setProfileFeedback({
        type: 'err',
        text: e instanceof Error ? e.message : 'Erro ao salvar perfil.',
      });
    } finally {
      setProfileSaving(false);
    }
  }

  async function handleSendResetCode() {
    if (!resetEmail.trim()) {
      setResetFeedback({ type: 'err', text: 'Informe o e-mail cadastrado.' });
      return;
    }
    setResetLoading(true);
    setResetFeedback(null);
    try {
      const msg = await apiForgotPassword(apiUrl, resetEmail.trim());
      setResetFeedback({ type: 'ok', text: msg });
      setResetStep(2);
    } catch (e: unknown) {
      setResetFeedback({ type: 'err', text: e instanceof Error ? e.message : 'Erro ao enviar código.' });
    } finally {
      setResetLoading(false);
    }
  }

  async function handleVerifyResetCode() {
    if (resetCode.replace(/\D/g, '').length !== 6) {
      setResetFeedback({ type: 'err', text: 'Digite os 6 dígitos do código.' });
      return;
    }
    setResetLoading(true);
    setResetFeedback(null);
    try {
      const msg = await apiVerifyResetCode(apiUrl, resetEmail.trim(), resetCode.trim());
      setResetFeedback({ type: 'ok', text: msg });
      setResetStep(3);
    } catch (e: unknown) {
      setResetFeedback({ type: 'err', text: e instanceof Error ? e.message : 'Código inválido.' });
    } finally {
      setResetLoading(false);
    }
  }

  async function handleConfirmResetPassword() {
    if (resetNewPass.length < 6) {
      setResetFeedback({ type: 'err', text: 'A nova senha deve ter pelo menos 6 caracteres.' });
      return;
    }
    if (resetNewPass !== resetConfirmPass) {
      setResetFeedback({ type: 'err', text: 'A confirmação de senha não confere.' });
      return;
    }
    setResetLoading(true);
    setResetFeedback(null);
    try {
      const msg = await apiResetPassword(
        apiUrl,
        resetEmail.trim(),
        resetCode.trim(),
        resetNewPass,
        resetConfirmPass
      );
      Alert.alert('Sucesso', msg);
      setEmail(resetEmail.trim());
      setResetStep(1);
      setResetCode('');
      setResetNewPass('');
      setResetConfirmPass('');
      setResetFeedback(null);
      setGuestScreen('login');
    } catch (e: unknown) {
      setResetFeedback({ type: 'err', text: e instanceof Error ? e.message : 'Erro ao redefinir senha.' });
    } finally {
      setResetLoading(false);
    }
  }

  async function handleLogout() {
    await clearSession();
    setToken(null);
    setUser(null);
    setSelectedDetail(null);
    setScreen('menu');
    setGuestScreen('home');
  }

  async function openDetail(id: number) {
    if (!token) return;
    setRefreshing(true);
    try {
      const detail = await apiGetAccountabilityDetail(token, id);
      setSelectedDetail(detail);
    } catch (e: unknown) {
      Alert.alert('Erro', e instanceof Error ? e.message : 'Erro ao abrir prestação.');
    } finally {
      setRefreshing(false);
    }
  }

  async function handleUploadPhoto(itemNumber: number, source: 'camera' | 'gallery') {
    if (!token || !selectedDetail) return;
    try {
      if (source === 'camera') {
        const perm = await ImagePicker.requestCameraPermissionsAsync();
        if (!perm.granted) {
          Alert.alert('Permissão necessária', 'Permita o acesso à câmera para fotografar o documento.');
          return;
        }
      } else {
        const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
        if (!perm.granted) {
          Alert.alert('Permissão necessária', 'Permita o acesso à galeria para escolher uma foto.');
          return;
        }
      }

      const opts: ImagePicker.ImagePickerOptions = {
        mediaTypes: ['images'],
        quality: 0.72,
      };

      const result =
        source === 'camera'
          ? await ImagePicker.launchCameraAsync(opts)
          : await ImagePicker.launchImageLibraryAsync(opts);

      if (result.canceled || !result.assets?.length) return;
      const asset = result.assets[0];
      const extFromMime =
        asset.mimeType === 'image/png' ? 'png' : asset.mimeType === 'image/webp' ? 'webp' : 'jpg';
      const fileName = asset.fileName || `documento_item_${itemNumber}_${Date.now()}.${extFromMime}`;

      setUploadingItem(itemNumber);
      await apiUploadAttachment(token, selectedDetail.id, itemNumber, {
        uri: asset.uri,
        name: fileName,
        mimeType: asset.mimeType || 'image/jpeg',
      });
      Alert.alert('Sucesso', `Foto "${fileName}" anexada ao Item ${itemNumber}!`);
      await openDetail(selectedDetail.id);
    } catch (e: unknown) {
      Alert.alert('Erro no envio', e instanceof Error ? e.message : 'Falha ao enviar foto.');
    } finally {
      setUploadingItem(null);
    }
  }

  async function handleUpload(itemNumber: number) {
    if (!token || !selectedDetail) return;
    try {
      const picked = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'image/jpeg', 'image/png', 'application/zip'],
        copyToCacheDirectory: true,
      });
      if (picked.canceled || !picked.assets?.length) return;

      const asset = picked.assets[0];
      setUploadingItem(itemNumber);
      await apiUploadAttachment(token, selectedDetail.id, itemNumber, {
        uri: asset.uri,
        name: asset.name,
        mimeType: asset.mimeType,
      });
      Alert.alert('Sucesso', `Arquivo "${asset.name}" anexado ao Item ${itemNumber}!`);
      await openDetail(selectedDetail.id);
    } catch (e: unknown) {
      Alert.alert('Erro no envio', e instanceof Error ? e.message : 'Falha ao enviar arquivo.');
    } finally {
      setUploadingItem(null);
    }
  }

  async function handleSubmitAccountability() {
    if (!token || !selectedDetail) return;
    setSubmittingAcc(true);
    try {
      const msg = await apiSubmitAccountability(token, selectedDetail.id);
      Alert.alert('Envio Confirmado', msg);
      setSelectedItemNumber(null);
      setSelectedDetail(null);
      await loadScreenData(token, 'accountabilities');
    } catch (e: unknown) {
      Alert.alert('Atenção', e instanceof Error ? e.message : 'Não foi possível confirmar o envio.');
    } finally {
      setSubmittingAcc(false);
    }
  }

  async function handlePreviewAttachment(att: {
    id: number;
    original_name: string;
    file_ext: string;
  }) {
    if (!token) return;
    const url = `${apiUrl.replace(/\/+$/, '')}/api/mobile/attachments/${att.id}/preview?token=${encodeURIComponent(token)}`;
    const ext = (att.file_ext || '').toLowerCase();
    if (['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
      setPreviewImage({ uri: url, name: att.original_name });
      return;
    }
    try {
      await Linking.openURL(url);
    } catch {
      Alert.alert('Erro', 'Não foi possível abrir o arquivo para visualização.');
    }
  }

  async function handleDeleteAttachment(attId: number) {
    if (!token || !selectedDetail) return;
    setDeletingAttId(attId);
    try {
      const msg = await apiDeleteAttachment(token, attId);
      Alert.alert('Sucesso', msg);
      await openDetail(selectedDetail.id);
    } catch (e: unknown) {
      Alert.alert('Erro', e instanceof Error ? e.message : 'Não foi possível remover o anexo.');
    } finally {
      setDeletingAttId(null);
    }
  }

  function toggleNewAccItem(itemNum: number) {
    setNewAccEnabledItems((prev) =>
      prev.includes(itemNum) ? prev.filter((n) => n !== itemNum) : [...prev, itemNum].sort((a, b) => a - b)
    );
  }

  async function handleCreateAccountability() {
    if (!token) return;
    const cleanTitle = newAccTitle.trim();
    if (cleanTitle.length < 3) {
      Alert.alert('Campo obrigatório', 'Informe um título com pelo menos 3 caracteres.');
      return;
    }
    if (newAccEnabledItems.length === 0) {
      Alert.alert('Campos obrigatórios', 'Selecione pelo menos um item/campo para esta prestação de contas.');
      return;
    }
    setNewAccSaving(true);
    try {
      const res = await apiCreateAccountability(token, {
        title: cleanTitle,
        description: newAccDesc.trim(),
        deadline: newAccDeadline.trim(),
        enabled_items: newAccEnabledItems,
      });
      setNewAccModalOpen(false);
      setNewAccTitle('');
      setNewAccDesc('');
      setNewAccDeadline('');
      setNewAccEnabledItems(ALL_CHECKLIST_NUMS);
      Alert.alert('Sucesso', res.message);
      await loadScreenData(token, 'accountabilities');
    } catch (e: unknown) {
      Alert.alert('Erro', e instanceof Error ? e.message : 'Não foi possível criar a prestação.');
    } finally {
      setNewAccSaving(false);
    }
  }

  async function openEditUser(userId: number) {
    if (!token) return;
    setRefreshing(true);
    try {
      const detail = await apiGetUserDetail(token, userId);
      setEditingUser(detail);
      setEditUserName(detail.name || '');
      setEditUserEmail(detail.email || '');
      setEditUserWhatsapp(detail.whatsapp || '');
      setEditUserRole(detail.role === 'admin' ? 'admin' : 'user');
      setEditUserPassword('');
      setEditUserAllowedItems(detail.allowed_items || []);
    } catch (e: unknown) {
      Alert.alert('Erro', e instanceof Error ? e.message : 'Não foi possível abrir o usuário.');
    } finally {
      setRefreshing(false);
    }
  }

  function toggleUserPermissionItem(itemNum: number) {
    setEditUserAllowedItems((prev) =>
      prev.includes(itemNum) ? prev.filter((n) => n !== itemNum) : [...prev, itemNum].sort((a, b) => a - b)
    );
  }

  async function handleSaveEditUser() {
    if (!token || !editingUser) return;
    if (editUserName.trim().length < 3) {
      Alert.alert('Atenção', 'O nome deve ter pelo menos 3 caracteres.');
      return;
    }
    if (!editUserEmail.trim() || !editUserEmail.includes('@')) {
      Alert.alert('Atenção', 'Informe um e-mail válido.');
      return;
    }
    if (editUserPassword.length > 0 && editUserPassword.length < 6) {
      Alert.alert('Atenção', 'A nova senha deve ter pelo menos 6 caracteres.');
      return;
    }
    setEditUserSaving(true);
    try {
      const msg = await apiUpdateUser(token, editingUser.id, {
        name: editUserName.trim(),
        email: editUserEmail.trim(),
        whatsapp: editUserWhatsapp.trim(),
        role: editUserRole,
        password: editUserPassword || undefined,
        allowed_items: editUserAllowedItems,
      });
      Alert.alert('Sucesso', msg);
      setEditingUser(null);
      await loadScreenData(token, 'users');
    } catch (e: unknown) {
      Alert.alert('Erro', e instanceof Error ? e.message : 'Não foi possível salvar o usuário.');
    } finally {
      setEditUserSaving(false);
    }
  }

  async function openWhatsappModal(itemNumber: number | null) {
    setWaTargetItemNum(itemNumber);
    setWaCustomMsg('');
    setWaModalOpen(true);
    if (token && isAdmin && usersList.length === 0) {
      try {
        setUsersList(await apiGetUsers(token));
      } catch {
        // ignora se offline
      }
    }
  }

  function buildWhatsappText(
    targetName: string,
    itemNumber: number | null,
    pendingNums: number[]
  ): string {
    if (waCustomMsg.trim()) return waCustomMsg.trim();
    const accTitle = selectedDetail?.title || 'Prestação de Contas';
    const deadline = selectedDetail?.deadline || 'Não definido';
    if (itemNumber !== null) {
      const found = selectedDetail?.items.find((i) => i.item_number === itemNumber);
      const numPad = String(itemNumber).padStart(2, '0');
      const label = found?.title ? ` — ${found.title}` : '';
      return (
        `Olá, ${targetName}! 👋\n\n` +
        `Lembrete da *Prestação de Contas: ${accTitle}* (Prazo: ${deadline}).\n\n` +
        `Ainda aguardamos o envio dos anexos do *Item ${numPad}${label}*.\n\n` +
        `Por favor, acesse o aplicativo Prestação de Contas para anexar os documentos e confirmar o envio. Obrigado!`
      );
    }
    const itensStr =
      pendingNums.length > 0
        ? `Itens pendentes: ${pendingNums.map((n) => String(n).padStart(2, '0')).join(', ')}.`
        : 'Você possui itens pendentes de envio nesta prestação de contas.';
    return (
      `Olá, ${targetName}! 👋\n\n` +
      `Lembrete da *Prestação de Contas: ${accTitle}* (Prazo: ${deadline}).\n\n` +
      `${itensStr}\n\n` +
      `Por favor, acesse o aplicativo Prestação de Contas para anexar os documentos e confirmar o envio. Obrigado!`
    );
  }

  async function handleSendWhatsappReminder(
    targetUser: { id: number; name: string; whatsapp: string },
    pendingNums: number[],
    mode: 'phone' | 'api'
  ) {
    if (!token || !selectedDetail) return;
    let digits = (targetUser.whatsapp || '').replace(/\D/g, '');
    if (digits.length < 10) {
      Alert.alert(
        'Sem WhatsApp',
        `${targetUser.name} não possui número de WhatsApp cadastrado. Cadastre em Usuários e Permissões.`
      );
      return;
    }
    if (digits.length <= 11 && !digits.startsWith('55')) {
      digits = '55' + digits;
    }

    const msgText = buildWhatsappText(targetUser.name, waTargetItemNum, pendingNums);

    if (mode === 'phone') {
      const waUrl = `https://wa.me/${digits}?text=${encodeURIComponent(msgText)}`;
      try {
        await Linking.openURL(waUrl);
      } catch {
        Alert.alert('Erro', 'Não foi possível abrir o WhatsApp no celular.');
      }
      return;
    }

    setWaSendingUserId(targetUser.id);
    try {
      const res = await apiRemindWhatsapp(token, selectedDetail.id, {
        user_id: targetUser.id,
        item_number: waTargetItemNum ?? undefined,
        custom_message: msgText,
      });
      if (res.sent_via_api) {
        Alert.alert('Enviado!', res.message);
      } else {
        Alert.alert(
          'Abrir no WhatsApp',
          'A API automática do WhatsApp não está ativa no servidor. Abrindo a mensagem direto no WhatsApp do celular.'
        );
        if (res.wa_url) {
          await Linking.openURL(res.wa_url);
        }
      }
    } catch (e: unknown) {
      Alert.alert('Erro', e instanceof Error ? e.message : 'Não foi possível enviar a cobrança.');
    } finally {
      setWaSendingUserId(null);
    }
  }

  function navigateTo(target: AppScreen, yearFilter: string | null = null) {
    setEditingUser(null);
    setSelectedItemNumber(null);
    setSelectedDetail(null);
    setSelectedHistoryYear(yearFilter);
    if (!isAdmin && target !== 'accountabilities' && target !== 'profile') {
      setScreen('accountabilities');
      return;
    }
    setScreen(target);
  }

  if (booting) {
    return (
      <SafeAreaView style={styles.center}>
        <Image
          source={SISTEMA_LOGO_IMG}
          style={{ width: 220, height: 110, resizeMode: 'contain', marginBottom: 18 }}
        />
        <ActivityIndicator size="large" color={COLORS.orange} />
      </SafeAreaView>
    );
  }

  // 1. HOME DEMONSTRATIVA (Clean & Minimalista)
  if (!token || !user) {
    if (guestScreen === 'home') {
      return (
        <SafeAreaView style={styles.cleanHomeRoot}>
          <StatusBar style="dark" />

          <View style={styles.cleanHomeCenter}>
            <View style={{ alignItems: 'center', marginBottom: 24 }}>
              <DesktopLogo apiUrl={apiUrl} height={110} />
            </View>

            <View style={styles.cleanAccentLine} />

            <Text style={styles.cleanHomeTitle}>Prestação de Contas</Text>
            <Text style={styles.cleanHomeSub}>Município de Arabutã</Text>

            <TouchableOpacity
              style={styles.cleanHomeBtn}
              activeOpacity={0.86}
              onPress={() => setGuestScreen('login')}
            >
              <Text style={styles.cleanHomeBtnText}>Entrar</Text>
              <FontAwesome5 name="arrow-right" size={14} color="#ffffff" />
            </TouchableOpacity>
          </View>

          <Text style={styles.cleanHomeFooter}>
            © {new Date().getFullYear()} Município de Arabutã
          </Text>
        </SafeAreaView>
      );
    }

    // 2. ESQUECI MINHA SENHA (3 etapas via código SMTP de 6 dígitos)
    if (guestScreen === 'forgot') {
      return (
        <SafeAreaView style={styles.guestRoot}>
          <StatusBar style="dark" />
          <View style={styles.topBar}>
            <TouchableOpacity style={styles.topBarBrand} onPress={() => setGuestScreen('home')}>
              <BrandShieldIcon size={32} />
              <Text style={styles.topBarTitle}>Prestação de Contas</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={styles.topBarBackBtn}
              onPress={() => {
                setResetFeedback(null);
                setGuestScreen('login');
              }}
            >
              <Text style={styles.topBarBackText}>← Login</Text>
            </TouchableOpacity>
          </View>

          <ScrollView contentContainerStyle={styles.loginInner} keyboardShouldPersistTaps="handled">
            <View style={styles.loginCard}>
              <View style={{ alignItems: 'center', marginBottom: 12 }}>
                <DesktopLogo apiUrl={apiUrl} height={66} />
              </View>

              <Text style={styles.loginTitle}>Esqueci minha senha</Text>
              <Text style={styles.loginSub}>
                {resetStep === 1
                  ? 'Informe o e-mail da sua conta para receber um código de 6 dígitos.'
                  : resetStep === 2
                  ? 'Digite o código de 6 dígitos enviado para o seu e-mail.'
                  : 'Código validado! Defina sua nova senha de acesso.'}
              </Text>

              {resetFeedback && (
                <View
                  style={[
                    styles.feedbackBox,
                    {
                      backgroundColor: resetFeedback.type === 'ok' ? '#f0fdf4' : '#fef2f2',
                      borderColor: resetFeedback.type === 'ok' ? '#bbf7d0' : '#fecaca',
                    },
                  ]}
                >
                  <Text
                    style={{
                      fontSize: 12,
                      fontWeight: '600',
                      color: resetFeedback.type === 'ok' ? '#15803d' : '#b91c1c',
                    }}
                  >
                    {resetFeedback.text}
                  </Text>
                </View>
              )}

              {resetStep === 1 && (
                <>
                  <Text style={styles.label}>E-mail cadastrado</Text>
                  <TextInput
                    style={styles.input}
                    value={resetEmail}
                    onChangeText={setResetEmail}
                    keyboardType="email-address"
                    autoCapitalize="none"
                    placeholder="seu@email.gov.br"
                  />

                  <TouchableOpacity
                    style={[styles.primaryBtn, resetLoading && { opacity: 0.7 }]}
                    onPress={handleSendResetCode}
                    disabled={resetLoading}
                  >
                    {resetLoading ? (
                      <ActivityIndicator color="#ffffff" />
                    ) : (
                      <Text style={styles.primaryBtnText}>Enviar código de 6 dígitos</Text>
                    )}
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={styles.linkBtn}
                    onPress={() => {
                      setResetFeedback(null);
                      setResetStep(2);
                    }}
                  >
                    <Text style={styles.linkBtnText}>Já recebi o código de 6 dígitos</Text>
                  </TouchableOpacity>
                </>
              )}

              {resetStep === 2 && (
                <>
                  <Text style={styles.label}>E-mail</Text>
                  <TextInput
                    style={styles.input}
                    value={resetEmail}
                    onChangeText={setResetEmail}
                    keyboardType="email-address"
                    autoCapitalize="none"
                    placeholder="seu@email.gov.br"
                  />

                  <Text style={[styles.label, { marginTop: 12 }]}>Código de 6 dígitos</Text>
                  <TextInput
                    style={[styles.input, { textAlign: 'center', fontSize: 20, fontWeight: '800', letterSpacing: 6 }]}
                    value={resetCode}
                    onChangeText={(v) => setResetCode(v.replace(/\D/g, '').slice(0, 6))}
                    keyboardType="number-pad"
                    maxLength={6}
                    placeholder="000000"
                  />

                  <TouchableOpacity
                    style={[styles.primaryBtn, resetLoading && { opacity: 0.7 }]}
                    onPress={handleVerifyResetCode}
                    disabled={resetLoading}
                  >
                    {resetLoading ? (
                      <ActivityIndicator color="#ffffff" />
                    ) : (
                      <Text style={styles.primaryBtnText}>Verificar código</Text>
                    )}
                  </TouchableOpacity>

                  <TouchableOpacity style={styles.linkBtn} onPress={handleSendResetCode} disabled={resetLoading}>
                    <Text style={styles.linkBtnText}>Reenviar código</Text>
                  </TouchableOpacity>
                </>
              )}

              {resetStep === 3 && (
                <>
                  <Text style={styles.label}>Nova Senha</Text>
                  <TextInput
                    style={styles.input}
                    value={resetNewPass}
                    onChangeText={setResetNewPass}
                    secureTextEntry
                    placeholder="Mínimo 6 caracteres"
                  />

                  <Text style={[styles.label, { marginTop: 12 }]}>Confirmar Nova Senha</Text>
                  <TextInput
                    style={styles.input}
                    value={resetConfirmPass}
                    onChangeText={setResetConfirmPass}
                    secureTextEntry
                    placeholder="Repita a nova senha"
                  />

                  <TouchableOpacity
                    style={[styles.primaryBtn, resetLoading && { opacity: 0.7 }]}
                    onPress={handleConfirmResetPassword}
                    disabled={resetLoading}
                  >
                    {resetLoading ? (
                      <ActivityIndicator color="#ffffff" />
                    ) : (
                      <Text style={styles.primaryBtnText}>Redefinir senha</Text>
                    )}
                  </TouchableOpacity>
                </>
              )}
            </View>
          </ScrollView>
        </SafeAreaView>
      );
    }

    // 3. LOGIN
    return (
      <SafeAreaView style={styles.guestRoot}>
        <StatusBar style="dark" />
        <View style={styles.topBar}>
          <TouchableOpacity style={styles.topBarBrand} onPress={() => setGuestScreen('home')}>
            <BrandShieldIcon size={32} />
            <Text style={styles.topBarTitle}>Prestação de Contas</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.topBarBackBtn} onPress={() => setGuestScreen('home')}>
            <Text style={styles.topBarBackText}>← Home</Text>
          </TouchableOpacity>
        </View>

        <ScrollView contentContainerStyle={styles.loginInner} keyboardShouldPersistTaps="handled">
          <View style={styles.loginCard}>
            <View style={{ alignItems: 'center', marginBottom: 14 }}>
              <DesktopLogo apiUrl={apiUrl} height={70} />
            </View>
            <Text style={styles.loginTitle}>Login Rápido</Text>
            <Text style={styles.loginSub}>Selecione um usuário ou informe suas credenciais</Text>

            {/* SELECT RÁPIDO DESENVOLVEDOR */}
            <Text style={styles.label}>Selecionar Usuário (Dev)</Text>
            <TouchableOpacity
              style={styles.selectBox}
              activeOpacity={0.8}
              onPress={() => setQuickDropdownOpen((prev) => !prev)}
            >
              <Text
                style={[styles.selectBoxText, !email && { color: '#94a3b8' }]}
                numberOfLines={1}
              >
                {(() => {
                  const found = quickUsers.find((u) => u.email === email);
                  if (found) {
                    const roleTag = found.role === 'admin' ? 'ADMIN' : 'USER';
                    return `[${roleTag}] ${found.name} (${found.email})`;
                  }
                  return '-- Escolha uma conta --';
                })()}
              </Text>
              <FontAwesome5
                name={quickDropdownOpen ? 'chevron-up' : 'chevron-down'}
                size={13}
                color={COLORS.muted}
              />
            </TouchableOpacity>

            {quickDropdownOpen && (
              <View style={styles.selectDropdown}>
                {quickUsers.length === 0 ? (
                  <Text style={[styles.hint, { padding: 10, marginTop: 0 }]}>
                    Nenhum usuário carregado do servidor.
                  </Text>
                ) : (
                  quickUsers.map((u) => {
                    const isSelected = email === u.email;
                    const isAdm = u.role === 'admin';
                    return (
                      <TouchableOpacity
                        key={u.id}
                        style={[
                          styles.selectOption,
                          isSelected && styles.selectOptionActive,
                        ]}
                        onPress={() => {
                          setEmail(u.email);
                          setPassword('123456');
                          setQuickDropdownOpen(false);
                        }}
                      >
                        <View
                          style={[
                            styles.selectRoleBadge,
                            { backgroundColor: isAdm ? COLORS.orangeSoft : COLORS.bgSoft },
                          ]}
                        >
                          <Text
                            style={[
                              styles.selectRoleText,
                              { color: isAdm ? COLORS.orangeText : COLORS.muted },
                            ]}
                          >
                            {isAdm ? 'ADMIN' : 'USER'}
                          </Text>
                        </View>
                        <View style={{ flex: 1 }}>
                          <Text style={styles.selectOptionName} numberOfLines={1}>
                            {u.name}
                          </Text>
                          <Text style={styles.selectOptionEmail} numberOfLines={1}>
                            {u.email}
                          </Text>
                        </View>
                      </TouchableOpacity>
                    );
                  })
                )}
              </View>
            )}

            <Text style={[styles.label, { marginTop: 12 }]}>E-mail</Text>
            <TextInput
              style={styles.input}
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              placeholder="seu@email.gov.br"
            />

            <Text style={[styles.label, { marginTop: 12 }]}>Senha</Text>
            <TextInput
              style={styles.input}
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              placeholder="Preenchida automaticamente (123456)"
            />
            <Text style={styles.hint}>Modo desenvolvedor — senha padrão: 123456</Text>

            <TouchableOpacity
              style={[styles.primaryBtn, loginLoading && { opacity: 0.7 }]}
              onPress={handleLogin}
              disabled={loginLoading}
            >
              {loginLoading ? (
                <ActivityIndicator color="#ffffff" />
              ) : (
                <Text style={styles.primaryBtnText}>Entrar</Text>
              )}
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.linkBtn}
              onPress={() => {
                setResetEmail(email.trim());
                setResetStep(1);
                setResetFeedback(null);
                setGuestScreen('forgot');
              }}
            >
              <Text style={styles.linkBtnText}>Esqueci minha senha</Text>
            </TouchableOpacity>
          </View>
        </ScrollView>
      </SafeAreaView>
    );
  }

  // 3. DETALHE DA PRESTAÇÃO DE CONTAS (e visualização do Item aberto com anexos)
  if (selectedDetail) {
    const openedItem =
      selectedItemNumber !== null
        ? selectedDetail.items.find((i) => i.item_number === selectedItemNumber) || null
        : null;

    const renderWhatsappModal = () =>
      isAdmin ? (
        <Modal
          visible={waModalOpen}
          transparent
          animationType="slide"
          onRequestClose={() => setWaModalOpen(false)}
        >
          <View style={styles.formModalBackdrop}>
            <View style={styles.formModalCard}>
              <View style={styles.formModalHeader}>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, flex: 1 }}>
                  <FontAwesome5 name="whatsapp" size={18} color="#16a34a" />
                  <Text style={styles.formModalTitle} numberOfLines={1}>
                    {waTargetItemNum !== null
                      ? `Cobrar Item ${String(waTargetItemNum).padStart(2, '0')}`
                      : 'Cobrar / Lembrar no WhatsApp'}
                  </Text>
                </View>
                <TouchableOpacity onPress={() => setWaModalOpen(false)}>
                  <FontAwesome5 name="times" size={18} color={COLORS.muted} />
                </TouchableOpacity>
              </View>

              <ScrollView keyboardShouldPersistTaps="handled">
                <Text style={styles.label}>Mensagem personalizada (opcional)</Text>
                <TextInput
                  style={[styles.input, { minHeight: 64, textAlignVertical: 'top', marginBottom: 12 }]}
                  value={waCustomMsg}
                  onChangeText={setWaCustomMsg}
                  multiline
                  placeholder="Deixe em branco para usar o texto automático de cobrança/lembrete."
                  placeholderTextColor="#94a3b8"
                />

                <Text style={styles.profileSectionTitle}>SELECIONE O USUÁRIO PARA COBRAR</Text>

                {(() => {
                  const userMap = new Map<
                    number,
                    { id: number; name: string; whatsapp: string; pendingItems: number[] }
                  >();
                  const relevantItems =
                    waTargetItemNum !== null
                      ? selectedDetail.items.filter((i) => i.item_number === waTargetItemNum)
                      : selectedDetail.items.filter((i) => i.status !== 'confirmado');

                  for (const it of relevantItems) {
                    for (const u of it.assigned_users || []) {
                      const cur = userMap.get(u.id) || {
                        id: u.id,
                        name: u.name,
                        whatsapp: u.whatsapp,
                        pendingItems: [],
                      };
                      if (!cur.pendingItems.includes(it.item_number)) {
                        cur.pendingItems.push(it.item_number);
                      }
                      userMap.set(u.id, cur);
                    }
                  }

                  if (userMap.size === 0) {
                    for (const u of usersList.filter((x) => x.role !== 'admin')) {
                      userMap.set(u.id, {
                        id: u.id,
                        name: u.name,
                        whatsapp: u.whatsapp || '',
                        pendingItems: [],
                      });
                    }
                  }

                  const targets = Array.from(userMap.values());
                  if (targets.length === 0) {
                    return (
                      <Text style={[styles.emptyText, { marginTop: 12, marginBottom: 12 }]}>
                        Nenhum usuário com WhatsApp disponível para cobrança.
                      </Text>
                    );
                  }

                  return targets.map((t) => {
                    const hasPhone = (t.whatsapp || '').replace(/\D/g, '').length >= 10;
                    const sending = waSendingUserId === t.id;
                    return (
                      <View key={t.id} style={styles.waUserCard}>
                        <Text style={styles.waUserName}>{t.name}</Text>
                        <Text style={styles.waUserSub}>
                          WhatsApp: {t.whatsapp || 'Não cadastrado'}
                        </Text>
                        {t.pendingItems.length > 0 && (
                          <Text style={styles.waUserItems}>
                            Itens: {t.pendingItems.map((n) => String(n).padStart(2, '0')).join(', ')}
                          </Text>
                        )}

                        <View style={{ flexDirection: 'row', gap: 8, marginTop: 10 }}>
                          <TouchableOpacity
                            style={[styles.waSendPhoneBtn, !hasPhone && { opacity: 0.5 }]}
                            onPress={() => handleSendWhatsappReminder(t, t.pendingItems, 'phone')}
                            disabled={sending}
                          >
                            <FontAwesome5 name="whatsapp" size={13} color="#ffffff" />
                            <Text style={styles.waSendBtnText}>WhatsApp (Celular)</Text>
                          </TouchableOpacity>

                          <TouchableOpacity
                            style={[
                              styles.waSendApiBtn,
                              (!hasPhone || sending) && { opacity: 0.6 },
                            ]}
                            onPress={() => handleSendWhatsappReminder(t, t.pendingItems, 'api')}
                            disabled={sending}
                          >
                            {sending ? (
                              <ActivityIndicator size="small" color={COLORS.shieldDark} />
                            ) : (
                              <>
                                <FontAwesome5 name="robot" size={12} color={COLORS.shieldDark} />
                                <Text style={styles.waSendApiText}>Via API</Text>
                              </>
                            )}
                          </TouchableOpacity>
                        </View>
                      </View>
                    );
                  });
                })()}
              </ScrollView>
            </View>
          </View>
        </Modal>
      ) : null;

    if (openedItem) {
      const st = STATUS_STYLE[openedItem.status] || STATUS_STYLE.pendente;
      const numPad = String(openedItem.item_number).padStart(2, '0');
      const canEditItem = isAdmin || openedItem.status !== 'confirmado';

      return (
        <SafeAreaView style={styles.container}>
          <StatusBar style="light" />
          <View style={styles.header}>
            <TouchableOpacity onPress={() => setSelectedItemNumber(null)} style={styles.backBtn}>
              <Text style={styles.backBtnText}>← Voltar para Itens</Text>
            </TouchableOpacity>
            <Text style={styles.headerTitle} numberOfLines={1}>
              Item {numPad}
            </Text>
            <Text style={styles.headerSub} numberOfLines={1}>
              {selectedDetail.title}
            </Text>
          </View>

          <ScrollView
            contentContainerStyle={{ padding: 16, paddingBottom: 36 }}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={() => openDetail(selectedDetail.id)} />
            }
          >
            <View style={styles.itemCard}>
              <View style={styles.itemTop}>
                <Text style={styles.itemNum}>Item {numPad}</Text>
                <View style={[styles.badge, { backgroundColor: st.bg }]}>
                  <Text style={[styles.badgeText, { color: st.fg }]}>{st.label}</Text>
                </View>
              </View>
              <Text style={[styles.itemTitle, { fontSize: 15, lineHeight: 21 }]}>{openedItem.title}</Text>
              {openedItem.rejection_reason ? (
                <Text style={styles.rejectText}>Motivo rejeição: {openedItem.rejection_reason}</Text>
              ) : null}

              {isAdmin && openedItem.status !== 'confirmado' && (
                <TouchableOpacity
                  style={styles.waActionBtn}
                  onPress={() => openWhatsappModal(openedItem.item_number)}
                >
                  <FontAwesome5 name="whatsapp" size={15} color="#ffffff" />
                  <Text style={styles.waActionBtnText}>Cobrar / Lembrar no WhatsApp</Text>
                </TouchableOpacity>
              )}
            </View>

            <Text style={styles.menuSectionLabel}>
              ANEXOS DO ITEM ({openedItem.attachments.length})
            </Text>

            {openedItem.attachments.length === 0 ? (
              <View style={styles.itemCard}>
                <Text style={[styles.emptyText, { marginTop: 8, marginBottom: 8 }]}>
                  Nenhum documento anexado neste item ainda.
                </Text>
              </View>
            ) : (
              openedItem.attachments.map((att) => {
                const ext = (att.file_ext || '').toLowerCase();
                const iconName =
                  ext === 'pdf'
                    ? 'file-pdf'
                    : ['jpg', 'jpeg', 'png', 'webp'].includes(ext)
                    ? 'file-image'
                    : ext === 'zip'
                    ? 'file-archive'
                    : 'file-alt';
                const iconColor =
                  ext === 'pdf'
                    ? '#dc2626'
                    : ['jpg', 'jpeg', 'png', 'webp'].includes(ext)
                    ? COLORS.shieldBlue
                    : '#b45309';

                return (
                  <View key={att.id} style={styles.attCard}>
                    <View style={styles.attCardTop}>
                      <View style={styles.attIconWrap}>
                        <FontAwesome5 name={iconName} size={18} color={iconColor} />
                      </View>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.attCardName} numberOfLines={2}>
                          {att.original_name}
                        </Text>
                        <Text style={styles.attCardMeta}>
                          {ext.toUpperCase()} • {formatFileSize(att.file_size)}
                        </Text>
                      </View>
                    </View>

                    <View style={styles.attActionRow}>
                      <TouchableOpacity
                        style={styles.attViewBtn}
                        onPress={() => handlePreviewAttachment(att)}
                      >
                        <FontAwesome5 name="eye" size={13} color="#ffffff" />
                        <Text style={styles.attViewBtnText}>Visualizar</Text>
                      </TouchableOpacity>

                      {canEditItem && (
                        <TouchableOpacity
                          style={[
                            styles.attDeleteBtn,
                            deletingAttId === att.id && { opacity: 0.6 },
                          ]}
                          onPress={() => handleDeleteAttachment(att.id)}
                          disabled={deletingAttId === att.id}
                        >
                          {deletingAttId === att.id ? (
                            <ActivityIndicator size="small" color="#b91c1c" />
                          ) : (
                            <>
                              <FontAwesome5 name="trash-alt" size={13} color="#b91c1c" />
                              <Text style={styles.attDeleteBtnText}>Excluir</Text>
                            </>
                          )}
                        </TouchableOpacity>
                      )}
                    </View>
                  </View>
                );
              })
            )}

            {canEditItem && (
              <View style={{ marginTop: 10 }}>
                <Text style={styles.menuSectionLabel}>ADICIONAR ANEXO</Text>
                <View style={{ flexDirection: 'row', gap: 8 }}>
                  <TouchableOpacity
                    style={[
                      styles.uploadBtn,
                      {
                        flex: 1,
                        marginTop: 0,
                        backgroundColor: COLORS.orange,
                        paddingVertical: 13,
                        flexDirection: 'row',
                        justifyContent: 'center',
                        alignItems: 'center',
                        gap: 7,
                      },
                    ]}
                    onPress={() => handleUploadPhoto(openedItem.item_number, 'camera')}
                    disabled={uploadingItem === openedItem.item_number}
                  >
                    <FontAwesome5 name="camera" size={14} color="#ffffff" />
                    <Text style={styles.uploadBtnText}>
                      {uploadingItem === openedItem.item_number ? 'Enviando...' : 'Câmera'}
                    </Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[
                      styles.uploadBtn,
                      {
                        flex: 1,
                        marginTop: 0,
                        backgroundColor: COLORS.shieldBlue,
                        paddingVertical: 13,
                        flexDirection: 'row',
                        justifyContent: 'center',
                        alignItems: 'center',
                        gap: 7,
                      },
                    ]}
                    onPress={() => handleUploadPhoto(openedItem.item_number, 'gallery')}
                    disabled={uploadingItem === openedItem.item_number}
                  >
                    <FontAwesome5 name="images" size={14} color="#ffffff" />
                    <Text style={styles.uploadBtnText}>Galeria</Text>
                  </TouchableOpacity>
                </View>

                <TouchableOpacity
                  style={[
                    styles.uploadBtn,
                    {
                      marginTop: 8,
                      paddingVertical: 11,
                      flexDirection: 'row',
                      justifyContent: 'center',
                      alignItems: 'center',
                      gap: 8,
                    },
                  ]}
                  onPress={() => handleUpload(openedItem.item_number)}
                  disabled={uploadingItem === openedItem.item_number}
                >
                  <FontAwesome5 name="file-upload" size={14} color="#ffffff" />
                  <Text style={styles.uploadBtnText}>Anexar Arquivo (PDF / ZIP)</Text>
                </TouchableOpacity>
              </View>
            )}
          </ScrollView>

          {/* Modal de visualização de imagem */}
          <Modal
            visible={previewImage !== null}
            transparent
            animationType="fade"
            onRequestClose={() => setPreviewImage(null)}
          >
            <View style={styles.previewModalBg}>
              <View style={styles.previewModalHeader}>
                <Text style={styles.previewModalTitle} numberOfLines={1}>
                  {previewImage?.name || 'Visualização'}
                </Text>
                <TouchableOpacity
                  style={styles.previewModalCloseBtn}
                  onPress={() => setPreviewImage(null)}
                >
                  <FontAwesome5 name="times" size={16} color="#ffffff" />
                  <Text style={styles.previewModalCloseText}>Fechar</Text>
                </TouchableOpacity>
              </View>
              {previewImage && (
                <Image
                  source={{ uri: previewImage.uri }}
                  style={styles.previewModalImage}
                  resizeMode="contain"
                />
              )}
            </View>
          </Modal>
          {renderWhatsappModal()}
        </SafeAreaView>
      );
    }

    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="light" />
        <View style={styles.header}>
          <TouchableOpacity
            onPress={() => {
              setSelectedItemNumber(null);
              setSelectedDetail(null);
            }}
            style={styles.backBtn}
          >
            <Text style={styles.backBtnText}>← Voltar</Text>
          </TouchableOpacity>
          <Text style={styles.headerTitle} numberOfLines={1}>
            {selectedDetail.title}
          </Text>
          <Text style={styles.headerSub}>Prazo: {selectedDetail.deadline}</Text>
        </View>

        <FlatList
          data={selectedDetail.items}
          keyExtractor={(item) => String(item.item_number)}
          contentContainerStyle={{ padding: 14, paddingBottom: 32 }}
          ListHeaderComponent={
            isAdmin ? (
              <TouchableOpacity
                style={[styles.waActionBtn, { marginTop: 0, marginBottom: 12 }]}
                onPress={() => openWhatsappModal(null)}
              >
                <FontAwesome5 name="whatsapp" size={16} color="#ffffff" />
                <Text style={styles.waActionBtnText}>Cobrar / Lembrar Usuário via WhatsApp</Text>
              </TouchableOpacity>
            ) : undefined
          }
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => openDetail(selectedDetail.id)} />
          }
          renderItem={({ item }) => {
            const st = STATUS_STYLE[item.status] || STATUS_STYLE.pendente;
            const numPad = String(item.item_number).padStart(2, '0');
            return (
              <TouchableOpacity
                style={styles.itemCard}
                activeOpacity={0.82}
                onPress={() => setSelectedItemNumber(item.item_number)}
              >
                <View style={styles.itemTop}>
                  <Text style={styles.itemNum}>Item {numPad}</Text>
                  <View style={[styles.badge, { backgroundColor: st.bg }]}>
                    <Text style={[styles.badgeText, { color: st.fg }]}>{st.label}</Text>
                  </View>
                </View>
                <Text style={styles.itemTitle}>{item.title}</Text>
                {item.rejection_reason ? (
                  <Text style={styles.rejectText}>Motivo rejeição: {item.rejection_reason}</Text>
                ) : null}

                <View style={styles.itemOpenRow}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                    <FontAwesome5 name="paperclip" size={12} color="#475569" />
                    <Text style={[styles.attCount, { marginTop: 0 }]}>
                      {item.attachments.length} arquivo(s) anexado(s)
                    </Text>
                  </View>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 5 }}>
                    <Text style={styles.itemOpenCue}>Abrir e ver anexos</Text>
                    <FontAwesome5 name="chevron-right" size={11} color={COLORS.shieldBlue} />
                  </View>
                </View>
              </TouchableOpacity>
            );
          }}
          ListFooterComponent={
            !isAdmin ? (
              <View style={styles.submitFooterBox}>
                {selectedDetail.can_submit ? (
                  <>
                    <Text style={styles.submitFooterTitle}>Todos os anexos foram adicionados!</Text>
                    <Text style={styles.submitFooterDesc}>
                      Revise os arquivos anexados e confirme o envio para encaminhar ao Administrador.
                    </Text>
                    <TouchableOpacity
                      style={[
                        styles.submitConfirmBtn,
                        submittingAcc && { opacity: 0.7 },
                      ]}
                      onPress={handleSubmitAccountability}
                      disabled={submittingAcc}
                    >
                      {submittingAcc ? (
                        <ActivityIndicator color="#ffffff" />
                      ) : (
                        <>
                          <FontAwesome5 name="paper-plane" size={15} color="#ffffff" />
                          <Text style={styles.submitConfirmBtnText}>Confirmar Envio da Prestação</Text>
                        </>
                      )}
                    </TouchableOpacity>
                  </>
                ) : (
                  <View style={styles.submitPendingBanner}>
                    <FontAwesome5 name="info-circle" size={15} color={COLORS.shieldBlue} />
                    <Text style={styles.submitPendingText}>
                      Adicione pelo menos 1 anexo em todos os seus itens (
                      {selectedDetail.items.filter((i) => i.attachments.length > 0).length}/
                      {selectedDetail.items.length}) para liberar o botão de confirmar envio.
                    </Text>
                  </View>
                )}
              </View>
            ) : undefined
          }
        />

        {/* Modal de Cobrança / Lembrete por WhatsApp (Admin) */}
        {renderWhatsappModal()}
      </SafeAreaView>
    );
  }

  const SCREEN_TITLES: Record<AppScreen, string> = {
    menu: 'Menu Principal',
    accountabilities: 'Prestações de Contas',
    spreadsheet: 'Planilha',
    annual_history: selectedHistoryYear ? `Histórico Anual — ${selectedHistoryYear}` : 'Histórico Anual',
    audit: 'Histórico de Auditoria',
    users: 'Usuários e Permissões',
    settings: 'Configurações',
    profile: 'Meu Perfil',
  };

  const isSettingsSection = screen === 'settings' || screen === 'users' || screen === 'profile';

  // 4. APP AUTENTICADO COM MENU MOBILE (substituindo a sidebar lateral por botões)
  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="light" />
      <View style={styles.header}>
        <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10 }}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10, flex: 1 }}>
            <BrandShieldIcon size={30} />
            <View style={{ flex: 1 }}>
              <Text style={styles.headerTitle} numberOfLines={1}>
                {SCREEN_TITLES[screen]}
              </Text>
              <Text style={styles.headerSub}>Olá, {user.name}</Text>
            </View>
          </View>
        </View>
      </View>

      <View style={{ flex: 1 }}>
        {/* TELA MENU MOBILE (somente Administrador) */}
        {isAdmin && screen === 'menu' && (
          <ScrollView
            contentContainerStyle={styles.menuScroll}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={() => loadScreenData(token, 'menu')} />
            }
          >
            <Text style={styles.menuSectionLabel}>MENU PRINCIPAL</Text>

            <TouchableOpacity
              style={[styles.menuBtn, styles.menuBtnHighlight]}
              onPress={() => navigateTo('accountabilities')}
            >
              <View style={styles.menuBtnLeft}>
                <View style={[styles.menuIconWrap, { backgroundColor: 'rgba(255,255,255,0.2)' }]}>
                  <FontAwesome5 name="file-invoice" size={17} color="#ffffff" />
                </View>
                <View>
                  <Text style={[styles.menuBtnTitle, { color: '#ffffff' }]}>Prestações de Contas</Text>
                  <Text style={[styles.menuBtnSub, { color: '#ffedd5' }]}>
                    Checklist ativo, prazos e envio de anexos
                  </Text>
                </View>
              </View>
              <FontAwesome5 name="chevron-right" size={14} color="#ffffff" />
            </TouchableOpacity>

            <TouchableOpacity style={styles.menuBtn} onPress={() => navigateTo('spreadsheet')}>
              <View style={styles.menuBtnLeft}>
                <View style={styles.menuIconWrap}>
                  <FontAwesome5 name="table" size={16} color={COLORS.shieldBlue} />
                </View>
                <View>
                  <Text style={styles.menuBtnTitle}>Planilha</Text>
                  <Text style={styles.menuBtnSub}>Receitas, aportes, despesas e saldo</Text>
                </View>
              </View>
              <FontAwesome5 name="chevron-right" size={14} color={COLORS.muted} />
            </TouchableOpacity>

            {/* Histórico Anual com abre/fecha dos anos */}
            <TouchableOpacity
              style={styles.menuBtn}
              onPress={() => setAnnualMenuExpanded((prev) => !prev)}
            >
              <View style={styles.menuBtnLeft}>
                <View style={styles.menuIconWrap}>
                  <FontAwesome5 name="calendar-alt" size={16} color={COLORS.shieldBlue} />
                </View>
                <View>
                  <Text style={styles.menuBtnTitle}>Histórico Anual</Text>
                  <Text style={styles.menuBtnSub}>Prestações finalizadas por ano</Text>
                </View>
              </View>
              <FontAwesome5
                name={annualMenuExpanded ? 'chevron-up' : 'chevron-down'}
                size={14}
                color={COLORS.muted}
              />
            </TouchableOpacity>

            {annualMenuExpanded && (
              <View style={styles.subMenuBox}>
                <TouchableOpacity
                  style={styles.subMenuBtn}
                  onPress={() => navigateTo('annual_history', null)}
                >
                  <Text style={styles.subMenuText}>Todos os Anos</Text>
                  <Text style={styles.subMenuCount}>{Object.keys(historyYears).length}</Text>
                </TouchableOpacity>
                {Object.entries(historyYears).map(([yr, list]) => (
                  <TouchableOpacity
                    key={yr}
                    style={styles.subMenuBtn}
                    onPress={() => navigateTo('annual_history', yr)}
                  >
                    <Text style={styles.subMenuText}>{yr}</Text>
                    <Text style={styles.subMenuCount}>{list.length}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            )}

            {isAdmin && (
              <TouchableOpacity style={styles.menuBtn} onPress={() => navigateTo('audit')}>
                <View style={styles.menuBtnLeft}>
                  <View style={styles.menuIconWrap}>
                    <FontAwesome5 name="history" size={16} color={COLORS.shieldBlue} />
                  </View>
                  <View>
                    <Text style={styles.menuBtnTitle}>Histórico de Auditoria</Text>
                    <Text style={styles.menuBtnSub}>Registro de ações e movimentações</Text>
                  </View>
                </View>
                <FontAwesome5 name="chevron-right" size={14} color={COLORS.muted} />
              </TouchableOpacity>
            )}
          </ScrollView>
        )}

        {/* PRESTAÇÕES DE CONTAS */}
        {screen === 'accountabilities' && (
          <>
            <FlatList
              data={accountabilities}
              keyExtractor={(item) => String(item.id)}
              contentContainerStyle={{ padding: 14 }}
              ListHeaderComponent={
                isAdmin ? (
                  <View style={styles.listActionHeader}>
                    <Text style={[styles.menuSectionLabel, { marginBottom: 0 }]}>
                      PRESTAÇÕES EM ABERTO ({accountabilities.length})
                    </Text>
                    <TouchableOpacity
                      style={styles.newAccBtn}
                      onPress={() => {
                        setNewAccTitle('');
                        setNewAccDesc('');
                        setNewAccDeadline('');
                        setNewAccEnabledItems(ALL_CHECKLIST_NUMS);
                        setNewAccModalOpen(true);
                      }}
                    >
                      <FontAwesome5 name="plus" size={12} color="#ffffff" />
                      <Text style={styles.newAccBtnText}>Novo</Text>
                    </TouchableOpacity>
                  </View>
                ) : undefined
              }
              refreshControl={
                <RefreshControl
                  refreshing={refreshing}
                  onRefresh={() => loadScreenData(token, 'accountabilities')}
                />
              }
              ListEmptyComponent={
                <Text style={styles.emptyText}>Nenhuma prestação em andamento encontrada.</Text>
              }
              renderItem={({ item }) => (
                <TouchableOpacity style={styles.accCard} onPress={() => openDetail(item.id)}>
                  <View style={styles.itemTop}>
                    <Text style={styles.accId}>#{item.id}</Text>
                    <Text style={styles.accDeadline}>Prazo: {item.deadline}</Text>
                  </View>
                  <Text style={styles.accTitle}>{item.title}</Text>
                  <View style={styles.progressTrack}>
                    <View style={[styles.progressFill, { width: `${item.progress}%` }]} />
                  </View>
                  <Text style={styles.accMeta}>
                    {item.progress}% • {item.filled_items}/{item.total_items} itens • {item.attachments_count}{' '}
                    anexo(s)
                  </Text>
                </TouchableOpacity>
              )}
            />

            {/* Modal Nova Prestação de Contas */}
            <Modal
              visible={newAccModalOpen}
              transparent
              animationType="slide"
              onRequestClose={() => setNewAccModalOpen(false)}
            >
              <View style={styles.formModalBackdrop}>
                <View style={styles.formModalCard}>
                  <View style={styles.formModalHeader}>
                    <Text style={styles.formModalTitle}>Nova Prestação de Contas</Text>
                    <TouchableOpacity onPress={() => setNewAccModalOpen(false)}>
                      <FontAwesome5 name="times" size={18} color={COLORS.muted} />
                    </TouchableOpacity>
                  </View>

                  <ScrollView keyboardShouldPersistTaps="handled">
                    <Text style={styles.label}>Título *</Text>
                    <TextInput
                      style={styles.input}
                      value={newAccTitle}
                      onChangeText={setNewAccTitle}
                      placeholder="Ex: Prestação de Contas 2026 - 1º Trimestre"
                      placeholderTextColor="#94a3b8"
                    />

                    <Text style={[styles.label, { marginTop: 12 }]}>Descrição</Text>
                    <TextInput
                      style={[styles.input, { minHeight: 74, textAlignVertical: 'top' }]}
                      value={newAccDesc}
                      onChangeText={setNewAccDesc}
                      multiline
                      placeholder="Observações ou detalhes desta prestação (opcional)"
                      placeholderTextColor="#94a3b8"
                    />

                    <Text style={[styles.label, { marginTop: 12 }]}>Prazo limite (DD/MM/AAAA)</Text>
                    <TextInput
                      style={styles.input}
                      value={newAccDeadline}
                      onChangeText={(txt) => setNewAccDeadline(formatDateMask(txt))}
                      keyboardType="number-pad"
                      maxLength={10}
                      placeholder="DD/MM/AAAA"
                      placeholderTextColor="#94a3b8"
                    />
                    <View style={{ flexDirection: 'row', gap: 6, marginTop: 7, flexWrap: 'wrap' }}>
                      <TouchableOpacity
                        style={styles.permQuickBtn}
                        onPress={() => setNewAccDeadline(formatDateFromOffset(15))}
                      >
                        <Text style={styles.permQuickBtnText}>+15 dias</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={styles.permQuickBtn}
                        onPress={() => setNewAccDeadline(formatDateFromOffset(30))}
                      >
                        <Text style={styles.permQuickBtnText}>+30 dias</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={styles.permQuickBtn}
                        onPress={() => setNewAccDeadline(formatEndOfMonth())}
                      >
                        <Text style={styles.permQuickBtnText}>Fim do mês</Text>
                      </TouchableOpacity>
                      {newAccDeadline !== '' && (
                        <TouchableOpacity
                          style={styles.permQuickBtn}
                          onPress={() => setNewAccDeadline('')}
                        >
                          <Text style={[styles.permQuickBtnText, { color: '#b91c1c' }]}>Limpar</Text>
                        </TouchableOpacity>
                      )}
                    </View>

                    <View style={styles.profileDivider} />

                    <Text style={styles.profileSectionTitle}>
                      CAMPOS / ITENS QUE SERÃO USADOS ({newAccEnabledItems.length}/{ALL_CHECKLIST_ITEMS.length})
                    </Text>

                    <View style={{ flexDirection: 'row', gap: 8, marginBottom: 10 }}>
                      <TouchableOpacity
                        style={styles.permQuickBtn}
                        onPress={() => setNewAccEnabledItems(ALL_CHECKLIST_NUMS)}
                      >
                        <Text style={styles.permQuickBtnText}>Marcar Todos</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={styles.permQuickBtn}
                        onPress={() => setNewAccEnabledItems([])}
                      >
                        <Text style={styles.permQuickBtnText}>Desmarcar Todos</Text>
                      </TouchableOpacity>
                    </View>

                    {ALL_CHECKLIST_ITEMS.map((it) => {
                      const checked = newAccEnabledItems.includes(it.item_number);
                      const numPad = String(it.item_number).padStart(2, '0');
                      return (
                        <TouchableOpacity
                          key={it.item_number}
                          style={[styles.permCheckRow, checked && styles.permCheckRowActive]}
                          onPress={() => toggleNewAccItem(it.item_number)}
                        >
                          <FontAwesome5
                            name={checked ? 'check-square' : 'square'}
                            size={16}
                            color={checked ? COLORS.orange : '#94a3b8'}
                            solid={checked}
                          />
                          <Text style={styles.permCheckText}>
                            <Text style={{ fontWeight: '800', color: COLORS.shieldDark }}>
                              Item {numPad}:
                            </Text>{' '}
                            {it.title}
                          </Text>
                        </TouchableOpacity>
                      );
                    })}

                    <View style={styles.formModalActions}>
                      <TouchableOpacity
                        style={styles.formModalCancelBtn}
                        onPress={() => setNewAccModalOpen(false)}
                        disabled={newAccSaving}
                      >
                        <Text style={styles.formModalCancelText}>Cancelar</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={[styles.formModalSaveBtn, newAccSaving && { opacity: 0.7 }]}
                        onPress={handleCreateAccountability}
                        disabled={newAccSaving}
                      >
                        {newAccSaving ? (
                          <ActivityIndicator color="#ffffff" />
                        ) : (
                          <Text style={styles.formModalSaveText}>Criar Prestação</Text>
                        )}
                      </TouchableOpacity>
                    </View>
                  </ScrollView>
                </View>
              </View>
            </Modal>
          </>
        )}

        {/* PLANILHA (Lista Resumida com Anexos - somente Admin) */}
        {isAdmin && screen === 'spreadsheet' && (
          <FlatList
            data={spreadsheetRows}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={{ padding: 14 }}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={() => loadScreenData(token, 'spreadsheet')} />
            }
            ListEmptyComponent={
              <Text style={styles.emptyText}>
                Nenhuma prestação com documentos anexados encontrada para a planilha.
              </Text>
            }
            renderItem={({ item }) => (
              <TouchableOpacity style={styles.itemCard} onPress={() => openDetail(item.id)}>
                <View style={styles.itemTop}>
                  <Text style={styles.accId}>#{item.id}</Text>
                  <Text style={styles.accDeadline}>
                    {item.attachments_count} anexo(s) • {item.entries_count} lanç.
                  </Text>
                </View>
                <Text style={styles.accTitle}>{item.title}</Text>
                <View style={styles.financeRow}>
                  <Text style={[styles.financePill, { color: '#15803d' }]}>
                    Entradas: {formatBRL(item.receitas + item.aportes)}
                  </Text>
                  <Text style={[styles.financePill, { color: '#b91c1c' }]}>
                    Despesas: {formatBRL(item.despesas)}
                  </Text>
                </View>
                <Text style={[styles.financeSaldo, { color: item.saldo < 0 ? '#b91c1c' : COLORS.shieldDark }]}>
                  Saldo: {formatBRL(item.saldo)}
                </Text>
              </TouchableOpacity>
            )}
          />
        )}

        {/* HISTÓRICO ANUAL (somente Admin) */}
        {isAdmin && screen === 'annual_history' && (
          <ScrollView
            contentContainerStyle={{ padding: 14 }}
            refreshControl={
              <RefreshControl
                refreshing={refreshing}
                onRefresh={() => loadScreenData(token, 'annual_history')}
              />
            }
          >
            {Object.keys(historyYears).length === 0 ? (
              <Text style={styles.emptyText}>Nenhuma prestação finalizada no histórico anual.</Text>
            ) : (
              Object.entries(historyYears)
                .filter(([yr]) => !selectedHistoryYear || yr === selectedHistoryYear)
                .map(([year, list]) => (
                  <View key={year} style={styles.itemCard}>
                    <Text style={styles.yearTitle}>
                      Exercício {year} ({list.length})
                    </Text>
                    {list.map((acc) => (
                      <TouchableOpacity
                        key={acc.id}
                        style={styles.historyRow}
                        onPress={() => openDetail(acc.id)}
                      >
                        <Text style={styles.historyRowTitle}>• {acc.title}</Text>
                        <Text style={styles.historyRowSub}>Prazo: {acc.deadline} • Finalizada</Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                ))
            )}
          </ScrollView>
        )}

        {/* HISTÓRICO DE AUDITORIA (somente Admin) */}
        {isAdmin && screen === 'audit' && (
          <FlatList
            data={auditLogs}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={{ padding: 14 }}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={() => loadScreenData(token, 'audit')} />
            }
            ListEmptyComponent={<Text style={styles.emptyText}>Nenhum registro de auditoria.</Text>}
            renderItem={({ item }) => (
              <View style={styles.itemCard}>
                <View style={styles.itemTop}>
                  <Text style={styles.itemNum}>{item.user_name}</Text>
                  <Text style={styles.accDeadline}>{item.created_at}</Text>
                </View>
                <Text style={styles.itemTitle}>{item.action}</Text>
              </View>
            )}
          />
        )}

        {/* USUÁRIOS E PERMISSÕES (somente Admin) */}
        {isAdmin && screen === 'users' && editingUser && (
          <ScrollView contentContainerStyle={styles.profileScroll} keyboardShouldPersistTaps="handled">
            <TouchableOpacity
              style={[styles.topBarBackBtn, { alignSelf: 'flex-start', marginBottom: 16 }]}
              onPress={() => setEditingUser(null)}
            >
              <Text style={styles.topBarBackText}>← Voltar para Usuários</Text>
            </TouchableOpacity>

            <Text style={styles.profileSectionTitle}>EDITAR USUÁRIO #{editingUser.id}</Text>

            <Text style={styles.label}>Nome completo</Text>
            <TextInput
              style={styles.input}
              value={editUserName}
              onChangeText={setEditUserName}
              placeholder="Nome completo"
              placeholderTextColor="#94a3b8"
            />

            <Text style={[styles.label, { marginTop: 12 }]}>E-mail</Text>
            <TextInput
              style={styles.input}
              value={editUserEmail}
              onChangeText={setEditUserEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
              placeholder="usuario@email.com"
              placeholderTextColor="#94a3b8"
            />

            <Text style={[styles.label, { marginTop: 12 }]}>WhatsApp</Text>
            <TextInput
              style={styles.input}
              value={editUserWhatsapp}
              onChangeText={setEditUserWhatsapp}
              keyboardType="phone-pad"
              placeholder="Ex: (49) 99999-9999"
              placeholderTextColor="#94a3b8"
            />

            <Text style={[styles.label, { marginTop: 12 }]}>Nova senha (opcional)</Text>
            <TextInput
              style={styles.input}
              value={editUserPassword}
              onChangeText={setEditUserPassword}
              secureTextEntry
              placeholder="Deixe em branco para manter a atual"
              placeholderTextColor="#94a3b8"
            />

            <Text style={[styles.label, { marginTop: 14 }]}>Perfil de acesso</Text>
            <View style={{ flexDirection: 'row', gap: 10 }}>
              <TouchableOpacity
                style={[
                  styles.roleToggleBtn,
                  editUserRole === 'user' && styles.roleToggleBtnActive,
                ]}
                onPress={() => setEditUserRole('user')}
              >
                <FontAwesome5
                  name="user"
                  size={13}
                  color={editUserRole === 'user' ? '#ffffff' : COLORS.muted}
                />
                <Text
                  style={[
                    styles.roleToggleText,
                    editUserRole === 'user' && styles.roleToggleTextActive,
                  ]}
                >
                  Usuário Padrão
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.roleToggleBtn,
                  editUserRole === 'admin' && styles.roleToggleBtnActiveAdmin,
                ]}
                onPress={() => setEditUserRole('admin')}
              >
                <FontAwesome5
                  name="user-shield"
                  size={13}
                  color={editUserRole === 'admin' ? '#ffffff' : COLORS.muted}
                />
                <Text
                  style={[
                    styles.roleToggleText,
                    editUserRole === 'admin' && styles.roleToggleTextActive,
                  ]}
                >
                  Administrador
                </Text>
              </TouchableOpacity>
            </View>

            <View style={styles.profileDivider} />

            <Text style={styles.profileSectionTitle}>
              PERMISSÕES DE ITENS ({editUserAllowedItems.length}/{editingUser.all_items.length})
            </Text>

            {editUserRole === 'admin' ? (
              <View style={styles.submitPendingBanner}>
                <FontAwesome5 name="shield-alt" size={15} color={COLORS.shieldBlue} />
                <Text style={styles.submitPendingText}>
                  Administradores têm acesso automático a todos os 24 itens do checklist.
                </Text>
              </View>
            ) : (
              <>
                <View style={{ flexDirection: 'row', gap: 8, marginBottom: 12 }}>
                  <TouchableOpacity
                    style={styles.permQuickBtn}
                    onPress={() =>
                      setEditUserAllowedItems(editingUser.all_items.map((i) => i.item_number))
                    }
                  >
                    <Text style={styles.permQuickBtnText}>Marcar Todos</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={styles.permQuickBtn}
                    onPress={() => setEditUserAllowedItems([])}
                  >
                    <Text style={styles.permQuickBtnText}>Desmarcar Todos</Text>
                  </TouchableOpacity>
                </View>

                {editingUser.all_items.map((it) => {
                  const checked = editUserAllowedItems.includes(it.item_number);
                  const numPad = String(it.item_number).padStart(2, '0');
                  return (
                    <TouchableOpacity
                      key={it.item_number}
                      style={[styles.permCheckRow, checked && styles.permCheckRowActive]}
                      onPress={() => toggleUserPermissionItem(it.item_number)}
                    >
                      <FontAwesome5
                        name={checked ? 'check-square' : 'square'}
                        size={17}
                        color={checked ? COLORS.orange : '#94a3b8'}
                        solid={checked}
                      />
                      <Text style={styles.permCheckText}>
                        <Text style={{ fontWeight: '800', color: COLORS.shieldDark }}>
                          Item {numPad}:
                        </Text>{' '}
                        {it.title}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </>
            )}

            <TouchableOpacity
              style={[styles.primaryBtn, { marginTop: 22 }, editUserSaving && { opacity: 0.7 }]}
              onPress={handleSaveEditUser}
              disabled={editUserSaving}
            >
              {editUserSaving ? (
                <ActivityIndicator color="#ffffff" />
              ) : (
                <Text style={styles.primaryBtnText}>Salvar Usuário e Permissões</Text>
              )}
            </TouchableOpacity>
          </ScrollView>
        )}

        {isAdmin && screen === 'users' && !editingUser && (
          <FlatList
            data={usersList}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={{ padding: 14 }}
            ListHeaderComponent={
              <TouchableOpacity
                style={[styles.topBarBackBtn, { alignSelf: 'flex-start', marginBottom: 12 }]}
                onPress={() => navigateTo('settings')}
              >
                <Text style={styles.topBarBackText}>← Voltar para Configurações</Text>
              </TouchableOpacity>
            }
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={() => loadScreenData(token, 'users')} />
            }
            renderItem={({ item }) => (
              <TouchableOpacity
                style={styles.itemCard}
                activeOpacity={0.82}
                onPress={() => openEditUser(item.id)}
              >
                <View style={styles.itemTop}>
                  <Text style={styles.yearTitle}>{item.name}</Text>
                  <View
                    style={[
                      styles.badge,
                      { backgroundColor: item.role === 'admin' ? COLORS.orangeSoft : COLORS.bgSoft },
                    ]}
                  >
                    <Text
                      style={[
                        styles.badgeText,
                        { color: item.role === 'admin' ? COLORS.orangeText : COLORS.muted },
                      ]}
                    >
                      {item.role === 'admin' ? 'Admin' : 'Usuário'}
                    </Text>
                  </View>
                </View>
                <Text style={styles.attName}>E-mail: {item.email}</Text>
                <Text style={styles.attName}>WhatsApp: {item.whatsapp || 'Não informado'}</Text>
                <View style={styles.itemOpenRow}>
                  <Text style={styles.itemOpenCue}>Editar usuário e permissões</Text>
                  <FontAwesome5 name="chevron-right" size={11} color={COLORS.shieldBlue} />
                </View>
              </TouchableOpacity>
            )}
          />
        )}

        {/* CONFIGURAÇÕES (Hub Admin) */}
        {isAdmin && screen === 'settings' && (
          <ScrollView
            contentContainerStyle={styles.menuScroll}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={() => loadScreenData(token, 'settings')} />
            }
          >
            <Text style={styles.menuSectionLabel}>OPÇÕES DE CONFIGURAÇÃO</Text>

            <TouchableOpacity style={styles.menuBtn} onPress={() => navigateTo('profile')}>
              <View style={styles.menuBtnLeft}>
                <View style={styles.menuIconWrap}>
                  <FontAwesome5 name="user-circle" size={17} color={COLORS.shieldBlue} />
                </View>
                <View>
                  <Text style={styles.menuBtnTitle}>Meu Perfil</Text>
                  <Text style={styles.menuBtnSub}>Dados da sua conta, e-mail e WhatsApp</Text>
                </View>
              </View>
              <FontAwesome5 name="chevron-right" size={14} color={COLORS.muted} />
            </TouchableOpacity>

            {isAdmin && (
              <TouchableOpacity style={styles.menuBtn} onPress={() => navigateTo('users')}>
                <View style={styles.menuBtnLeft}>
                  <View style={styles.menuIconWrap}>
                    <FontAwesome5 name="users-cog" size={16} color={COLORS.shieldBlue} />
                  </View>
                  <View>
                    <Text style={styles.menuBtnTitle}>Usuários e Permissões</Text>
                    <Text style={styles.menuBtnSub}>Gerenciar contas cadastradas e perfis</Text>
                  </View>
                </View>
                <FontAwesome5 name="chevron-right" size={14} color={COLORS.muted} />
              </TouchableOpacity>
            )}
          </ScrollView>
        )}

        {/* PERFIL (Detalhado em inputs, sem card, com foto e alterar senha) */}
        {screen === 'profile' && (
          <ScrollView contentContainerStyle={styles.profileScroll} keyboardShouldPersistTaps="handled">
            {isAdmin && (
              <TouchableOpacity
                style={[styles.topBarBackBtn, { alignSelf: 'flex-start', marginBottom: 16 }]}
                onPress={() => navigateTo('settings')}
              >
                <Text style={styles.topBarBackText}>← Voltar para Configurações</Text>
              </TouchableOpacity>
            )}

            {profileFeedback && (
              <View
                style={[
                  styles.feedbackBox,
                  {
                    backgroundColor: profileFeedback.type === 'ok' ? '#dcfce7' : '#fee2e2',
                    borderColor: profileFeedback.type === 'ok' ? '#86efac' : '#fca5a5',
                  },
                ]}
              >
                <Text
                  style={{
                    color: profileFeedback.type === 'ok' ? '#166534' : '#991b1b',
                    fontSize: 13,
                    fontWeight: '600',
                  }}
                >
                  {profileFeedback.text}
                </Text>
              </View>
            )}

            {/* FOTO DE PERFIL */}
            <Text style={styles.profileSectionTitle}>FOTO DE PERFIL</Text>
            <View style={styles.profilePhotoRow}>
              <View style={styles.profileAvatarWrap}>
                {profileAvatarPick?.uri ? (
                  <Image source={{ uri: profileAvatarPick.uri }} style={styles.profileAvatarImg} />
                ) : user.avatar_url && !avatarImgError ? (
                  <Image
                    source={{ uri: `${apiUrl.replace(/\/+$/, '')}${user.avatar_url}` }}
                    style={styles.profileAvatarImg}
                    onError={() => setAvatarImgError(true)}
                  />
                ) : (
                  <FontAwesome5 name="user" size={32} color={COLORS.shieldBlue} />
                )}
              </View>
              <View style={{ flex: 1 }}>
                <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap' }}>
                  <TouchableOpacity
                    style={[styles.profilePhotoBtn, { backgroundColor: COLORS.orange }]}
                    onPress={() => handlePickProfilePhoto('camera')}
                    disabled={profileSaving}
                  >
                    <FontAwesome5 name="camera" size={13} color="#ffffff" />
                    <Text style={styles.profilePhotoBtnText}>Câmera</Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={styles.profilePhotoBtn}
                    onPress={() => handlePickProfilePhoto('gallery')}
                    disabled={profileSaving}
                  >
                    <FontAwesome5 name="images" size={13} color="#ffffff" />
                    <Text style={styles.profilePhotoBtnText}>Galeria</Text>
                  </TouchableOpacity>

                  {(profileAvatarPick || user.avatar_url) && (
                    <TouchableOpacity
                      style={styles.profileRemoveBtn}
                      onPress={handleRemoveProfilePhoto}
                      disabled={profileSaving}
                    >
                      <FontAwesome5 name="trash-alt" size={13} color="#b91c1c" />
                      <Text style={styles.profileRemoveBtnText}>Remover</Text>
                    </TouchableOpacity>
                  )}
                </View>
                <Text style={styles.hint}>
                  {profileAvatarPick
                    ? `Selecionada: ${profileAvatarPick.name}`
                    : 'Formatos JPG, PNG ou WEBP (máx. 5 MB)'}
                </Text>
              </View>
            </View>

            <View style={styles.profileDivider} />

            {/* DADOS DA CONTA */}
            <Text style={styles.profileSectionTitle}>DADOS DA CONTA</Text>

            <Text style={styles.label}>Nome completo</Text>
            <TextInput
              style={styles.input}
              value={profileName}
              onChangeText={setProfileName}
              placeholder="Seu nome completo"
              placeholderTextColor="#94a3b8"
            />

            <Text style={[styles.label, { marginTop: 14 }]}>E-mail</Text>
            <TextInput
              style={styles.input}
              value={profileEmail}
              onChangeText={setProfileEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
              placeholder="seu@email.com"
              placeholderTextColor="#94a3b8"
            />

            <Text style={[styles.label, { marginTop: 14 }]}>WhatsApp</Text>
            <TextInput
              style={styles.input}
              value={profileWhatsapp}
              onChangeText={setProfileWhatsapp}
              keyboardType="phone-pad"
              placeholder="Ex: (49) 99999-9999"
              placeholderTextColor="#94a3b8"
            />

            <Text style={[styles.label, { marginTop: 14 }]}>Perfil de acesso</Text>
            <TextInput
              style={[styles.input, styles.inputReadonly]}
              value={user.role === 'admin' ? 'Administrador' : 'Usuário Padrão'}
              editable={false}
            />

            <View style={styles.profileDivider} />

            {/* ALTERAR SENHA */}
            <Text style={styles.profileSectionTitle}>ALTERAR SENHA</Text>
            <Text style={[styles.hint, { marginTop: 0, marginBottom: 12 }]}>
              Deixe os campos abaixo em branco se não quiser alterar sua senha atual.
            </Text>

            <Text style={styles.label}>Nova senha</Text>
            <TextInput
              style={styles.input}
              value={profileNewPassword}
              onChangeText={setProfileNewPassword}
              secureTextEntry
              placeholder="Mínimo 6 caracteres"
              placeholderTextColor="#94a3b8"
            />

            <Text style={[styles.label, { marginTop: 14 }]}>Confirmar nova senha</Text>
            <TextInput
              style={styles.input}
              value={profileConfirmPassword}
              onChangeText={setProfileConfirmPassword}
              secureTextEntry
              placeholder="Repita a nova senha"
              placeholderTextColor="#94a3b8"
            />

            <TouchableOpacity
              style={[styles.primaryBtn, { marginTop: 22 }, profileSaving && { opacity: 0.7 }]}
              onPress={handleSaveProfile}
              disabled={profileSaving}
            >
              {profileSaving ? (
                <ActivityIndicator color="#ffffff" />
              ) : (
                <Text style={styles.primaryBtnText}>Salvar Alterações</Text>
              )}
            </TouchableOpacity>
          </ScrollView>
        )}
      </View>

      {/* RODAPÉ FIXO COM ÍCONES FONT AWESOME */}
      <View style={styles.bottomNav}>
        {isAdmin && (
          <TouchableOpacity
            style={[
              styles.bottomNavItem,
              (screen === 'menu' ||
                screen === 'spreadsheet' ||
                screen === 'annual_history' ||
                screen === 'audit') &&
                styles.bottomNavItemActive,
            ]}
            onPress={() => navigateTo('menu')}
          >
            <FontAwesome5
              name="bars"
              size={18}
              color={
                screen === 'menu' ||
                screen === 'spreadsheet' ||
                screen === 'annual_history' ||
                screen === 'audit'
                  ? COLORS.orange
                  : COLORS.muted
              }
              style={{ marginBottom: 4 }}
            />
            <Text
              style={[
                styles.bottomNavLabel,
                (screen === 'menu' ||
                  screen === 'spreadsheet' ||
                  screen === 'annual_history' ||
                  screen === 'audit') &&
                  styles.bottomNavLabelActive,
              ]}
            >
              Menu
            </Text>
          </TouchableOpacity>
        )}

        <TouchableOpacity
          style={[
            styles.bottomNavItem,
            screen === 'accountabilities' && styles.bottomNavItemActive,
          ]}
          onPress={() => navigateTo('accountabilities')}
        >
          <FontAwesome5
            name="file-alt"
            size={18}
            color={screen === 'accountabilities' ? COLORS.orange : COLORS.muted}
            style={{ marginBottom: 4 }}
          />
          <Text
            style={[
              styles.bottomNavLabel,
              screen === 'accountabilities' && styles.bottomNavLabelActive,
            ]}
          >
            Prestações
          </Text>
        </TouchableOpacity>

        {isAdmin ? (
          <TouchableOpacity
            style={[styles.bottomNavItem, isSettingsSection && styles.bottomNavItemActive]}
            onPress={() => navigateTo('settings')}
          >
            <FontAwesome5
              name="cog"
              size={18}
              color={isSettingsSection ? COLORS.orange : COLORS.muted}
              style={{ marginBottom: 4 }}
            />
            <Text style={[styles.bottomNavLabel, isSettingsSection && styles.bottomNavLabelActive]}>
              Configurações
            </Text>
          </TouchableOpacity>
        ) : (
          <TouchableOpacity
            style={[styles.bottomNavItem, screen === 'profile' && styles.bottomNavItemActive]}
            onPress={() => navigateTo('profile')}
          >
            <FontAwesome5
              name="user-circle"
              size={18}
              color={screen === 'profile' ? COLORS.orange : COLORS.muted}
              style={{ marginBottom: 4 }}
            />
            <Text style={[styles.bottomNavLabel, screen === 'profile' && styles.bottomNavLabelActive]}>
              Meu Perfil
            </Text>
          </TouchableOpacity>
        )}

        <TouchableOpacity style={styles.bottomNavItem} onPress={handleLogout}>
          <FontAwesome5
            name="sign-out-alt"
            size={18}
            color="#b91c1c"
            style={{ marginBottom: 4 }}
          />
          <Text style={[styles.bottomNavLabel, { color: '#b91c1c' }]}>Sair</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const NAV_TOP_PADDING =
  Platform.OS === 'android' ? (RNStatusBar.currentHeight || 32) + 14 : Platform.OS === 'ios' ? 16 : 14;

const styles = StyleSheet.create({
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: COLORS.bgSoft },
  container: { flex: 1, backgroundColor: COLORS.bgSoft },
  guestRoot: { flex: 1, backgroundColor: COLORS.bgSoft },

  /* Top Bar estilo Desktop */
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: NAV_TOP_PADDING,
    paddingBottom: 14,
    backgroundColor: COLORS.card,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  topBarBrand: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  topBarTitle: { fontSize: 15, fontWeight: '800', color: COLORS.shieldDark },
  topBarBtn: {
    backgroundColor: COLORS.orange,
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 7,
  },
  topBarBtnText: { color: '#ffffff', fontWeight: '700', fontSize: 13 },
  topBarBackBtn: {
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 7,
    backgroundColor: COLORS.bg,
  },
  topBarBackText: { color: COLORS.navy, fontWeight: '700', fontSize: 13 },

  /* Escudo institucional */
  shieldIconBox: {
    backgroundColor: COLORS.shieldDark,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: COLORS.shieldBlue,
    position: 'relative',
  },
  shieldCheckDot: {
    position: 'absolute',
    right: -3,
    bottom: -3,
    backgroundColor: COLORS.orangeBright,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: '#ffffff',
  },
  logoFallbackRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  logoFallbackSmall: { fontSize: 12, color: COLORS.shieldDark, fontWeight: '500' },
  logoFallbackMain: { fontSize: 18, color: COLORS.shieldDark, fontWeight: '800' },
  logoFallbackLine: { height: 2.5, backgroundColor: COLORS.orangeBright, marginVertical: 4, borderRadius: 2 },
  logoFallbackSub: { fontSize: 10, color: COLORS.muted, fontWeight: '600', letterSpacing: 0.4 },

  /* Home Demonstrativa (Clean) */
  cleanHomeRoot: {
    flex: 1,
    backgroundColor: '#ffffff',
    paddingHorizontal: 28,
    paddingTop: NAV_TOP_PADDING,
    paddingBottom: 24,
    justifyContent: 'space-between',
  },
  cleanHomeCenter: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    width: '100%',
    maxWidth: 380,
    alignSelf: 'center',
  },
  cleanAccentLine: {
    width: 44,
    height: 3,
    borderRadius: 99,
    backgroundColor: COLORS.orange,
    marginBottom: 18,
  },
  cleanHomeTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: COLORS.shieldDark,
    textAlign: 'center',
    letterSpacing: -0.3,
  },
  cleanHomeSub: {
    fontSize: 14,
    color: COLORS.muted,
    textAlign: 'center',
    marginTop: 6,
    marginBottom: 34,
  },
  cleanHomeBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    backgroundColor: COLORS.orange,
    paddingVertical: 15,
    paddingHorizontal: 28,
    borderRadius: 12,
    width: '100%',
  },
  cleanHomeBtnText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 16,
  },
  cleanHomeFooter: {
    textAlign: 'center',
    fontSize: 12,
    color: '#94a3b8',
  },
  homeScroll: {
    flexGrow: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
    width: '100%',
  },
  heroCard: {
    backgroundColor: COLORS.card,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 28,
    alignItems: 'center',
    width: '100%',
    maxWidth: 520,
  },
  homeFooterBar: {
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    backgroundColor: COLORS.card,
  },
  heroTag: {
    backgroundColor: COLORS.orangeSoft,
    borderColor: COLORS.orangeBorder,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 4,
    marginBottom: 12,
  },
  heroTagText: { fontSize: 11, fontWeight: '800', color: COLORS.orangeText, letterSpacing: 0.8 },
  heroTitle: { fontSize: 22, fontWeight: '800', color: COLORS.shieldDark, textAlign: 'center', marginBottom: 8 },
  heroSubtitle: { fontSize: 14, color: '#475569', textAlign: 'center', lineHeight: 21, marginBottom: 20 },
  heroCta: {
    backgroundColor: COLORS.orange,
    paddingHorizontal: 26,
    paddingVertical: 13,
    borderRadius: 9,
    width: '100%',
    alignItems: 'center',
  },
  heroCtaText: { color: '#ffffff', fontWeight: '800', fontSize: 15 },
  sectionHeading: { fontSize: 14, fontWeight: '800', color: COLORS.shieldDark, marginBottom: 10, marginLeft: 2 },
  featureCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    marginBottom: 10,
  },
  featureIcon: { width: 44, height: 44, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  featureTitle: { fontSize: 14, fontWeight: '700', color: COLORS.navy, marginBottom: 2 },
  featureDesc: { fontSize: 12, color: COLORS.muted, lineHeight: 18 },
  demoStatsCard: { backgroundColor: COLORS.shieldDark, borderRadius: 14, padding: 16, marginTop: 8, marginBottom: 18 },
  demoStatsHeader: {
    color: '#93c5fd',
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1,
    textAlign: 'center',
    marginBottom: 12,
  },
  demoStatsRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-around' },
  demoStatItem: { alignItems: 'center', flex: 1 },
  demoStatVal: { color: '#ffffff', fontSize: 20, fontWeight: '800' },
  demoStatLbl: { color: '#cbd5e1', fontSize: 11, marginTop: 2 },
  demoStatDivider: { width: 1, height: 32, backgroundColor: 'rgba(255,255,255,0.16)' },
  footerText: { textAlign: 'center', fontSize: 11, color: COLORS.muted, marginTop: 4 },

  /* Login */
  loginInner: { flexGrow: 1, justifyContent: 'center', padding: 18, maxWidth: 460, width: '100%', alignSelf: 'center' },
  loginCard: { backgroundColor: COLORS.card, borderRadius: 16, borderWidth: 1, borderColor: COLORS.border, padding: 20 },
  loginTitle: { fontSize: 20, fontWeight: '800', color: COLORS.shieldDark, textAlign: 'center' },
  loginSub: { fontSize: 13, color: COLORS.muted, textAlign: 'center', marginTop: 4, marginBottom: 16 },
  label: { fontSize: 13, fontWeight: '700', color: '#334155', marginBottom: 6 },
  input: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 9,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 14,
    color: COLORS.navy,
    backgroundColor: '#ffffff',
  },
  selectBox: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 9,
    paddingHorizontal: 12,
    paddingVertical: 11,
    backgroundColor: '#f8fafc',
    gap: 8,
  },
  selectBoxText: {
    flex: 1,
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.navy,
  },
  selectDropdown: {
    marginTop: 6,
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 10,
    backgroundColor: '#ffffff',
    overflow: 'hidden',
  },
  selectOption: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.bgSoft,
  },
  selectOptionActive: {
    backgroundColor: '#eff6ff',
  },
  selectRoleBadge: {
    paddingHorizontal: 7,
    paddingVertical: 3,
    borderRadius: 5,
  },
  selectRoleText: {
    fontSize: 10,
    fontWeight: '800',
  },
  selectOptionName: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.navy,
  },
  selectOptionEmail: {
    fontSize: 11,
    color: COLORS.muted,
  },
  hint: { fontSize: 11, color: COLORS.muted, marginTop: 4 },
  primaryBtn: { marginTop: 18, backgroundColor: COLORS.orange, borderRadius: 9, paddingVertical: 13, alignItems: 'center' },
  primaryBtnText: { color: '#ffffff', fontWeight: '700', fontSize: 15 },
  linkBtn: { marginTop: 14, alignItems: 'center', paddingVertical: 6 },
  linkBtnText: { color: COLORS.shieldBlue, fontWeight: '700', fontSize: 13 },
  feedbackBox: { borderWidth: 1, borderRadius: 8, padding: 10, marginBottom: 12 },

  /* Header & Menu Mobile */
  header: {
    backgroundColor: COLORS.shieldDark,
    paddingHorizontal: 16,
    paddingTop: NAV_TOP_PADDING,
    paddingBottom: 14,
    borderBottomWidth: 3,
    borderBottomColor: COLORS.orangeBright,
  },
  headerTitle: { color: '#ffffff', fontSize: 18, fontWeight: '800' },
  headerSub: { color: '#cbd5e1', fontSize: 12, marginTop: 2 },
  menuPillBtn: {
    backgroundColor: 'rgba(255,255,255,0.15)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.28)',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
  },
  menuPillText: { color: '#ffffff', fontWeight: '700', fontSize: 13 },
  backBtn: { marginBottom: 6 },
  backBtnText: { color: '#fdba74', fontWeight: '700', fontSize: 14 },

  menuScroll: { padding: 16, paddingBottom: 34, maxWidth: 580, width: '100%', alignSelf: 'center' },
  menuSectionLabel: {
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.9,
    color: COLORS.muted,
    marginBottom: 8,
    marginLeft: 2,
  },
  menuBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 13,
    marginBottom: 9,
  },
  menuBtnHighlight: {
    backgroundColor: COLORS.orange,
    borderColor: COLORS.orange,
  },
  menuBtnLeft: { flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1 },
  menuIconWrap: {
    width: 38,
    height: 38,
    borderRadius: 10,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  statusInfoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 6,
  },
  menuBtnIcon: { fontSize: 20 },
  menuBtnTitle: { fontSize: 15, fontWeight: '700', color: COLORS.navy },
  menuBtnSub: { fontSize: 12, color: COLORS.muted, marginTop: 1 },
  menuArrow: { fontSize: 16, fontWeight: '800', color: COLORS.muted, marginLeft: 8 },

  subMenuBox: {
    marginLeft: 18,
    marginTop: -2,
    marginBottom: 10,
    paddingLeft: 12,
    borderLeftWidth: 2,
    borderLeftColor: COLORS.orangeBorder,
  },
  subMenuBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 9,
    paddingHorizontal: 12,
    paddingVertical: 9,
    marginBottom: 6,
  },
  subMenuText: { fontSize: 13, fontWeight: '700', color: COLORS.shieldDark },
  subMenuCount: {
    fontSize: 11,
    fontWeight: '800',
    color: COLORS.orangeText,
    backgroundColor: COLORS.orangeSoft,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 99,
  },

  /* Cards de conteúdo */
  accCard: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    padding: 14,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  accId: { fontWeight: '800', color: COLORS.orange, fontSize: 12 },
  accDeadline: { fontSize: 12, color: COLORS.muted, fontWeight: '600' },
  accTitle: { fontSize: 15, fontWeight: '700', color: COLORS.navy, marginTop: 6, marginBottom: 10 },
  progressTrack: { height: 7, backgroundColor: COLORS.border, borderRadius: 99, overflow: 'hidden' },
  progressFill: { height: '100%', backgroundColor: COLORS.success },
  accMeta: { fontSize: 12, color: '#475569', marginTop: 6 },
  itemCard: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    padding: 14,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  itemTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  itemNum: { fontWeight: '800', color: COLORS.shieldDark, fontSize: 13 },
  itemTitle: { fontSize: 14, color: COLORS.text, marginTop: 6, fontWeight: '600' },
  badge: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: 99 },
  badgeText: { fontSize: 11, fontWeight: '700' },
  rejectText: { color: '#b91c1c', fontSize: 12, marginTop: 6, fontWeight: '600' },
  attCount: { fontSize: 12, color: '#475569', marginTop: 8, fontWeight: '600' },
  attName: { fontSize: 12, color: '#334155', marginTop: 3 },
  uploadBtn: {
    marginTop: 10,
    backgroundColor: COLORS.shieldDark,
    borderRadius: 8,
    paddingVertical: 9,
    alignItems: 'center',
  },
  uploadBtnText: { color: '#ffffff', fontWeight: '700', fontSize: 13 },
  financeRow: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 4 },
  financePill: { fontSize: 12, fontWeight: '700' },
  financeSaldo: { fontSize: 13, fontWeight: '800', marginTop: 6 },
  yearTitle: { fontSize: 16, fontWeight: '800', color: COLORS.shieldDark, marginBottom: 8 },
  historyRow: { paddingVertical: 8, borderTopWidth: 1, borderTopColor: COLORS.bgSoft },
  historyRowTitle: { fontSize: 14, fontWeight: '600', color: COLORS.text },
  historyRowSub: { fontSize: 12, color: COLORS.muted, marginTop: 2 },
  emptyText: { textAlign: 'center', color: COLORS.muted, marginTop: 40, fontSize: 14 },

  /* Meu Perfil (detalhado em inputs, sem card) */
  profileScroll: {
    padding: 18,
    paddingBottom: 36,
    maxWidth: 580,
    width: '100%',
    alignSelf: 'center',
  },
  profileSectionTitle: {
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.9,
    color: COLORS.shieldDark,
    marginBottom: 10,
  },
  profilePhotoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
  },
  profileAvatarWrap: {
    width: 82,
    height: 82,
    borderRadius: 41,
    backgroundColor: '#e0ecff',
    borderWidth: 2,
    borderColor: COLORS.shieldBlue,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  profileAvatarImg: {
    width: '100%',
    height: '100%',
    resizeMode: 'cover',
  },
  profilePhotoBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
    backgroundColor: COLORS.shieldDark,
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: 8,
  },
  profilePhotoBtnText: {
    color: '#ffffff',
    fontWeight: '700',
    fontSize: 13,
  },
  profileRemoveBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: '#fee2e2',
    borderWidth: 1,
    borderColor: '#fecaca',
    paddingHorizontal: 12,
    paddingVertical: 9,
    borderRadius: 8,
  },
  profileRemoveBtnText: {
    color: '#b91c1c',
    fontWeight: '700',
    fontSize: 13,
  },
  profileDivider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginVertical: 20,
  },
  inputReadonly: {
    backgroundColor: '#e2e8f0',
    color: '#475569',
  },
  submitFooterBox: {
    marginTop: 6,
    marginBottom: 18,
  },
  submitFooterTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#15803d',
    marginBottom: 4,
  },
  submitFooterDesc: {
    fontSize: 12,
    color: COLORS.muted,
    marginBottom: 12,
    lineHeight: 18,
  },
  submitConfirmBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: COLORS.success,
    borderRadius: 10,
    paddingVertical: 14,
    paddingHorizontal: 16,
  },
  submitConfirmBtnText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 15,
  },
  submitPendingBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: '#eff6ff',
    borderWidth: 1,
    borderColor: '#bfdbfe',
    borderRadius: 10,
    padding: 12,
  },
  submitPendingText: {
    flex: 1,
    fontSize: 12,
    color: COLORS.shieldDark,
    fontWeight: '600',
    lineHeight: 18,
  },
  itemOpenRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 12,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: COLORS.bgSoft,
  },
  itemOpenCue: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.shieldBlue,
  },
  attCard: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    padding: 13,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  attCardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  attIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 10,
    backgroundColor: COLORS.bgSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  attCardName: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.navy,
  },
  attCardMeta: {
    fontSize: 11,
    color: COLORS.muted,
    marginTop: 2,
  },
  attActionRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 11,
  },
  attViewBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    backgroundColor: COLORS.shieldBlue,
    paddingVertical: 9,
    borderRadius: 8,
  },
  attViewBtnText: {
    color: '#ffffff',
    fontWeight: '700',
    fontSize: 13,
  },
  attDeleteBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    backgroundColor: '#fee2e2',
    borderWidth: 1,
    borderColor: '#fecaca',
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: 8,
  },
  attDeleteBtnText: {
    color: '#b91c1c',
    fontWeight: '700',
    fontSize: 13,
  },
  previewModalBg: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.94)',
    justifyContent: 'center',
  },
  previewModalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: NAV_TOP_PADDING,
    paddingBottom: 12,
    gap: 12,
  },
  previewModalTitle: {
    flex: 1,
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '700',
  },
  previewModalCloseBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: 'rgba(255,255,255,0.16)',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
  },
  previewModalCloseText: {
    color: '#ffffff',
    fontWeight: '700',
    fontSize: 13,
  },
  previewModalImage: {
    flex: 1,
    width: '100%',
    marginBottom: 20,
  },
  listActionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  newAccBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: COLORS.orange,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 8,
  },
  newAccBtnText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 13,
  },
  formModalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.55)',
    justifyContent: 'center',
    padding: 18,
  },
  formModalCard: {
    backgroundColor: COLORS.card,
    borderRadius: 16,
    padding: 18,
    maxHeight: '85%',
    width: '100%',
    maxWidth: 480,
    alignSelf: 'center',
  },
  formModalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
    paddingBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  formModalTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.shieldDark,
  },
  formModalActions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 18,
  },
  formModalCancelBtn: {
    flex: 1,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 9,
    paddingVertical: 12,
    alignItems: 'center',
    backgroundColor: COLORS.bgSoft,
  },
  formModalCancelText: {
    color: COLORS.text,
    fontWeight: '700',
    fontSize: 14,
  },
  formModalSaveBtn: {
    flex: 1,
    backgroundColor: COLORS.orange,
    borderRadius: 9,
    paddingVertical: 12,
    alignItems: 'center',
  },
  formModalSaveText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 14,
  },
  roleToggleBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 7,
    paddingVertical: 11,
    borderRadius: 9,
    borderWidth: 1,
    borderColor: '#cbd5e1',
    backgroundColor: '#ffffff',
  },
  roleToggleBtnActive: {
    backgroundColor: COLORS.shieldDark,
    borderColor: COLORS.shieldDark,
  },
  roleToggleBtnActiveAdmin: {
    backgroundColor: COLORS.orange,
    borderColor: COLORS.orange,
  },
  roleToggleText: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.muted,
  },
  roleToggleTextActive: {
    color: '#ffffff',
  },
  permQuickBtn: {
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 7,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  permQuickBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.shieldDark,
  },
  permCheckRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 9,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.card,
    marginBottom: 7,
  },
  permCheckRowActive: {
    borderColor: COLORS.orangeBorder,
    backgroundColor: COLORS.orangeSoft,
  },
  permCheckText: {
    flex: 1,
    fontSize: 12,
    color: COLORS.text,
    lineHeight: 18,
  },
  waActionBtn: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: '#16a34a',
    paddingVertical: 11,
    paddingHorizontal: 14,
    borderRadius: 9,
  },
  waActionBtnText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 13,
  },
  waUserCard: {
    backgroundColor: COLORS.bg,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 10,
    padding: 12,
    marginBottom: 10,
  },
  waUserName: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.shieldDark,
  },
  waUserSub: {
    fontSize: 12,
    color: COLORS.muted,
    marginTop: 2,
  },
  waUserItems: {
    fontSize: 11,
    fontWeight: '700',
    color: COLORS.orangeText,
    marginTop: 4,
  },
  waSendPhoneBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    backgroundColor: '#16a34a',
    paddingVertical: 9,
    borderRadius: 8,
  },
  waSendBtnText: {
    color: '#ffffff',
    fontWeight: '700',
    fontSize: 12,
  },
  waSendApiBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    backgroundColor: '#e0ecff',
    borderWidth: 1,
    borderColor: '#bfdbfe',
    paddingHorizontal: 12,
    paddingVertical: 9,
    borderRadius: 8,
  },
  waSendApiText: {
    color: COLORS.shieldDark,
    fontWeight: '700',
    fontSize: 12,
  },

  /* Rodapé fixo com ícones */
  bottomNav: {
    flexDirection: 'row',
    backgroundColor: COLORS.card,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    paddingBottom: 6,
    paddingTop: 6,
  },
  bottomNavItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 5,
    borderTopWidth: 2.5,
    borderTopColor: 'transparent',
  },
  bottomNavItemActive: {
    borderTopColor: COLORS.orange,
  },
  bottomNavIcon: { fontSize: 19, marginBottom: 2 },
  bottomNavLabel: { fontSize: 11, fontWeight: '700', color: COLORS.muted },
  bottomNavLabelActive: { color: COLORS.orange, fontWeight: '800' },
});
