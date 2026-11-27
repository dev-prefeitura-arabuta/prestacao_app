import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  RefreshControl,
  SafeAreaView,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { FontAwesome5 } from '@expo/vector-icons';
import { AccountabilityDetail, apiConfirmAllSent, apiSubmitAccountability, isNetworkError } from '../../api';
import { OfflineBanner } from '../../components/OfflineBanner';
import { WaQueueBanner } from '../../components/WaQueueBanner';
import type { UploadQueueState } from '../../hooks/useUploadQueue';
import type { WaQueueState } from '../../hooks/useWaRetryQueue';
import { COLORS, getStatusStyle } from '../../theme/colors';
import { styles } from '../../theme/styles';
import { errorMessage, padItem } from '../../utils/format';
import { AccountabilityItemScreen } from './AccountabilityItemScreen';
import { NewAccountabilityModal } from './NewAccountabilityModal';
import { WhatsappReminderModal } from './WhatsappReminderModal';

/** Detalhe da prestação: lista de itens do checklist e, para o usuário, o envio final. */
export function AccountabilityDetailScreen({
  detail,
  token,
  apiUrl,
  isAdmin,
  loading,
  offlineSince,
  uploads,
  userId,
  wa,
  onReload,
  onClose,
  onSubmitted,
  initialItemNumber = null,
  onDuplicated,
}: {
  detail: AccountabilityDetail;
  token: string;
  apiUrl: string;
  isAdmin: boolean;
  loading: boolean;
  /** Preenchido quando a tela mostra a cópia salva no aparelho (sem conexão). */
  offlineSince: string | null;
  uploads: UploadQueueState;
  userId: number;
  wa: WaQueueState;
  onReload: () => Promise<void>;
  onClose: () => void;
  /** Chamado após confirmar o envio (o App fecha o detalhe e recarrega a lista). */
  onSubmitted: () => void;
  /** Abre direto um item (vindo da fila "Para avaliar"). */
  initialItemNumber?: number | null;
  /** Chamado após criar a cópia da prestação (admin). */
  onDuplicated?: () => void;
}) {
  const [selectedItemNumber, setSelectedItemNumber] = useState<number | null>(initialItemNumber);
  const [duplicateOpen, setDuplicateOpen] = useState(false);
  const [confirmingAll, setConfirmingAll] = useState(false);
  const sentCount = detail.items.filter((i) => i.status === 'enviado' && i.attachments.length > 0).length;
  const duplicateInitial = useMemo(
    () => ({
      title: `Cópia de ${detail.title}`.slice(0, 100),
      description: detail.description || '',
      enabledItems: detail.items.map((i) => i.item_number),
    }),
    [detail]
  );

  function handleConfirmAll() {
    Alert.alert('Confirmar enviados', `Confirmar de uma vez os ${sentCount} item(ns) enviado(s)? Revise os documentos antes.`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Confirmar todos',
        onPress: async () => {
          setConfirmingAll(true);
          try {
            const res = await apiConfirmAllSent(token, detail.id);
            Alert.alert('Pronto', res.message);
            await onReload();
          } catch (e: unknown) {
            Alert.alert('Atenção', isNetworkError(e) ? 'Sem conexão. Tente quando estiver online.' : errorMessage(e, 'Não foi possível confirmar.'));
          } finally {
            setConfirmingAll(false);
          }
        },
      },
    ]);
  }
  const [waModal, setWaModal] = useState<{ open: boolean; itemNumber: number | null }>({ open: false, itemNumber: null });
  const [submitting, setSubmitting] = useState(false);
  const [notifyAdmin, setNotifyAdmin] = useState(true);

  const openedItem =
    selectedItemNumber !== null ? detail.items.find((i) => i.item_number === selectedItemNumber) || null : null;

  function openReminder(itemNumber: number | null) {
    setWaModal({ open: true, itemNumber });
  }

  const queued = uploads.pendingFor(detail.id);
  const queuedPending = queued.filter((e) => e.status === 'pending').length;

  async function handleSubmit() {
    if (queuedPending > 0) {
      Alert.alert(
        'Anexos aguardando conexão',
        `Ainda há ${queuedPending} anexo(s) salvos no aparelho. Assim que eles forem enviados, você poderá confirmar a prestação.`
      );
      return;
    }
    setSubmitting(true);
    try {
      const res = await apiSubmitAccountability(token, detail.id, notifyAdmin);
      onSubmitted();
      Alert.alert(
        'Envio Confirmado',
        notifyAdmin && res.notified_via_api
          ? `${res.message}\n\nAdministrador notificado automaticamente pela API do WhatsApp.`
          : res.message
      );
    } catch (e: unknown) {
      Alert.alert(
        'Atenção',
        isNetworkError(e)
          ? 'Sem conexão com o servidor. A confirmação do envio precisa de internet: tente novamente quando estiver conectado.'
          : errorMessage(e, 'Não foi possível confirmar o envio.')
      );
    } finally {
      setSubmitting(false);
    }
  }

  const reminderModal = isAdmin ? (
    <WhatsappReminderModal
      visible={waModal.open}
      onClose={() => setWaModal((m) => ({ ...m, open: false }))}
      token={token}
      detail={detail}
      targetItemNum={waModal.itemNumber}
      onQueueChange={wa.setQueue}
    />
  ) : null;

  if (openedItem) {
    return (
      <>
        <AccountabilityItemScreen
          detail={detail}
          item={openedItem}
          token={token}
          apiUrl={apiUrl}
          isAdmin={isAdmin}
          loading={loading}
          offlineSince={offlineSince}
          uploads={uploads}
          userId={userId}
          onReload={onReload}
          onBack={() => setSelectedItemNumber(null)}
          onRemind={openReminder}
        />
        {reminderModal}
      </>
    );
  }

  const filledCount = detail.items.filter((i) => i.attachments.length > 0).length;
  const offlineBanner = (
    <OfflineBanner
      offlineSince={offlineSince}
      pendingCount={queuedPending}
      failedCount={queued.length - queuedPending}
      flushing={uploads.flushing}
      onRetry={uploads.flush}
    />
  );

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="light" />
      <View style={styles.header}>
        <TouchableOpacity onPress={onClose} style={styles.backBtn}>
          <Text style={styles.backBtnText}>← Voltar</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>
          {detail.title}
        </Text>
        <Text style={styles.headerSub}>Prazo: {detail.deadline}</Text>
      </View>

      <FlatList
        data={detail.items}
        keyExtractor={(item) => String(item.item_number)}
        contentContainerStyle={{ padding: 14, paddingBottom: 32 }}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={onReload} />}
        ListHeaderComponent={
          isAdmin ? (
            <View style={{ marginBottom: 10 }}>
              {offlineBanner}
              <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 }}>
                <Text style={[styles.menuSectionLabel, { marginBottom: 0 }]}>ITENS ({detail.items.length})</Text>
                <TouchableOpacity style={[styles.waSmallPillBtn, { marginTop: 0 }]} onPress={() => openReminder(null)}>
                  <FontAwesome5 name="whatsapp" size={13} color="#ffffff" />
                  <Text style={styles.waSmallPillText}>Cobrar</Text>
                </TouchableOpacity>
              </View>
              <WaQueueBanner wa={wa} subtitle="Aguardando WhatsApp voltar (reenvio automático ativo)" />
              <View style={{ flexDirection: 'row', gap: 8, marginTop: 10 }}>
                {sentCount > 0 && (
                  <TouchableOpacity
                    style={[adminActionBtn, { backgroundColor: '#16a34a' }, (confirmingAll || !!offlineSince) && { opacity: 0.6 }]}
                    onPress={handleConfirmAll}
                    disabled={confirmingAll || !!offlineSince}
                  >
                    {confirmingAll ? (
                      <ActivityIndicator size="small" color="#ffffff" />
                    ) : (
                      <>
                        <FontAwesome5 name="check-double" size={12} color="#ffffff" />
                        <Text style={adminActionText}>Confirmar {sentCount} enviado(s)</Text>
                      </>
                    )}
                  </TouchableOpacity>
                )}
                <TouchableOpacity style={[adminActionBtn, { backgroundColor: COLORS.shieldBlue }]} onPress={() => setDuplicateOpen(true)}>
                  <FontAwesome5 name="copy" size={12} color="#ffffff" />
                  <Text style={adminActionText}>Duplicar</Text>
                </TouchableOpacity>
              </View>
            </View>
          ) : (
            offlineBanner
          )
        }
        renderItem={({ item }) => {
          const st = getStatusStyle(item.status);
          const itemQueued = uploads.pendingFor(detail.id, item.item_number).length;
          return (
            <TouchableOpacity
              style={styles.itemCard}
              activeOpacity={0.82}
              onPress={() => setSelectedItemNumber(item.item_number)}
            >
              <View style={styles.itemTop}>
                <Text style={styles.itemNum}>Item {padItem(item.item_number)}</Text>
                <View style={[styles.badge, { backgroundColor: st.bg }]}>
                  <Text style={[styles.badgeText, { color: st.fg }]}>{st.label}</Text>
                </View>
              </View>
              <Text style={styles.itemTitle}>{item.title}</Text>
              {item.rejection_reason ? (
                <Text style={styles.rejectText}>Motivo rejeição: {item.rejection_reason}</Text>
              ) : null}

              <View style={styles.itemOpenRow}>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                  <FontAwesome5 name="paperclip" size={12} color="#475569" />
                  <Text style={[styles.attCount, { marginTop: 0 }]}>
                    {item.attachments.length} arquivo(s) anexado(s)
                    {itemQueued > 0 ? ` • ${itemQueued} aguardando conexão` : ''}
                  </Text>
                </View>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 5 }}>
                  <Text style={styles.itemOpenCue}>Abrir e ver anexos</Text>
                  <FontAwesome5 name="chevron-right" size={11} color={COLORS.shieldBlue} />
                </View>
              </View>
            </TouchableOpacity>
          );
        }}
        ListFooterComponent={
          !isAdmin ? (
            <View style={styles.submitFooterBox}>
              {queuedPending > 0 ? (
                <View style={styles.submitPendingBanner}>
                  <FontAwesome5 name="cloud-upload-alt" size={15} color="#b45309" />
                  <Text style={styles.submitPendingText}>
                    {queuedPending} anexo(s) aguardando conexão. A confirmação do envio fica disponível depois que eles
                    chegarem ao servidor.
                  </Text>
                </View>
              ) : detail.can_submit ? (
                <>
                  <Text style={styles.submitFooterTitle}>Todos os anexos foram adicionados!</Text>
                  <Text style={styles.submitFooterDesc}>
                    Revise os arquivos anexados e confirme o envio para encaminhar ao Administrador.
                  </Text>

                  <TouchableOpacity
                    style={[
                      styles.permCheckRow,
                      notifyAdmin && { borderColor: '#86efac', backgroundColor: '#f0fdf4' },
                      { marginBottom: 12 },
                    ]}
                    onPress={() => setNotifyAdmin((p) => !p)}
                  >
                    <FontAwesome5
                      name={notifyAdmin ? 'check-square' : 'square'}
                      size={17}
                      color={notifyAdmin ? '#16a34a' : '#94a3b8'}
                      solid={notifyAdmin}
                    />
                    <Text style={styles.permCheckText}>
                      <Text style={{ fontWeight: '800', color: '#166534' }}>Avisar Administrador no WhatsApp</Text>{' '}
                      (ex.: &quot;Todos os anexos foram enviados na prestação&quot;)
                    </Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[styles.submitConfirmBtn, submitting && { opacity: 0.7 }]}
                    onPress={handleSubmit}
                    disabled={submitting}
                  >
                    {submitting ? (
                      <ActivityIndicator color="#ffffff" />
                    ) : (
                      <>
                        <FontAwesome5 name="paper-plane" size={15} color="#ffffff" />
                        <Text style={styles.submitConfirmBtnText}>Confirmar Envio da Prestação</Text>
                      </>
                    )}
                  </TouchableOpacity>
                </>
              ) : (
                <View style={styles.submitPendingBanner}>
                  <FontAwesome5 name="info-circle" size={15} color={COLORS.shieldBlue} />
                  <Text style={styles.submitPendingText}>
                    Adicione pelo menos 1 anexo em todos os seus itens ({filledCount}/{detail.items.length}) para liberar
                    o botão de confirmar envio.
                  </Text>
                </View>
              )}
            </View>
          ) : undefined
        }
      />

      {reminderModal}

      {isAdmin && (
        <NewAccountabilityModal
          visible={duplicateOpen}
          onClose={() => setDuplicateOpen(false)}
          token={token}
          initial={duplicateInitial}
          onCreated={() => onDuplicated?.()}
        />
      )}
    </SafeAreaView>
  );
}

const adminActionBtn = {
  flex: 1,
  flexDirection: 'row' as const,
  alignItems: 'center' as const,
  justifyContent: 'center' as const,
  gap: 6,
  paddingVertical: 10,
  borderRadius: 9,
};

const adminActionText = { color: '#ffffff', fontWeight: '700' as const, fontSize: 12 };
