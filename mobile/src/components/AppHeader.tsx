import React from 'react';
import { Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { styles } from '../theme/styles';
import { BrandShieldIcon } from './Brand';

/** Cabeçalho azul das telas autenticadas, com o sininho de notificações (admin). */
export function AppHeader({
  title,
  userName,
  bell,
}: {
  title: string;
  userName: string;
  bell?: { enabled: boolean; unread: number; onPress: () => void };
}) {
  return (
    <View style={styles.header}>
      <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10 }}>
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10, flex: 1 }}>
          <BrandShieldIcon size={30} />
          <View style={{ flex: 1 }}>
            <Text style={styles.headerTitle} numberOfLines={1}>
              {title}
            </Text>
            <Text style={styles.headerSub}>Olá, {userName}</Text>
          </View>
        </View>

        {bell && (
          <TouchableOpacity style={styles.notifBellBtn} onPress={bell.onPress}>
            <FontAwesome5
              name={bell.enabled ? 'bell' : 'bell-slash'}
              size={18}
              color={bell.enabled ? '#ffffff' : '#94a3b8'}
            />
            {bell.enabled && bell.unread > 0 && (
              <View style={styles.notifBadge}>
                <Text style={styles.notifBadgeText}>{bell.unread > 99 ? '99+' : String(bell.unread)}</Text>
              </View>
            )}
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}
