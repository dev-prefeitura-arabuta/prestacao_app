import React, { useState } from 'react';
import { Alert, FlatList, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { EditableUserDetail, MobileUser, apiGetUserDetail, apiGetUsers } from '../../api';
import { BackLink } from '../../components/ui';
import { useScreenData } from '../../hooks/useScreenData';
import { COLORS } from '../../theme/colors';
import { styles } from '../../theme/styles';
import { errorMessage } from '../../utils/format';
import { UserEditScreen } from './UserEditScreen';

/** Lista de usuários; tocar em um abre a edição de dados e permissões. */
export function UsersScreen({ token, onBack }: { token: string; onBack: () => void }) {
  const { data: users, loading, reload } = useScreenData<MobileUser[]>(() => apiGetUsers(token), []);
  const [editingUser, setEditingUser] = useState<EditableUserDetail | null>(null);
  const [openingUser, setOpeningUser] = useState(false);

  async function openEditUser(userId: number) {
    setOpeningUser(true);
    try {
      setEditingUser(await apiGetUserDetail(token, userId));
    } catch (e: unknown) {
      Alert.alert('Erro', errorMessage(e, 'Não foi possível abrir o usuário.'));
    } finally {
      setOpeningUser(false);
    }
  }

  if (editingUser) {
    return (
      <UserEditScreen
        key={editingUser.id}
        token={token}
        user={editingUser}
        onBack={() => setEditingUser(null)}
        onSaved={() => {
          setEditingUser(null);
          reload();
        }}
      />
    );
  }

  return (
    <FlatList
      data={users}
      keyExtractor={(item) => String(item.id)}
      contentContainerStyle={{ padding: 14 }}
      ListHeaderComponent={<BackLink label="← Voltar para Configurações" onPress={onBack} marginBottom={12} />}
      refreshControl={<RefreshControl refreshing={loading || openingUser} onRefresh={reload} />}
      renderItem={({ item }) => {
        const isAdm = item.role === 'admin';
        return (
          <TouchableOpacity style={styles.itemCard} activeOpacity={0.82} onPress={() => openEditUser(item.id)}>
            <View style={styles.itemTop}>
              <Text style={styles.yearTitle}>{item.name}</Text>
              <View style={[styles.badge, { backgroundColor: isAdm ? COLORS.orangeSoft : COLORS.bgSoft }]}>
                <Text style={[styles.badgeText, { color: isAdm ? COLORS.orangeText : COLORS.muted }]}>
                  {isAdm ? 'Admin' : 'Usuário'}
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
        );
      }}
    />
  );
}
