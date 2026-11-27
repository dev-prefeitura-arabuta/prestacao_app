import React, { useState } from 'react';
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
import { AccountabilityDetail, apiSubmitAccountability } from '../../api';
import { WaQueueBanner } from '../../components/WaQueueBanner';
import type { WaQueueState } from '../../hooks/useWaRetryQueue';
import { COLORS, getStatusStyle } from '../../theme/colors';
import { styles } from '../../theme/styles';
import { errorMessage, padItem } from '../../utils/format';
import { AccountabilityItemScreen } from './AccountabilityItemScreen';
import { WhatsappReminderModal } from './WhatsappReminderModal';

/** Detalhe da prestação: lista de itens do checklist e, para o usuário, o envio final. */
export function AccountabilityDetailScreen({
  detail,
  token,
  apiUrl,
  isAdmin,
  loading,
  wa,
  onReload,
  onClose,
  onSubmitted,
}: {
  detail: AccountabilityDetail;
  token: string;
  apiUrl: string;
  isAdmin: boolean;
  loading: boolean;
  wa: WaQueueState;
  onReload: () => Promise<void>;
  onClose: () => void;
  /** Chamado após confirmar o envio (o App fecha o detalhe e recarrega a lista). */
  onSubmitted: () => void;
}) {
  const [selectedItemNumber, setSelectedItemNumber] = useState<number | null>(null);
  const [waModal, setWaModal] = useState<{ open: boolean; itemNumber: number | null }>({ open: false, itemNumber: null });
  const [submitting, setSubmitting] = useState(false);
  const [notifyAdmin, setNotifyAdmin] = useState(true);

  const openedItem =
    selectedItemNumber !== null ? detail.items.find((i) => i.item_number === selectedItemNumber) || null : null;

  function openReminder(itemNumber: number | null) {
    setWaModal({ open: true, itemNumber });
  }

  async function handleSubmit() {
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
      Alert.alert('Atenção', errorMessage(e, 'Não foi possível confirmar o envio.'));
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
          onReload={onReload}
          onBack={() => setSelectedItemNumber(null)}
          onRemind={openReminder}
        />
        {reminderModal}
      </>
    );
  }

  const filledCount = detail.items.filter((i) => i.attachments.length > 0).length;

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
              <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 }}>
                <Text style={[styles.menuSectionLabel, { marginBottom: 0 }]}>ITENS ({detail.items.length})</Text>
                <TouchableOpacity style={[styles.waSmallPillBtn, { marginTop: 0 }]} onPress={() => openReminder(null)}>
                  <FontAwesome5 name="whatsapp" size={13} color="#ffffff" />
                  <Text style={styles.waSmallPillText}>Cobrar</Text>
                </TouchableOpacity>
              </View>
              <WaQueueBanner wa={wa} subtitle="Aguardando WhatsApp voltar (reenvio automático ativo)" />
            </View>
          ) : undefined
        }
        renderItem={({ item }) => {
          const st = getStatusStyle(item.status);
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
                  <Text style={[styles.attCount, { marginTop: 0 }]}>{item.attachments.length} arquivo(s) anexado(s)</Text>
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
              {detail.can_submit ? (
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
    </SafeAreaView>
  );
}
