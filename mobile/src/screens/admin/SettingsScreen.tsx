import React from 'react';
import { ScrollView, Text } from 'react-native';
import { MenuButton } from '../../components/ui';
import { AppScreen } from '../../navigation/types';
import { styles } from '../../theme/styles';

/** Hub de configurações do administrador. */
export function SettingsScreen({ onNavigate }: { onNavigate: (target: AppScreen) => void }) {
  return (
    <ScrollView contentContainerStyle={styles.menuScroll}>
      <Text style={styles.menuSectionLabel}>OPÇÕES DE CONFIGURAÇÃO</Text>

      <MenuButton
        icon="user-circle"
        iconSize={17}
        title="Meu Perfil"
        subtitle="Dados da sua conta, e-mail e WhatsApp"
        onPress={() => onNavigate('profile')}
      />

      <MenuButton
        icon="users-cog"
        title="Usuários e Permissões"
        subtitle="Gerenciar contas cadastradas e perfis"
        onPress={() => onNavigate('users')}
      />
    </ScrollView>
  );
}
