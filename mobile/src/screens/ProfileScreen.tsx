import React, { useEffect, useState } from 'react';
import { Alert, Image, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { MobileUser, apiGetMe, apiRemoveAvatar, apiUpdateProfile } from '../api';
import { BackLink, Feedback, FeedbackBox, PrimaryButton } from '../components/ui';
import { COLORS } from '../theme/colors';
import { styles } from '../theme/styles';
import { errorMessage, joinApiUrl } from '../utils/format';
import { ImageSource, pickImage } from '../utils/pickImage';

type AvatarPick = { uri: string; name: string; mimeType?: string };

/** Meu Perfil: foto, dados da conta e troca de senha. */
export function ProfileScreen({
  token,
  user,
  apiUrl,
  isAdmin,
  onUserUpdated,
  onBack,
}: {
  token: string;
  user: MobileUser;
  apiUrl: string;
  isAdmin: boolean;
  onUserUpdated: (user: MobileUser) => void;
  onBack: () => void;
}) {
  const [name, setName] = useState(user.name || '');
  const [email, setEmail] = useState(user.email || '');
  const [whatsapp, setWhatsapp] = useState(user.whatsapp || '');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [avatarPick, setAvatarPick] = useState<AvatarPick | null>(null);
  const [saving, setSaving] = useState(false);
  const [feedback, setFeedback] = useState<Feedback | null>(null);
  const [avatarImgError, setAvatarImgError] = useState(false);

  // Busca os dados mais recentes do servidor (mantém o cache se offline)
  useEffect(() => {
    apiGetMe(token)
      .then((fresh) => {
        onUserUpdated(fresh);
        setName(fresh.name || '');
        setEmail(fresh.email || '');
        setWhatsapp(fresh.whatsapp || '');
      })
      .catch(() => {});
  }, [token]);

  async function handlePickPhoto(source: ImageSource) {
    try {
      const img = await pickImage(
        source,
        { allowsEditing: true, aspect: [1, 1], quality: 0.72 },
        'Permita o acesso à câmera para tirar uma foto.'
      );
      if (!img) return;
      setAvatarPick({
        uri: img.uri,
        name: img.fileName || `foto_perfil_${Date.now()}.${img.ext}`,
        mimeType: img.mimeType || 'image/jpeg',
      });
      setAvatarImgError(false);
      setFeedback(null);
    } catch (e: unknown) {
      Alert.alert('Erro', errorMessage(e, 'Não foi possível capturar ou selecionar a imagem.'));
    }
  }

  async function handleRemovePhoto() {
    if (avatarPick) {
      setAvatarPick(null);
      return;
    }
    if (!user.avatar_url) return;
    setSaving(true);
    setFeedback(null);
    try {
      const res = await apiRemoveAvatar(token);
      onUserUpdated(res.user);
      setFeedback({ type: 'ok', text: res.message });
    } catch (e: unknown) {
      setFeedback({ type: 'err', text: errorMessage(e, 'Erro ao remover foto.') });
    } finally {
      setSaving(false);
    }
  }

  async function handleSave() {
    const cleanName = name.trim();
    const cleanEmail = email.trim();

    if (cleanName.length < 3) {
      setFeedback({ type: 'err', text: 'O nome deve ter pelo menos 3 caracteres.' });
      return;
    }
    if (!cleanEmail || !cleanEmail.includes('@')) {
      setFeedback({ type: 'err', text: 'Informe um e-mail válido.' });
      return;
    }
    if (newPassword.length > 0 && newPassword.length < 6) {
      setFeedback({ type: 'err', text: 'A nova senha deve ter pelo menos 6 caracteres.' });
      return;
    }
    if (newPassword !== confirmPassword) {
      setFeedback({ type: 'err', text: 'A confirmação de senha não confere com a nova senha.' });
      return;
    }

    setSaving(true);
    setFeedback(null);
    try {
      const res = await apiUpdateProfile(token, {
        name: cleanName,
        email: cleanEmail,
        whatsapp: whatsapp.trim(),
        new_password: newPassword || undefined,
        confirm_password: confirmPassword || undefined,
        avatar: avatarPick,
      });
      onUserUpdated(res.user);
      setAvatarPick(null);
      setNewPassword('');
      setConfirmPassword('');
      setAvatarImgError(false);
      setFeedback({ type: 'ok', text: res.message });
    } catch (e: unknown) {
      setFeedback({ type: 'err', text: errorMessage(e, 'Erro ao salvar perfil.') });
    } finally {
      setSaving(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.profileScroll} keyboardShouldPersistTaps="handled">
      {isAdmin && <BackLink label="← Voltar para Configurações" onPress={onBack} />}

      {feedback && <FeedbackBox feedback={feedback} strong />}

      {/* FOTO DE PERFIL */}
      <Text style={styles.profileSectionTitle}>FOTO DE PERFIL</Text>
      <View style={styles.profilePhotoRow}>
        <View style={styles.profileAvatarWrap}>
          {avatarPick?.uri ? (
            <Image source={{ uri: avatarPick.uri }} style={styles.profileAvatarImg} />
          ) : user.avatar_url && !avatarImgError ? (
            <Image
              source={{ uri: joinApiUrl(apiUrl, user.avatar_url) }}
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
              onPress={() => handlePickPhoto('camera')}
              disabled={saving}
            >
              <FontAwesome5 name="camera" size={13} color="#ffffff" />
              <Text style={styles.profilePhotoBtnText}>Câmera</Text>
            </TouchableOpacity>

            <TouchableOpacity style={styles.profilePhotoBtn} onPress={() => handlePickPhoto('gallery')} disabled={saving}>
              <FontAwesome5 name="images" size={13} color="#ffffff" />
              <Text style={styles.profilePhotoBtnText}>Galeria</Text>
            </TouchableOpacity>

            {(avatarPick || user.avatar_url) && (
              <TouchableOpacity style={styles.profileRemoveBtn} onPress={handleRemovePhoto} disabled={saving}>
                <FontAwesome5 name="trash-alt" size={13} color="#b91c1c" />
                <Text style={styles.profileRemoveBtnText}>Remover</Text>
              </TouchableOpacity>
            )}
          </View>
          <Text style={styles.hint}>
            {avatarPick ? `Selecionada: ${avatarPick.name}` : 'Formatos JPG, PNG ou WEBP (máx. 5 MB)'}
          </Text>
        </View>
      </View>

      <View style={styles.profileDivider} />

      {/* DADOS DA CONTA */}
      <Text style={styles.profileSectionTitle}>DADOS DA CONTA</Text>

      <Text style={styles.label}>Nome completo</Text>
      <TextInput
        style={styles.input}
        value={name}
        onChangeText={setName}
        placeholder="Seu nome completo"
        placeholderTextColor="#94a3b8"
      />

      <Text style={[styles.label, { marginTop: 14 }]}>E-mail</Text>
      <TextInput
        style={styles.input}
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
        autoCapitalize="none"
        autoCorrect={false}
        placeholder="seu@email.com"
        placeholderTextColor="#94a3b8"
      />

      <Text style={[styles.label, { marginTop: 14 }]}>WhatsApp</Text>
      <TextInput
        style={styles.input}
        value={whatsapp}
        onChangeText={setWhatsapp}
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
        value={newPassword}
        onChangeText={setNewPassword}
        secureTextEntry
        placeholder="Mínimo 6 caracteres"
        placeholderTextColor="#94a3b8"
      />

      <Text style={[styles.label, { marginTop: 14 }]}>Confirmar nova senha</Text>
      <TextInput
        style={styles.input}
        value={confirmPassword}
        onChangeText={setConfirmPassword}
        secureTextEntry
        placeholder="Repita a nova senha"
        placeholderTextColor="#94a3b8"
      />

      <PrimaryButton label="Salvar Alterações" loading={saving} onPress={handleSave} style={{ marginTop: 22 }} />
    </ScrollView>
  );
}
