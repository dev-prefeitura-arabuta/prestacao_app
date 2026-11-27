import React from 'react';
import { Linking, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { MobileUser } from '../api';
import { COLORS } from '../theme/colors';
import { styles } from '../theme/styles';

/**
 * Tela inicial da conta de desenvolvedor no app. A área de negócio (prestações, avaliação,
 * usuários…) não é usada por essa conta; logs e configurações técnicas ficam no painel web.
 */
export function DeveloperHomeScreen({
  user,
  apiUrl,
  onOpenProfile,
  onLogout,
}: {
  user: MobileUser;
  apiUrl: string;
  onOpenProfile: () => void;
  onLogout: () => void;
}) {
  const panelUrl = `${apiUrl.replace(/\/+$/, '')}/logs`;

  return (
    <ScrollView contentContainerStyle={{ padding: 16 }}>
      <View style={[styles.itemCard, { borderColor: '#c7d2fe', backgroundColor: '#eef2ff' }]}>
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 8 }}>
          <FontAwesome5 name="code" size={18} color="#3730a3" />
          <Text style={{ fontSize: 16, fontWeight: '800', color: '#1e1b4b' }}>Conta de desenvolvedor</Text>
        </View>
        <Text style={{ fontSize: 13, color: '#312e81', lineHeight: 19 }}>
          Olá, {user.name}! Esta conta não participa das prestações de contas. Os logs do sistema e as configurações
          técnicas (e-mail, WhatsApp e IA) ficam no painel web.
        </Text>
        <TouchableOpacity
          style={[styles.primaryBtn, { backgroundColor: '#3730a3', flexDirection: 'row', justifyContent: 'center', gap: 8 }]}
          onPress={() => Linking.openURL(panelUrl).catch(() => {})}
        >
          <FontAwesome5 name="external-link-alt" size={13} color="#ffffff" />
          <Text style={{ color: '#ffffff', fontWeight: '800' }}>Abrir painel web (Logs)</Text>
        </TouchableOpacity>
        <Text style={{ fontSize: 11, color: '#4338ca', marginTop: 8 }}>{panelUrl}</Text>
      </View>

      <TouchableOpacity style={[styles.itemCard, { flexDirection: 'row', alignItems: 'center', gap: 12 }]} onPress={onOpenProfile}>
        <FontAwesome5 name="user-circle" size={20} color={COLORS.shieldBlue} />
        <View style={{ flex: 1 }}>
          <Text style={{ fontSize: 14, fontWeight: '800', color: '#0f172a' }}>Meu Perfil</Text>
          <Text style={{ fontSize: 12, color: '#64748b' }}>Nome, e-mail, WhatsApp, foto e senha</Text>
        </View>
        <FontAwesome5 name="chevron-right" size={12} color={COLORS.muted} />
      </TouchableOpacity>

      <TouchableOpacity style={[styles.itemCard, { flexDirection: 'row', alignItems: 'center', gap: 12 }]} onPress={onLogout}>
        <FontAwesome5 name="sign-out-alt" size={18} color="#dc2626" />
        <Text style={{ fontSize: 14, fontWeight: '800', color: '#dc2626' }}>Sair</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}
