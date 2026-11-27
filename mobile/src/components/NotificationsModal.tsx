import React from 'react';
import { ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { AdminNotificationItem } from '../api';
import type { AdminNotificationsState } from '../hooks/useAdminNotifications';
import { COLORS } from '../theme/colors';
import { styles } from '../theme/styles';
import { FormModal } from './FormModal';

/** Modal do sininho: liga/desliga alertas, marca como lidas e abre a prestação relacionada. */
export function NotificationsModal({
  visible,
  onClose,
  notif,
  onOpenItem,
}: {
  visible: boolean;
  onClose: () => void;
  notif: AdminNotificationsState;
  onOpenItem: (it: AdminNotificationItem) => void;
}) {
  return (
    <FormModal
      visible={visible}
      onClose={onClose}
      title="Notificações"
      headerLeft={
        <FontAwesome5
          name={notif.enabled ? 'bell' : 'bell-slash'}
          size={16}
          color={notif.enabled ? COLORS.orange : COLORS.muted}
        />
      }
    >
      <View style={styles.notifToggleRow}>
        <Text style={{ fontSize: 12, fontWeight: '700', color: COLORS.text, flex: 1 }}>
          Alertas de atividades concluídas
        </Text>
        <TouchableOpacity
          style={[styles.notifTogglePill, notif.enabled && styles.notifTogglePillActive]}
          onPress={notif.toggle}
        >
          <Text style={[styles.notifTogglePillText, notif.enabled && styles.notifTogglePillTextActive]}>
            {notif.enabled ? 'Ativas' : 'Pausadas'}
          </Text>
        </TouchableOpacity>
      </View>

      {notif.unread > 0 && (
        <TouchableOpacity style={{ alignSelf: 'flex-end', marginBottom: 8 }} onPress={notif.markAllRead}>
          <Text style={{ fontSize: 12, fontWeight: '700', color: COLORS.shieldBlue }}>Marcar todas como lidas</Text>
        </TouchableOpacity>
      )}

      <ScrollView style={{ maxHeight: 360 }}>
        {notif.items.length === 0 ? (
          <Text style={[styles.emptyText, { marginTop: 18, marginBottom: 18 }]}>
            Nenhuma atividade concluída registrada ainda.
          </Text>
        ) : (
          notif.items.map((it) => (
            <TouchableOpacity
              key={it.id}
              style={[styles.notifItemCard, !it.is_read && styles.notifItemCardUnread]}
              onPress={() => onOpenItem(it)}
            >
              <Text style={styles.notifItemTitle}>{it.title}</Text>
              <Text style={styles.notifItemMsg}>{it.message}</Text>
              <Text style={styles.notifItemTime}>{it.created_at}</Text>
            </TouchableOpacity>
          ))
        )}
      </ScrollView>
    </FormModal>
  );
}
