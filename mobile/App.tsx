import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Image, SafeAreaView, View } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import {
  AccountabilityDetail,
  AdminNotificationItem,
  DEFAULT_API_URL,
  MobileUser,
  apiGetAccountabilityDetail,
  clearSession,
  getApiUrl,
  getStoredSession,
} from './src/api';
import { AppHeader } from './src/components/AppHeader';
import { BottomNav } from './src/components/BottomNav';
import { SISTEMA_LOGO_IMG } from './src/components/Brand';
import { NotificationsModal } from './src/components/NotificationsModal';
import { useAdminNotifications } from './src/hooks/useAdminNotifications';
import { useWaRetryQueue } from './src/hooks/useWaRetryQueue';
import { AppScreen, USER_SCREENS } from './src/navigation/types';
import { AccountabilitiesScreen } from './src/screens/accountability/AccountabilitiesScreen';
import { AccountabilityDetailScreen } from './src/screens/accountability/AccountabilityDetailScreen';
import { AnnualHistoryScreen } from './src/screens/admin/AnnualHistoryScreen';
import { AuditScreen } from './src/screens/admin/AuditScreen';
import { MenuScreen } from './src/screens/admin/MenuScreen';
import { SettingsScreen } from './src/screens/admin/SettingsScreen';
import { UsersScreen } from './src/screens/admin/UsersScreen';
import { AuthFlow } from './src/screens/auth/AuthFlow';
import { ProfileScreen } from './src/screens/ProfileScreen';
import { COLORS } from './src/theme/colors';
import { styles } from './src/theme/styles';
import { errorMessage } from './src/utils/format';

function homeScreenFor(user: MobileUser): AppScreen {
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
  const [notifModalOpen, setNotifModalOpen] = useState(false);

  const isAdmin = user?.role === 'admin';
  const wa = useWaRetryQueue(token, isAdmin);
  const notif = useAdminNotifications(token, isAdmin);

  useEffect(() => {
    (async () => {
      setApiUrl(await getApiUrl());
      const session = await getStoredSession();
      if (session) {
        setToken(session.token);
        setUser(session.user);
        setScreen(homeScreenFor(session.user));
      }
      setBooting(false);
    })();
  }, []);

  // Usuário comum só acessa Prestações e Meu Perfil
  useEffect(() => {
    if (user && !isAdmin && !USER_SCREENS.includes(screen)) {
      setScreen('accountabilities');
    }
  }, [user, isAdmin, screen]);

  function handleLoggedIn(newToken: string, newUser: MobileUser) {
    setToken(newToken);
    setUser(newUser);
    setScreen(homeScreenFor(newUser));
  }

  async function handleLogout() {
    await clearSession();
    setToken(null);
    setUser(null);
    setDetail(null);
    setScreen('menu');
  }

  async function openDetail(id: number) {
    if (!token) return;
    setDetailLoading(true);
    try {
      setDetail(await apiGetAccountabilityDetail(token, id));
    } catch (e: unknown) {
      Alert.alert('Erro', errorMessage(e, 'Erro ao abrir prestação.'));
    } finally {
      setDetailLoading(false);
    }
  }

  function navigateTo(target: AppScreen, year: string | null = null) {
    setDetail(null);
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

  if (detail) {
    return (
      <AccountabilityDetailScreen
        detail={detail}
        token={token}
        apiUrl={apiUrl}
        isAdmin={isAdmin}
        loading={detailLoading}
        wa={wa}
        onReload={() => openDetail(detail.id)}
        onClose={() => setDetail(null)}
        onSubmitted={() => setDetail(null)}
      />
    );
  }

  const SCREEN_TITLES: Record<AppScreen, string> = {
    menu: 'Menu Principal',
    accountabilities: 'Prestações de Contas',
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
