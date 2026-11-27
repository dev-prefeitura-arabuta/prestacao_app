import React, { useState } from 'react';
import { FlatList, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { AccountabilitySummary, apiGetAccountabilities } from '../../api';
import { OfflineBanner } from '../../components/OfflineBanner';
import { WaQueueBanner } from '../../components/WaQueueBanner';
import type { UploadQueueState } from '../../hooks/useUploadQueue';
import { Cached, loadWithCache } from '../../offline/screenCache';
import { useScreenData } from '../../hooks/useScreenData';
import type { WaQueueState } from '../../hooks/useWaRetryQueue';
import { styles } from '../../theme/styles';
import { NewAccountabilityModal } from './NewAccountabilityModal';

/** Lista de prestações em aberto (admin vê o botão "Novo" e a fila de cobranças). */
export function AccountabilitiesScreen({
  token,
  isAdmin,
  userId,
  uploads,
  wa,
  openingDetail,
  onOpenDetail,
}: {
  token: string;
  isAdmin: boolean;
  userId: number;
  uploads: UploadQueueState;
  wa: WaQueueState;
  openingDetail: boolean;
  onOpenDetail: (id: number) => void;
}) {
  const { data: cached, loading, reload } = useScreenData<Cached<AccountabilitySummary[]>>(
    () => loadWithCache(userId, 'list', () => apiGetAccountabilities(token)),
    { data: [], offlineSince: null }
  );
  const data = cached.data;
  const pendingCount = uploads.items.filter((e) => e.status === 'pending').length;
  const failedCount = uploads.items.length - pendingCount;
  const offlineBanner = (
    <OfflineBanner
      offlineSince={cached.offlineSince}
      pendingCount={pendingCount}
      failedCount={failedCount}
      flushing={uploads.flushing}
      onRetry={uploads.flush}
    />
  );
  const [newModalOpen, setNewModalOpen] = useState(false);

  return (
    <>
      <FlatList
        data={data}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: 14 }}
        refreshControl={<RefreshControl refreshing={loading || openingDetail} onRefresh={reload} />}
        ListHeaderComponent={
          isAdmin ? (
            <View>
              {offlineBanner}
              <View style={styles.listActionHeader}>
                <Text style={[styles.menuSectionLabel, { marginBottom: 0 }]}>PRESTAÇÕES EM ABERTO ({data.length})</Text>
                <TouchableOpacity style={styles.newAccBtn} onPress={() => setNewModalOpen(true)}>
                  <FontAwesome5 name="plus" size={12} color="#ffffff" />
                  <Text style={styles.newAccBtnText}>Novo</Text>
                </TouchableOpacity>
              </View>
              <WaQueueBanner
                wa={wa}
                subtitle="Pacote pronto para envio quando o WhatsApp voltar"
                style={{ marginTop: 0, marginBottom: 12 }}
              />
            </View>
          ) : (
            offlineBanner
          )
        }
        ListEmptyComponent={<Text style={styles.emptyText}>Nenhuma prestação em andamento encontrada.</Text>}
        renderItem={({ item }) => (
          <TouchableOpacity style={styles.accCard} onPress={() => onOpenDetail(item.id)}>
            {uploads.pendingFor(item.id).length > 0 ? (
              <Text style={{ fontSize: 11, fontWeight: '700', color: '#b45309', marginBottom: 4 }}>
                {uploads.pendingFor(item.id).length} anexo(s) aguardando conexão
              </Text>
            ) : null}
            <View style={styles.itemTop}>
              <Text style={styles.accId}>#{item.id}</Text>
              <Text style={styles.accDeadline}>Prazo: {item.deadline}</Text>
            </View>
            <Text style={styles.accTitle}>{item.title}</Text>
            <View style={styles.progressTrack}>
              <View style={[styles.progressFill, { width: `${item.progress}%` }]} />
            </View>
            <Text style={styles.accMeta}>
              {item.progress}% • {item.filled_items}/{item.total_items} itens • {item.attachments_count} anexo(s)
            </Text>
          </TouchableOpacity>
        )}
      />

      {isAdmin && (
        <NewAccountabilityModal
          visible={newModalOpen}
          onClose={() => setNewModalOpen(false)}
          token={token}
          onCreated={reload}
        />
      )}
    </>
  );
}
