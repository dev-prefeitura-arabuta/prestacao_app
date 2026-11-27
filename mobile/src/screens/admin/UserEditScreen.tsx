import React, { useState } from 'react';
import { Alert, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { EditableUserDetail, apiUpdateUser } from '../../api';
import { BackLink, ChecklistPicker, PrimaryButton } from '../../components/ui';
import { COLORS } from '../../theme/colors';
import { styles } from '../../theme/styles';
import { errorMessage } from '../../utils/format';

type Role = 'admin' | 'user';

function RoleToggle({ value, onChange }: { value: Role; onChange: (r: Role) => void }) {
  const options: Array<{ role: Role; icon: string; label: string; activeStyle: object }> = [
    { role: 'user', icon: 'user', label: 'Usuário Padrão', activeStyle: styles.roleToggleBtnActive },
    { role: 'admin', icon: 'user-shield', label: 'Administrador', activeStyle: styles.roleToggleBtnActiveAdmin },
  ];
  return (
    <View style={{ flexDirection: 'row', gap: 10 }}>
      {options.map((o) => {
        const active = value === o.role;
        return (
          <TouchableOpacity
            key={o.role}
            style={[styles.roleToggleBtn, active && o.activeStyle]}
            onPress={() => onChange(o.role)}
          >
            <FontAwesome5 name={o.icon} size={13} color={active ? '#ffffff' : COLORS.muted} />
            <Text style={[styles.roleToggleText, active && styles.roleToggleTextActive]}>{o.label}</Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

/** Formulário de edição de usuário + permissões por item do checklist. */
export function UserEditScreen({
  token,
  user,
  onBack,
  onSaved,
}: {
  token: string;
  user: EditableUserDetail;
  onBack: () => void;
  onSaved: () => void;
}) {
  const [name, setName] = useState(user.name || '');
  const [email, setEmail] = useState(user.email || '');
  const [whatsapp, setWhatsapp] = useState(user.whatsapp || '');
  const [role, setRole] = useState<Role>(user.role === 'admin' ? 'admin' : 'user');
  const [password, setPassword] = useState('');
  const [allowedItems, setAllowedItems] = useState<number[]>(user.allowed_items || []);
  const [saving, setSaving] = useState(false);

  async function handleSave() {
    if (name.trim().length < 3) {
      Alert.alert('Atenção', 'O nome deve ter pelo menos 3 caracteres.');
      return;
    }
    if (!email.trim() || !email.includes('@')) {
      Alert.alert('Atenção', 'Informe um e-mail válido.');
      return;
    }
    if (password.length > 0 && password.length < 6) {
      Alert.alert('Atenção', 'A nova senha deve ter pelo menos 6 caracteres.');
      return;
    }
    setSaving(true);
    try {
      const msg = await apiUpdateUser(token, user.id, {
        name: name.trim(),
        email: email.trim(),
        whatsapp: whatsapp.trim(),
        role,
        password: password || undefined,
        allowed_items: allowedItems,
      });
      Alert.alert('Sucesso', msg);
      onSaved();
    } catch (e: unknown) {
      Alert.alert('Erro', errorMessage(e, 'Não foi possível salvar o usuário.'));
    } finally {
      setSaving(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.profileScroll} keyboardShouldPersistTaps="handled">
      <BackLink label="← Voltar para Usuários" onPress={onBack} />

      <Text style={styles.profileSectionTitle}>EDITAR USUÁRIO #{user.id}</Text>

      <Text style={styles.label}>Nome completo</Text>
      <TextInput
        style={styles.input}
        value={name}
        onChangeText={setName}
        placeholder="Nome completo"
        placeholderTextColor="#94a3b8"
      />

      <Text style={[styles.label, { marginTop: 12 }]}>E-mail</Text>
      <TextInput
        style={styles.input}
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
        autoCapitalize="none"
        autoCorrect={false}
        placeholder="usuario@email.com"
        placeholderTextColor="#94a3b8"
      />

      <Text style={[styles.label, { marginTop: 12 }]}>WhatsApp</Text>
      <TextInput
        style={styles.input}
        value={whatsapp}
        onChangeText={setWhatsapp}
        keyboardType="phone-pad"
        placeholder="Ex: (49) 99999-9999"
        placeholderTextColor="#94a3b8"
      />

      <Text style={[styles.label, { marginTop: 12 }]}>Nova senha (opcional)</Text>
      <TextInput
        style={styles.input}
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        placeholder="Deixe em branco para manter a atual"
        placeholderTextColor="#94a3b8"
      />

      <Text style={[styles.label, { marginTop: 14 }]}>Perfil de acesso</Text>
      {user.is_primary_admin ? (
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            gap: 8,
            padding: 12,
            borderRadius: 9,
            backgroundColor: '#f1f5f9',
            borderWidth: 1,
            borderColor: '#e2e8f0',
          }}
        >
          <FontAwesome5 name="lock" size={13} color={COLORS.muted} />
          <Text style={{ flex: 1, fontSize: 13, color: '#334155' }}>
            <Text style={{ fontWeight: '800' }}>Administrador (principal)</Text> — sempre será administrador.
          </Text>
        </View>
      ) : (
        <RoleToggle value={role} onChange={setRole} />
      )}

      <View style={styles.profileDivider} />

      <Text style={styles.profileSectionTitle}>
        PERMISSÕES DE ITENS ({allowedItems.length}/{user.all_items.length})
      </Text>

      {role === 'admin' ? (
        <View style={styles.submitPendingBanner}>
          <FontAwesome5 name="shield-alt" size={15} color={COLORS.shieldBlue} />
          <Text style={styles.submitPendingText}>
            Administradores têm acesso automático a todos os 24 itens do checklist.
          </Text>
        </View>
      ) : (
        <ChecklistPicker
          items={user.all_items}
          selected={allowedItems}
          onChange={setAllowedItems}
          iconSize={17}
          quickButtonsMarginBottom={12}
        />
      )}

      <PrimaryButton label="Salvar Usuário e Permissões" loading={saving} onPress={handleSave} style={{ marginTop: 22 }} />
    </ScrollView>
  );
}
