import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Linking, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import {
  AccountabilityDetail,
  MobileUser,
  PendingWaReminder,
  apiGetUsers,
  apiTrySendWaReminder,
  enqueueWaReminder,
} from '../../api';
import { FormModal } from '../../components/FormModal';
import { styles } from '../../theme/styles';
import { padItem } from '../../utils/format';

type ReminderTarget = { id: number; name: string; whatsapp: string; pendingItems: number[] };

function buildDefaultMessage(
  detail: AccountabilityDetail,
  targetName: string,
  itemNumber: number | null,
  pendingNums: number[]
): string {
  const header =
    `Olá, ${targetName}! 👋\n\n` +
    `Lembrete da *Prestação de Contas: ${detail.title || 'Prestação de Contas'}* (Prazo: ${detail.deadline || 'Não definido'}).\n\n`;
  const footer =
    'Por favor, acesse o aplicativo Prestação de Contas para anexar os documentos e confirmar o envio. Obrigado!';

  if (itemNumber !== null) {
    const found = detail.items.find((i) => i.item_number === itemNumber);
    const label = found?.title ? ` — ${found.title}` : '';
    return `${header}Ainda aguardamos o envio dos anexos do *Item ${padItem(itemNumber)}${label}*.\n\n${footer}`;
  }
  const itensStr =
    pendingNums.length > 0
      ? `Itens pendentes: ${pendingNums.map(padItem).join(', ')}.`
      : 'Você possui itens pendentes de envio nesta prestação de contas.';
  return `${header}${itensStr}\n\n${footer}`;
}

/** Usuários a cobrar: os responsáveis pelos itens pendentes ou, se não houver, todos os não-admin. */
function buildTargets(
  detail: AccountabilityDetail,
  itemNumber: number | null,
  fallbackUsers: MobileUser[]
): ReminderTarget[] {
  const map = new Map<number, ReminderTarget>();
  const relevant =
    itemNumber !== null
      ? detail.items.filter((i) => i.item_number === itemNumber)
      : detail.items.filter((i) => i.status !== 'confirmado');

  for (const it of relevant) {
    for (const u of it.assigned_users || []) {
      const cur = map.get(u.id) || { id: u.id, name: u.name, whatsapp: u.whatsapp, pendingItems: [] };
      if (!cur.pendingItems.includes(it.item_number)) cur.pendingItems.push(it.item_number);
      map.set(u.id, cur);
    }
  }

  if (map.size === 0) {
    for (const u of fallbackUsers.filter((x) => x.role !== 'admin')) {
      map.set(u.id, { id: u.id, name: u.name, whatsapp: u.whatsapp || '', pendingItems: [] });
    }
  }
  return Array.from(map.values());
}

