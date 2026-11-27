import React from 'react';
import { FlatList, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { ReviewQueueRow, apiGetReviewQueue } from '../../api';
import { useScreenData } from '../../hooks/useScreenData';
import { COLORS } from '../../theme/colors';
import { styles } from '../../theme/styles';
import { padItem } from '../../utils/format';

/** Caixa de entrada do admin: itens enviados aguardando avaliação, de todas as prestações. */
export function ReviewQueueScreen({
  token,
  opening,
  onOpenItem,
}: {
  token: string;
  opening: boolean;
  onOpenItem: (accountabilityId: number, itemNumber: number) => void;
}) {
  const { data, loading, reload } = useScreenData<ReviewQueueRow[]>(() => apiGetReviewQueue(token), []);

  return (
    <FlatList
      data={data}
      keyExtractor={(row) => `${row.accountability_id}-${row.item_number}`}
      contentContainerStyle={{ padding: 14 }}
      refreshControl={<RefreshControl refreshing={loading || opening} onRefresh={reload} />}
      ListHeaderComponent={
        <Text style={styles.menuSectionLabel}>AGUARDANDO AVALIAÇÃO ({data.length}) • MAIS ANTIGOS PRIMEIRO</Text>
      }
      ListEmptyComponent={<Text style={styles.emptyText}>Tudo em dia! Nenhum item aguardando avaliação.</Text>}
      renderItem={({ item: row }) => (
        <TouchableOpacity style={styles.accCard} onPress={() => onOpenItem(row.accountability_id, row.item_number)}>
          <View style={styles.itemTop}>
            <Text style={styles.accId}>Item {padItem(row.item_number)}</Text>
            <Text style={styles.accDeadline}>Enviado: {row.sent_at || '—'}</Text>
          </View>
          <Text style={styles.accTitle}>{row.accountability_title}</Text>
          <Text style={[styles.accMeta, { marginTop: 2 }]} numberOfLines={2}>
            {row.item_title}
          </Text>
          <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 8 }}>
            <Text style={styles.accMeta}>
              {row.uploaders.join(', ')} • {row.attachments_count} anexo(s)
              {row.is_paused ? ' • Pausada' : ''}
            </Text>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 5 }}>
              <Text style={{ color: COLORS.shieldBlue, fontWeight: '700', fontSize: 12 }}>Avaliar</Text>
              <FontAwesome5 name="chevron-right" size={11} color={COLORS.shieldBlue} />
            </View>
          </View>
        </TouchableOpacity>
      )}
    />
  );
}
