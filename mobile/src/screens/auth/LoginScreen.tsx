import React, { useEffect, useState } from 'react';
import { Alert, SafeAreaView, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { FontAwesome5 } from '@expo/vector-icons';
import { MobileUser, QuickLoginUser, apiGetQuickLoginUsers, apiLogin } from '../../api';
import { DesktopLogo } from '../../components/Brand';
import { PrimaryButton } from '../../components/ui';
import { COLORS } from '../../theme/colors';
import { styles } from '../../theme/styles';
import { errorMessage } from '../../utils/format';
import { GuestTopBar } from './GuestTopBar';

const DEV_PASSWORD = '123456';

export function LoginScreen({
  apiUrl,
  email,
  onEmailChange,
  onHome,
  onForgotPassword,
  onLoggedIn,
}: {
  apiUrl: string;
  email: string;
  onEmailChange: (email: string) => void;
  onHome: () => void;
  onForgotPassword: () => void;
  onLoggedIn: (token: string, user: MobileUser) => void;
}) {
  const [password, setPassword] = useState(DEV_PASSWORD);
  const [loading, setLoading] = useState(false);
  const [quickUsers, setQuickUsers] = useState<QuickLoginUser[]>([]);
  const [dropdownOpen, setDropdownOpen] = useState(false);

  useEffect(() => {
    apiGetQuickLoginUsers(apiUrl)
      .then(setQuickUsers)
      .catch(() => {});
  }, [apiUrl]);

  async function handleLogin() {
    if (!email.trim() || !password) {
      Alert.alert('Campos obrigatórios', 'Informe seu e-mail e senha.');
      return;
    }
    setLoading(true);
    try {
      const res = await apiLogin(apiUrl, email.trim(), password);
      setPassword(DEV_PASSWORD);
      onLoggedIn(res.token, res.user);
    } catch (e: unknown) {
      Alert.alert('Erro no login', errorMessage(e, 'Não foi possível conectar.'));
    } finally {
      setLoading(false);
    }
  }

  const selectedQuick = quickUsers.find((u) => u.email === email);

  return (
    <SafeAreaView style={styles.guestRoot}>
      <StatusBar style="dark" />
      <GuestTopBar onBrandPress={onHome} backLabel="← Home" onBack={onHome} />

      <ScrollView contentContainerStyle={styles.loginInner} keyboardShouldPersistTaps="handled">
        <View style={styles.loginCard}>
          <View style={{ alignItems: 'center', marginBottom: 14 }}>
            <DesktopLogo height={70} />
          </View>
          <Text style={styles.loginTitle}>Login Rápido</Text>
          <Text style={styles.loginSub}>Selecione um usuário ou informe suas credenciais</Text>

          {/* SELECT RÁPIDO DESENVOLVEDOR */}
          <Text style={styles.label}>Selecionar Usuário (Dev)</Text>
          <TouchableOpacity style={styles.selectBox} activeOpacity={0.8} onPress={() => setDropdownOpen((p) => !p)}>
            <Text style={[styles.selectBoxText, !email && { color: '#94a3b8' }]} numberOfLines={1}>
              {selectedQuick
                ? `[${selectedQuick.role === 'admin' ? 'ADMIN' : 'USER'}] ${selectedQuick.name} (${selectedQuick.email})`
                : '-- Escolha uma conta --'}
            </Text>
            <FontAwesome5 name={dropdownOpen ? 'chevron-up' : 'chevron-down'} size={13} color={COLORS.muted} />
          </TouchableOpacity>

          {dropdownOpen && (
            <View style={styles.selectDropdown}>
              {quickUsers.length === 0 ? (
                <Text style={[styles.hint, { padding: 10, marginTop: 0 }]}>Nenhum usuário carregado do servidor.</Text>
              ) : (
                quickUsers.map((u) => {
                  const isAdm = u.role === 'admin';
                  return (
                    <TouchableOpacity
                      key={u.id}
                      style={[styles.selectOption, email === u.email && styles.selectOptionActive]}
                      onPress={() => {
                        onEmailChange(u.email);
                        setPassword(DEV_PASSWORD);
                        setDropdownOpen(false);
                      }}
                    >
                      <View
                        style={[styles.selectRoleBadge, { backgroundColor: isAdm ? COLORS.orangeSoft : COLORS.bgSoft }]}
                      >
                        <Text style={[styles.selectRoleText, { color: isAdm ? COLORS.orangeText : COLORS.muted }]}>
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
            onChangeText={onEmailChange}
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

          <PrimaryButton label="Entrar" loading={loading} onPress={handleLogin} />

          <TouchableOpacity style={styles.linkBtn} onPress={onForgotPassword}>
            <Text style={styles.linkBtnText}>Esqueci minha senha</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
