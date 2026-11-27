import React, { useState } from 'react';
import { ActivityIndicator, Alert, FlatList, Linking, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { PendingPersonRow, apiGetPendingByUser, apiRemindWhatsapp, isNetworkError } from '../../api';
import { useScreenData } from '../../hooks/useScreenData';
import { styles } from '../../theme/styles';
import { errorMessage, padItem } from '../../utils/format';

const STATUS_LABEL: Record<string, { text: string; color: string }> = {
  pendente: { text: 'Pendente', color: '#475569' },
  rejeitado: { text: 'Rejeitado', color: '#b91c1c' },
  prazo_vencido: { text: 'Prazo vencido', color: '#c2410c' },
};

/** Painel de pendências por pessoa, com cobrança agrupada no WhatsApp. */
export function PendingByUserScreen({
  token,
  onOpenItem,
}: {
  token: string;
  onOpenItem: (accountabilityId: number, itemNumber: number) => void;
}) {
  const { data, loading, reload } = useScreenData<PendingPersonRow[]>(() => apiGetPendingByUser(token), []);
  const [remindingId, setRemindingId] = useState<number | null>(null);

  async function remind(row: PendingPersonRow) {
    setRemindingId(row.user.id);
    try {
      // Uma única mensagem com todas as pendências da pessoa (registrada no histórico da 1ª prestação)
      const res = await apiRemindWhatsapp(token, row.items[0].accountability_id, {
        user_id: row.user.id,
        custom_message: row.reminder_text,
      });
      if (res.sent_via_api) {
        Alert.alert('Cobrança enviada', res.message);
      } else if (res.wa_url) {
        await Linking.openURL(res.wa_url);
      }
    } catch (e: unknown) {
      Alert.alert('Atenção', isNetworkError(e) ? 'Sem conexão. Tente quando estiver online.' : errorMessage(e, 'Não foi possível cobrar.'));
    } finally {
      setRemindingId(null);
    }
  }

  return (
    <FlatList
      data={data}
      keyExtractor={(row) => String(row.user.id)}
      contentContainerStyle={{ padding: 14 }}
      refreshControl={<RefreshControl refreshing={loading} onRefresh={reload} />}
      ListHeaderComponent={<Text style={styles.menuSectionLabel}>PESSOAS COM PENDÊNCIAS ({data.length})</Text>}
      ListEmptyComponent={<Text style={styles.emptyText}>Ninguém com pendências nas prestações em andamento.</Text>}
      renderItem={({ item: row }) => (
        <View style={styles.accCard}>
          <View style={styles.itemTop}>
            <Text style={styles.accTitle}>{row.user.name}</Text>
            {row.overdue_count > 0 ? (
              <Text style={{ color: '#c2410c', fontWeight: '800', fontSize: 12 }}>{row.overdue_count} atrasado(s)</Text>
            ) : null}
          </View>
          <Text style={[styles.accMeta, { marginBottom: 6 }]}>{row.items.length} item(ns) pendente(s)</Text>

          {row.items.map((it) => {
            const st = STATUS_LABEL[it.status] || STATUS_LABEL.pendente;
            return (
              <TouchableOpacity
                key={`${it.accountability_id}-${it.item_number}`}
                onPress={() => onOpenItem(it.accountability_id, it.item_number)}
                style={{ paddingVertical: 6, borderTopWidth: 1, borderTopColor: '#f1f5f9' }}
              >
                <Text style={{ fontSize: 12, color: '#0f172a' }} numberOfLines={1}>
                  <Text style={{ fontWeight: '800' }}>Item {padItem(it.item_number)}</Text> • {it.accountability_title}
                </Text>
                <Text style={{ fontSize: 11, color: st.color }}>
                  {st.text}
                  {it.has_files ? ' • já anexou, falta enviar' : ''}
                  {it.deadline ? ` • prazo ${it.deadline}` : ''}
                </Text>
              </TouchableOpacity>
            );
          })}

          <TouchableOpacity
            style={[styles.waSmallPillBtn, { alignSelf: 'flex-start', marginTop: 10 }, !row.user.has_whatsapp && { opacity: 0.5 }]}
            disabled={!row.user.has_whatsapp || remindingId !== null}
            onPress={() => remind(row)}
          >
            {remindingId === row.user.id ? (
              <ActivityIndicator size="small" color="#ffffff" />
            ) : (
              <>
                <FontAwesome5 name="whatsapp" size={13} color="#ffffff" />
                <Text style={styles.waSmallPillText}>{row.user.has_whatsapp ? 'Cobrar tudo no WhatsApp' : 'Sem WhatsApp cadastrado'}</Text>
              </>
            )}
          </TouchableOpacity>
        </View>
      )}
    />
  );
}