/** Modal (admin) para cobrar/lembrar usuários pelo WhatsApp; falhas vão para a fila de reenvio. */
export function WhatsappReminderModal({
  visible,
  onClose,
  token,
  detail,
  targetItemNum,
  onQueueChange,
}: {
  visible: boolean;
  onClose: () => void;
  token: string;
  detail: AccountabilityDetail;
  /** null = cobrar todos os itens pendentes. */
  targetItemNum: number | null;
  onQueueChange: (queue: PendingWaReminder[]) => void;
}) {
  const [customMsg, setCustomMsg] = useState('');
  const [sendingUserId, setSendingUserId] = useState<number | null>(null);
  const [fallbackUsers, setFallbackUsers] = useState<MobileUser[]>([]);

  useEffect(() => {
    if (!visible) return;
    setCustomMsg('');
    if (fallbackUsers.length === 0) {
      apiGetUsers(token)
        .then(setFallbackUsers)
        .catch(() => {});
    }
  }, [visible]);

  async function handleSend(target: ReminderTarget, mode: 'phone' | 'api') {
    let digits = (target.whatsapp || '').replace(/\D/g, '');
    if (digits.length < 10) {
      Alert.alert('Sem WhatsApp', `${target.name} não possui número de WhatsApp cadastrado. Cadastre em Usuários e Permissões.`);
      return;
    }
    if (digits.length <= 11 && !digits.startsWith('55')) {
      digits = '55' + digits;
    }

    const msgText = customMsg.trim() || buildDefaultMessage(detail, target.name, targetItemNum, target.pendingItems);

    if (mode === 'phone') {
      try {
        await Linking.openURL(`https://wa.me/${digits}?text=${encodeURIComponent(msgText)}`);
      } catch {
        Alert.alert('Erro', 'Não foi possível abrir o WhatsApp no celular.');
      }
      return;
    }

    setSendingUserId(target.id);
    try {
      const res = await apiTrySendWaReminder(token, detail.id, {
        user_id: target.id,
        item_number: targetItemNum ?? undefined,
        custom_message: msgText,
      });
      if (res.delivered) {
        Alert.alert('Enviado (HTTP 200)', res.message);
        return;
      }
      const queue = await enqueueWaReminder({
        accountability_id: detail.id,
        accountability_title: detail.title,
        user_id: target.id,
        user_name: target.name,
        item_number: targetItemNum ?? undefined,
        custom_message: msgText,
        last_error: res.message,
      });
      onQueueChange(queue);
      Alert.alert(
        'Salvo em Cache',
        `O WhatsApp não retornou status 200 (${res.message}). A cobrança para ${target.name} foi salva na fila (${queue.length} pendente(s)) e será reenviada automaticamente quando o WhatsApp voltar.`
      );
    } finally {
      setSendingUserId(null);
    }
  }

  const targets = buildTargets(detail, targetItemNum, fallbackUsers);

  return (
    <FormModal
      visible={visible}
      onClose={onClose}
      title={targetItemNum !== null ? `Cobrar Item ${padItem(targetItemNum)}` : 'Cobrar / Lembrar no WhatsApp'}
      headerLeft={<FontAwesome5 name="whatsapp" size={18} color="#16a34a" />}
    >
      <ScrollView keyboardShouldPersistTaps="handled">
        <Text style={styles.label}>Mensagem personalizada (opcional)</Text>
        <TextInput
          style={[styles.input, { minHeight: 64, textAlignVertical: 'top', marginBottom: 12 }]}
          value={customMsg}
          onChangeText={setCustomMsg}
          multiline
          placeholder="Deixe em branco para usar o texto automático de cobrança/lembrete."
          placeholderTextColor="#94a3b8"
        />

        <Text style={styles.profileSectionTitle}>SELECIONE O USUÁRIO PARA COBRAR</Text>

        {targets.length === 0 ? (
          <Text style={[styles.emptyText, { marginTop: 12, marginBottom: 12 }]}>
            Nenhum usuário com WhatsApp disponível para cobrança.
          </Text>
        ) : (
          targets.map((t) => {
            const hasPhone = (t.whatsapp || '').replace(/\D/g, '').length >= 10;
            const sending = sendingUserId === t.id;
            return (
              <View key={t.id} style={styles.waUserCard}>
                <Text style={styles.waUserName}>{t.name}</Text>
                <Text style={styles.waUserSub}>WhatsApp: {t.whatsapp || 'Não cadastrado'}</Text>
                {t.pendingItems.length > 0 && (
                  <Text style={styles.waUserItems}>Itens: {t.pendingItems.map(padItem).join(', ')}</Text>
                )}

                <View style={{ flexDirection: 'row', gap: 8, marginTop: 10 }}>
                  <TouchableOpacity
                    style={[styles.waSendPhoneBtn, (!hasPhone || sending) && { opacity: 0.6 }]}
                    onPress={() => handleSend(t, 'api')}
                    disabled={sending}
                  >
                    {sending ? (
                      <ActivityIndicator size="small" color="#ffffff" />
                    ) : (
                      <>
                        <FontAwesome5 name="whatsapp" size={14} color="#ffffff" />
                        <Text style={styles.waSendBtnText}>Enviar Cobrança (API WhatsApp)</Text>
                      </>
                    )}
                  </TouchableOpacity>
                </View>
              </View>
            );
          })
        )}
      </ScrollView>
    </FormModal>
  );
}
