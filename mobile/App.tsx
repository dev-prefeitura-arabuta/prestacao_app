import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Image, SafeAreaView, Text, TouchableOpacity, View } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import {
  AccountabilityDetail,
  AdminNotificationItem,
  DEFAULT_API_URL,
  MobileUser,
  apiGetAccountabilityDetail,
  apiGetMe,
  clearSession,
  getApiUrl,
  getStoredSession,
} from './src/api';
import { AppHeader } from './src/components/AppHeader';
import { BottomNav } from './src/components/BottomNav';
import { SISTEMA_LOGO_IMG } from './src/components/Brand';
import { NotificationsModal } from './src/components/NotificationsModal';
import { useAdminNotifications } from './src/hooks/useAdminNotifications';
import { useUploadQueue } from './src/hooks/useUploadQueue';
import { useWaRetryQueue } from './src/hooks/useWaRetryQueue';
import { clearScreenCache, loadWithCache } from './src/offline/screenCache';
import { clearPendingUploads } from './src/offline/uploadQueue';
import { AppScreen, USER_SCREENS } from './src/navigation/types';
import { AccountabilitiesScreen } from './src/screens/accountability/AccountabilitiesScreen';
import { AccountabilityDetailScreen } from './src/screens/accountability/AccountabilityDetailScreen';
import { AnnualHistoryScreen } from './src/screens/admin/AnnualHistoryScreen';
import { AuditScreen } from './src/screens/admin/AuditScreen';
import { MenuScreen } from './src/screens/admin/MenuScreen';
import { PendingByUserScreen } from './src/screens/admin/PendingByUserScreen';
import { ReviewQueueScreen } from './src/screens/admin/ReviewQueueScreen';
import { SettingsScreen } from './src/screens/admin/SettingsScreen';
import { UsersScreen } from './src/screens/admin/UsersScreen';
import { AuthFlow } from './src/screens/auth/AuthFlow';
import { DeveloperHomeScreen } from './src/screens/DeveloperHomeScreen';
import { ProfileScreen } from './src/screens/ProfileScreen';
import { COLORS } from './src/theme/colors';
import { styles } from './src/theme/styles';
import { errorMessage } from './src/utils/format';

function homeScreenFor(user: MobileUser): AppScreen {
  if (user.is_developer) return 'profile';
  return user.role === 'admin' ? 'menu' : 'accountabilities';
}

