import React from 'react';
import { Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { AppScreen } from '../navigation/types';
import { COLORS } from '../theme/colors';
import { styles } from '../theme/styles';

function NavItem({
  icon,
  label,
  active,
  onPress,
  color,
}: {
  icon: string;
  label: string;
  active?: boolean;
  onPress: () => void;
  /** Cor fixa (ex.: vermelho do "Sair"); ignora o estado ativo. */
  color?: string;
}) {
  const iconColor = color ?? (active ? COLORS.orange : COLORS.muted);
  return (
    <TouchableOpacity style={[styles.bottomNavItem, active && styles.bottomNavItemActive]} onPress={onPress}>
      <FontAwesome5 name={icon} size={18} color={iconColor} style={{ marginBottom: 4 }} />
      <Text style={[styles.bottomNavLabel, active && styles.bottomNavLabelActive, color ? { color } : null]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

/** Rodapé fixo de navegação (substitui a sidebar do desktop). */
export function BottomNav({
  screen,
  isAdmin,
  onNavigate,
  onLogout,
}: {
  screen: AppScreen;
  isAdmin: boolean;
  onNavigate: (target: AppScreen) => void;
  onLogout: () => void;
}) {
  const inMenu = screen === 'menu' || screen === 'annual_history' || screen === 'audit';
  const inSettings = screen === 'settings' || screen === 'users' || screen === 'profile';

  return (
    <View style={styles.bottomNav}>
      {isAdmin && <NavItem icon="bars" label="Menu" active={inMenu} onPress={() => onNavigate('menu')} />}

      <NavItem
        icon="file-alt"
        label="Prestações"
        active={screen === 'accountabilities'}
        onPress={() => onNavigate('accountabilities')}
      />

      {isAdmin ? (
        <NavItem icon="cog" label="Configurações" active={inSettings} onPress={() => onNavigate('settings')} />
      ) : (
        <NavItem
          icon="user-circle"
          label="Meu Perfil"
          active={screen === 'profile'}
          onPress={() => onNavigate('profile')}
        />
      )}

      <NavItem icon="sign-out-alt" label="Sair" color="#b91c1c" onPress={onLogout} />
    </View>
  );
}
