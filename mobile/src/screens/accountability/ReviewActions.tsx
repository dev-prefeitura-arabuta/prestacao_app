import React, { useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { apiSetItemStatus, isNetworkError } from '../../api';
import { FormModal } from '../../components/FormModal';
import { styles } from '../../theme/styles';
import { errorMessage, padItem } from '../../utils/format';

const DEFAULT_PRESETS = [
  'Documento ilegível ou com baixa qualidade.',
  'Falta assinatura ou carimbo.',
  'Documento de outro período.',
  'O arquivo não corresponde a este item.',
];

/**
 * Avaliação do item pelo administrador: Confirmar ou Rejeitar com motivo
 * (motivos prontos preenchem o texto, que pode ser editado). Na rejeição, o servidor avisa quem enviou.
 */
export function ReviewActions({
  token,
  accountabilityId,
  itemNumber,
  status,
  hasAttachments,
  presets,
  offline,
  onChanged,
}: {
  token: string;
  accountabilityId: number;
  itemNumber: number;
  status: string;
  hasAttachments: boolean;
  presets?: string[];
  /** Tela exibindo dados salvos (sem conexão): avaliação desativada */
  offline: boolean;
  onChanged: () => Promise<void> | void;
}) {
  const [saving, setSaving] = useState<'confirmado' | 'rejeitado' | null>(null);
  const [rejectOpen, setRejectOpen] = useState(false);
  const [reason, setReason] = useState('');
  const presetList = presets && presets.length > 0 ? presets : DEFAULT_PRESETS;

  async function apply(newStatus: 'confirmado' | 'rejeitado', rejectionReason = '') {
    setSaving(newStatus);
    try {
      const res = await apiSetItemStatus(token, accountabilityId, itemNumber, newStatus, rejectionReason);
      setRejectOpen(false);
      setReason('');
      Alert.alert(newStatus === 'confirmado' ? 'Item confirmado' : 'Item rejeitado', res.message);
      await onChanged();
    } catch (e: unknown) {
      Alert.alert(
        'Atenção',
        isNetworkError(e) ? 'Sem conexão. A avaliação precisa de internet.' : errorMessage(e, 'Não foi possível avaliar o item.')
      );
    } finally {
      setSaving(null);
    }
  }

  function confirmItem() {
    Alert.alert('Confirmar documento', `Confirmar o Item ${padItem(itemNumber)}? O usuário não poderá mais alterá-lo.`, [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Confirmar', onPress: () => apply('confirmado') },
    ]);
  }

  function addPreset(text: string) {
    setReason((prev) => (prev.trim() ? `${prev.trim()} ${text}` : text));
  }

  if (!hasAttachments) {
    return (
      <View style={[styles.itemCard, { backgroundColor: '#f8fafc' }]}>
        <Text style={{ fontSize: 12, color: '#64748b' }}>Aguardando anexos para liberar a confirmação ou rejeição.</Text>
      </View>
    );
  }

  const disabled = offline || saving !== null;

  return (
    <View style={styles.itemCard}>
      <Text style={[styles.menuSectionLabel, { marginBottom: 8 }]}>AVALIAÇÃO DO DOCUMENTO</Text>
      {offline ? (
        <Text style={{ fontSize: 12, color: '#92400e', marginBottom: 8 }}>Sem conexão: a avaliação fica disponível quando a internet voltar.</Text>
      ) : null}
      <View style={{ flexDirection: 'row', gap: 8 }}>
        <TouchableOpacity
          style={[reviewBtn, { backgroundColor: '#16a34a' }, (disabled || status === 'confirmado') && { opacity: 0.5 }]}
          onPress={confirmItem}
          disabled={disabled || status === 'confirmado'}
        >
          {saving === 'confirmado' ? (
            <ActivityIndicator color="#ffffff" />
          ) : (
            <>
              <FontAwesome5 name="check" size={13} color="#ffffff" />
              <Text style={reviewBtnText}>{status === 'confirmado' ? 'Confirmado' : 'Confirmar'}</Text>
            </>
          )}
        </TouchableOpacity>
        <TouchableOpacity
          style={[reviewBtn, { backgroundColor: '#dc2626' }, disabled && { opacity: 0.5 }]}
          onPress={() => setRejectOpen(true)}
          disabled={disabled}
        >
          <FontAwesome5 name="times" size={13} color="#ffffff" />
          <Text style={reviewBtnText}>Rejeitar</Text>
        </TouchableOpacity>
      </View>

      <FormModal visible={rejectOpen} onClose={() => setRejectOpen(false)} title={`Rejeitar Item ${padItem(itemNumber)}`}>
        <ScrollView keyboardShouldPersistTaps="handled">
          <Text style={styles.label}>Motivos rápidos</Text>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: 12 }}>
            {presetList.map((p) => (
              <TouchableOpacity
                key={p}
                onPress={() => addPreset(p)}
                style={{ borderWidth: 1, borderColor: '#fecaca', backgroundColor: '#fff5f5', borderRadius: 999, paddingVertical: 6, paddingHorizontal: 10 }}
              >
                <Text style={{ fontSize: 12, color: '#b91c1c' }}>{p.replace(/\.$/, '')}</Text>
              </TouchableOpacity>
            ))}
          </View>

          <Text style={styles.label}>Motivo da rejeição *</Text>
          <TextInput
            style={[styles.input, { minHeight: 90, textAlignVertical: 'top' }]}
            multiline
            value={reason}
            onChangeText={setReason}
            placeholder="Explique o que o usuário precisa corrigir"
          />
          <Text style={{ fontSize: 11, color: '#64748b', marginTop: 6 }}>
            Quem enviou o documento será avisado do motivo por e-mail e WhatsApp (quando ativos).
          </Text>

          <TouchableOpacity
            style={[styles.primaryBtn, { backgroundColor: '#dc2626' }, (!reason.trim() || saving !== null) && { opacity: 0.6 }]}
            disabled={!reason.trim() || saving !== null}
            onPress={() => apply('rejeitado', reason.trim())}
          >
            {saving === 'rejeitado' ? (
              <ActivityIndicator color="#ffffff" />
            ) : (
              <Text style={{ color: '#ffffff', fontWeight: '800' }}>Rejeitar e avisar</Text>
            )}
          </TouchableOpacity>
        </ScrollView>
      </FormModal>
    </View>
  );
}

const reviewBtn = {
  flex: 1,
  flexDirection: 'row' as const,
  alignItems: 'center' as const,
  justifyContent: 'center' as const,
  gap: 7,
  paddingVertical: 12,
  borderRadius: 9,
};

const reviewBtnText = { color: '#ffffff', fontWeight: '800' as const, fontSize: 14 };