export default function App() {
  const [booting, setBooting] = useState(true);
  const [apiUrl, setApiUrl] = useState(DEFAULT_API_URL);
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<MobileUser | null>(null);
  const [loginEmail, setLoginEmail] = useState('');

  // Navegação (menu mobile no lugar da sidebar do desktop)
  const [screen, setScreen] = useState<AppScreen>('menu');
  const [historyYear, setHistoryYear] = useState<string | null>(null);
  const [detail, setDetail] = useState<AccountabilityDetail | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [detailOfflineSince, setDetailOfflineSince] = useState<string | null>(null);
  // Item a abrir direto ao entrar no detalhe (vindo de "Para avaliar" ou "Pendências")
  const [detailInitialItem, setDetailInitialItem] = useState<number | null>(null);
  const [notifModalOpen, setNotifModalOpen] = useState(false);

  // A conta de desenvolvedor é admin no servidor, mas no app não usa a área de negócio
  const isDeveloper = !!user?.is_developer;
  const isAdmin = user?.role === 'admin' && !isDeveloper;
  const [devProfileOpen, setDevProfileOpen] = useState(false);
  const wa = useWaRetryQueue(token, isAdmin);
  const notif = useAdminNotifications(token, isAdmin);
  // Quando um anexo da fila offline chega ao servidor, atualiza a prestação aberta
  const detailIdRef = useRef<number | null>(null);
  useEffect(() => {
    detailIdRef.current = detail?.id ?? null;
  }, [detail]);
  const uploads = useUploadQueue(token, user?.id ?? null, () => {
    if (detailIdRef.current !== null) openDetail(detailIdRef.current, true);
  });

  useEffect(() => {
    (async () => {
      setApiUrl(await getApiUrl());
      const session = await getStoredSession();
      if (session) {
        setToken(session.token);
        setUser(session.user);
        setScreen(homeScreenFor(session.user));
        // Atualiza os dados salvos (ex.: a conta virou desenvolvedor depois do último login)
        apiGetMe(session.token)
          .then((fresh) => {
            setUser(fresh);
            setScreen(homeScreenFor(fresh));
          })
          .catch(() => {});
      }
      setBooting(false);
    })();
  }, []);

  // Usuário comum só acessa Prestações e Meu Perfil
  useEffect(() => {
    if (user && !isAdmin && !isDeveloper && !USER_SCREENS.includes(screen)) {
      setScreen('accountabilities');
    }
  }, [user, isAdmin, isDeveloper, screen]);

  function handleLoggedIn(newToken: string, newUser: MobileUser) {
    setToken(newToken);
    setUser(newUser);
    setScreen(homeScreenFor(newUser));
  }

  async function handleLogout() {
    const waiting = uploads.items.length;
    if (waiting > 0) {
      Alert.alert(
        'Anexos aguardando envio',
        `Há ${waiting} anexo(s) salvos no aparelho que ainda não chegaram ao servidor. Se mantiver, eles serão enviados quando você entrar de novo com esta conta.`,
        [
          { text: 'Cancelar', style: 'cancel' },
          { text: 'Manter e sair', onPress: () => doLogout(false) },
          { text: 'Descartar e sair', style: 'destructive', onPress: () => doLogout(true) },
        ]
      );
      return;
    }
    await doLogout(false);
  }

  async function doLogout(discardUploads: boolean) {
    if (discardUploads && user) await clearPendingUploads(user.id);
    clearScreenCache();
    await clearSession();
    setToken(null);
    setUser(null);
    setDevProfileOpen(false);
    setDetail(null);
    setScreen('menu');
  }

  async function openItem(accountabilityId: number, itemNumber: number) {
    setDetailInitialItem(itemNumber);
    await openDetail(accountabilityId);
  }

  async function openDetail(id: number, silent = false) {
    if (!token || !user) return;
    setDetailLoading(true);
    try {
      const res = await loadWithCache(user.id, `detail_${id}`, () => apiGetAccountabilityDetail(token, id));
      setDetail(res.data);
      setDetailOfflineSince(res.offlineSince);
    } catch (e: unknown) {
      if (!silent) {
        setDetailInitialItem(null);
        Alert.alert('Erro', errorMessage(e, 'Erro ao abrir prestação.'));
      }
    } finally {
      setDetailLoading(false);
    }
  }

  function navigateTo(target: AppScreen, year: string | null = null) {
    setDetail(null);
    setDetailInitialItem(null);
    setHistoryYear(year);
    setScreen(!isAdmin && !USER_SCREENS.includes(target) ? 'accountabilities' : target);
  }

  async function handleOpenNotification(it: AdminNotificationItem) {
    notif.markRead(it);
    setNotifModalOpen(false);
    if (it.accountability_id) {
      await openDetail(it.accountability_id);
    }
  }

  if (booting) {
    return (
      <SafeAreaView style={styles.center}>
        <Image source={SISTEMA_LOGO_IMG} style={{ width: 220, height: 110, resizeMode: 'contain', marginBottom: 18 }} />
        <ActivityIndicator size="large" color={COLORS.orange} />
      </SafeAreaView>
    );
  }

  if (!token || !user) {
    return <AuthFlow apiUrl={apiUrl} email={loginEmail} onEmailChange={setLoginEmail} onLoggedIn={handleLoggedIn} />;
  }

  // Conta de desenvolvedor: só o próprio perfil (logs e configurações ficam no painel web)
  if (isDeveloper) {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="light" />
        <AppHeader title={devProfileOpen ? 'Meu Perfil' : 'Desenvolvedor'} userName={user.name} />
        <View style={{ flex: 1 }}>
          {devProfileOpen ? (
            <ProfileScreen
              token={token}
              user={user}
              apiUrl={apiUrl}
              isAdmin={false}
              onUserUpdated={setUser}
              onBack={() => setDevProfileOpen(false)}
            />
          ) : (
            <DeveloperHomeScreen
              user={user}
              apiUrl={apiUrl}
              onOpenProfile={() => setDevProfileOpen(true)}
              onLogout={handleLogout}
            />
          )}
        </View>
        {devProfileOpen && (
          <TouchableOpacity
            style={{ padding: 14, alignItems: 'center', borderTopWidth: 1, borderTopColor: '#e2e8f0', backgroundColor: '#ffffff' }}
            onPress={() => setDevProfileOpen(false)}
          >
            <Text style={{ color: COLORS.shieldBlue, fontWeight: '800' }}>← Voltar</Text>
          </TouchableOpacity>
        )}
      </SafeAreaView>
    );
  }

  if (detail) {
    return (
      <AccountabilityDetailScreen
        detail={detail}
        token={token}
        apiUrl={apiUrl}
        isAdmin={isAdmin}
        loading={detailLoading}
        offlineSince={detailOfflineSince}
        uploads={uploads}
        userId={user.id}
        wa={wa}
        onReload={() => openDetail(detail.id)}
        initialItemNumber={detailInitialItem}
        onClose={() => {
          setDetail(null);
          setDetailInitialItem(null);
        }}
        onSubmitted={() => setDetail(null)}
        onDuplicated={() => navigateTo('accountabilities')}
      />
    );
  }

  const SCREEN_TITLES: Record<AppScreen, string> = {
    menu: 'Menu Principal',
    accountabilities: 'Prestações de Contas',
    review: 'Para Avaliar',
    pending_people: 'Pendências por Pessoa',
    annual_history: historyYear ? `Histórico Anual — ${historyYear}` : 'Histórico Anual',
    audit: 'Histórico de Auditoria',
    users: 'Usuários e Permissões',
    settings: 'Configurações',
    profile: 'Meu Perfil',
  };

  function renderScreen() {
    if (!token || !user) return null;
    switch (screen) {
      case 'accountabilities':
        return (
          <AccountabilitiesScreen
            token={token}
            isAdmin={isAdmin}
            userId={user.id}
            uploads={uploads}
            wa={wa}
            openingDetail={detailLoading}
            onOpenDetail={openDetail}
          />
        );
      case 'profile':
        return (
          <ProfileScreen
            token={token}
            user={user}
            apiUrl={apiUrl}
            isAdmin={isAdmin}
            onUserUpdated={setUser}
            onBack={() => navigateTo('settings')}
          />
        );
    }
    if (!isAdmin) return null;
    switch (screen) {
      case 'menu':
        return <MenuScreen token={token} onNavigate={navigateTo} />;
      case 'annual_history':
        return (
          <AnnualHistoryScreen token={token} year={historyYear} openingDetail={detailLoading} onOpenDetail={openDetail} />
        );
      case 'audit':
        return <AuditScreen token={token} />;
      case 'review':
        return <ReviewQueueScreen token={token} opening={detailLoading} onOpenItem={openItem} />;
      case 'pending_people':
        return <PendingByUserScreen token={token} onOpenItem={openItem} />;
      case 'users':
        return <UsersScreen token={token} onBack={() => navigateTo('settings')} />;
      case 'settings':
        return <SettingsScreen onNavigate={navigateTo} />;
    }
    return null;
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="light" />
      <AppHeader
        title={SCREEN_TITLES[screen]}
        userName={user.name}
        bell={
          isAdmin
            ? {
                enabled: notif.enabled,
                unread: notif.unread,
                onPress: () => {
                  setNotifModalOpen(true);
                  notif.refresh();
                },
              }
            : undefined
        }
      />

      <View style={{ flex: 1 }}>{renderScreen()}</View>

      <BottomNav screen={screen} isAdmin={isAdmin} onNavigate={navigateTo} onLogout={handleLogout} />

      {isAdmin && (
        <NotificationsModal
          visible={notifModalOpen}
          onClose={() => setNotifModalOpen(false)}
          notif={notif}
          onOpenItem={handleOpenNotification}
        />
      )}
    </SafeAreaView>
  );
}
