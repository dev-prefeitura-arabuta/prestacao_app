import React, { useState } from 'react';
import { Alert, SafeAreaView, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { apiForgotPassword, apiResetPassword, apiVerifyResetCode } from '../../api';
import { DesktopLogo } from '../../components/Brand';
import { Feedback, FeedbackBox, PrimaryButton } from '../../components/ui';
import { styles } from '../../theme/styles';
import { errorMessage } from '../../utils/format';
import { GuestTopBar } from './GuestTopBar';

const STEP_HINTS = {
  1: 'Informe o e-mail da sua conta para receber um código de 6 dígitos.',
  2: 'Digite o código de 6 dígitos enviado para o seu e-mail.',
  3: 'Código validado! Defina sua nova senha de acesso.',
} as const;

/** Esqueci minha senha em 3 etapas: 1 = e-mail, 2 = código de 6 dígitos (SMTP), 3 = nova senha. */
export function ForgotPasswordScreen({
  apiUrl,
  initialEmail,
  onHome,
  onBackToLogin,
  onPasswordReset,
}: {
  apiUrl: string;
  initialEmail: string;
  onHome: () => void;
  onBackToLogin: () => void;
  /** Chamado após redefinir a senha, com o e-mail usado (para preencher o login). */
  onPasswordReset: (email: string) => void;
}) {
  const [step, setStep] = useState<1 | 2 | 3>(1);
  const [email, setEmail] = useState(initialEmail);
  const [code, setCode] = useState('');
  const [newPass, setNewPass] = useState('');
  const [confirmPass, setConfirmPass] = useState('');
  const [loading, setLoading] = useState(false);
  const [feedback, setFeedback] = useState<Feedback | null>(null);

  async function run(action: () => Promise<void>, fallbackError: string) {
    setLoading(true);
    setFeedback(null);
    try {
      await action();
    } catch (e: unknown) {
      setFeedback({ type: 'err', text: errorMessage(e, fallbackError) });
    } finally {
      setLoading(false);
    }
  }

  function handleSendCode() {
    if (!email.trim()) {
      setFeedback({ type: 'err', text: 'Informe o e-mail cadastrado.' });
      return;
    }
    run(async () => {
      const msg = await apiForgotPassword(apiUrl, email.trim());
      setFeedback({ type: 'ok', text: msg });
      setStep(2);
    }, 'Erro ao enviar código.');
  }

  function handleVerifyCode() {
    if (code.replace(/\D/g, '').length !== 6) {
      setFeedback({ type: 'err', text: 'Digite os 6 dígitos do código.' });
      return;
    }
    run(async () => {
      const msg = await apiVerifyResetCode(apiUrl, email.trim(), code.trim());
      setFeedback({ type: 'ok', text: msg });
      setStep(3);
    }, 'Código inválido.');
  }

  function handleConfirm() {
    if (newPass.length < 6) {
      setFeedback({ type: 'err', text: 'A nova senha deve ter pelo menos 6 caracteres.' });
      return;
    }
    if (newPass !== confirmPass) {
      setFeedback({ type: 'err', text: 'A confirmação de senha não confere.' });
      return;
    }
    run(async () => {
      const msg = await apiResetPassword(apiUrl, email.trim(), code.trim(), newPass, confirmPass);
      Alert.alert('Sucesso', msg);
      onPasswordReset(email.trim());
    }, 'Erro ao redefinir senha.');
  }

  const emailInput = (
    <TextInput
      style={styles.input}
      value={email}
      onChangeText={setEmail}
      keyboardType="email-address"
      autoCapitalize="none"
      placeholder="seu@email.gov.br"
    />
  );

  return (
    <SafeAreaView style={styles.guestRoot}>
      <StatusBar style="dark" />
      <GuestTopBar onBrandPress={onHome} backLabel="← Login" onBack={onBackToLogin} />

      <ScrollView contentContainerStyle={styles.loginInner} keyboardShouldPersistTaps="handled">
        <View style={styles.loginCard}>
          <View style={{ alignItems: 'center', marginBottom: 12 }}>
            <DesktopLogo height={66} />
          </View>

          <Text style={styles.loginTitle}>Esqueci minha senha</Text>
          <Text style={styles.loginSub}>{STEP_HINTS[step]}</Text>

          {feedback && <FeedbackBox feedback={feedback} />}

          {step === 1 && (
            <>
              <Text style={styles.label}>E-mail cadastrado</Text>
              {emailInput}
              <PrimaryButton label="Enviar código de 6 dígitos" loading={loading} onPress={handleSendCode} />
              <TouchableOpacity
                style={styles.linkBtn}
                onPress={() => {
                  setFeedback(null);
                  setStep(2);
                }}
              >
                <Text style={styles.linkBtnText}>Já recebi o código de 6 dígitos</Text>
              </TouchableOpacity>
            </>
          )}

          {step === 2 && (
            <>
              <Text style={styles.label}>E-mail</Text>
              {emailInput}

              <Text style={[styles.label, { marginTop: 12 }]}>Código de 6 dígitos</Text>
              <TextInput
                style={[styles.input, { textAlign: 'center', fontSize: 20, fontWeight: '800', letterSpacing: 6 }]}
                value={code}
                onChangeText={(v) => setCode(v.replace(/\D/g, '').slice(0, 6))}
                keyboardType="number-pad"
                maxLength={6}
                placeholder="000000"
              />

              <PrimaryButton label="Verificar código" loading={loading} onPress={handleVerifyCode} />
              <TouchableOpacity style={styles.linkBtn} onPress={handleSendCode} disabled={loading}>
                <Text style={styles.linkBtnText}>Reenviar código</Text>
              </TouchableOpacity>
            </>
          )}

          {step === 3 && (
            <>
              <Text style={styles.label}>Nova Senha</Text>
              <TextInput
                style={styles.input}
                value={newPass}
                onChangeText={setNewPass}
                secureTextEntry
                placeholder="Mínimo 6 caracteres"
              />

              <Text style={[styles.label, { marginTop: 12 }]}>Confirmar Nova Senha</Text>
              <TextInput
                style={styles.input}
                value={confirmPass}
                onChangeText={setConfirmPass}
                secureTextEntry
                placeholder="Repita a nova senha"
              />

              <PrimaryButton label="Redefinir senha" loading={loading} onPress={handleConfirm} />
            </>
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
