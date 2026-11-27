import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { apiCreateAccountability } from '../../api';
import { FormModal } from '../../components/FormModal';
import { ChecklistPicker } from '../../components/ui';
import { ALL_CHECKLIST_ITEMS, ALL_CHECKLIST_NUMS } from '../../constants/checklist';
import { styles } from '../../theme/styles';
import { errorMessage, formatDateFromOffset, formatDateMask, formatEndOfMonth } from '../../utils/format';

/** Modal (admin) para criar uma nova prestação de contas escolhendo os itens do checklist. */
export function NewAccountabilityModal({
  visible,
  onClose,
  token,
  onCreated,
  initial,
}: {
  visible: boolean;
  onClose: () => void;
  token: string;
  onCreated: () => void;
  /** Valores iniciais (usado em "Duplicar": copia título, descrição e itens de outra prestação). */
  initial?: { title: string; description: string; enabledItems: number[] };
}) {
  const [title, setTitle] = useState('');
  const [desc, setDesc] = useState('');
  const [deadline, setDeadline] = useState('');
  const [enabledItems, setEnabledItems] = useState<number[]>(ALL_CHECKLIST_NUMS);
  const [saving, setSaving] = useState(false);

  // Formulário sempre começa limpo ao abrir
  useEffect(() => {
    if (!visible) return;
    setTitle(initial?.title ?? '');
    setDesc(initial?.description ?? '');
    setDeadline('');
    setEnabledItems(initial?.enabledItems ?? ALL_CHECKLIST_NUMS);
  }, [visible, initial]);

  async function handleCreate() {
    const cleanTitle = title.trim();
    if (cleanTitle.length < 3) {
      Alert.alert('Campo obrigatório', 'Informe um título com pelo menos 3 caracteres.');
      return;
    }
    if (enabledItems.length === 0) {
      Alert.alert('Campos obrigatórios', 'Selecione pelo menos um item/campo para esta prestação de contas.');
      return;
    }
    setSaving(true);
    try {
      const res = await apiCreateAccountability(token, {
        title: cleanTitle,
        description: desc.trim(),
        deadline: deadline.trim(),
        enabled_items: enabledItems,
      });
      onClose();
      Alert.alert('Sucesso', res.message);
      onCreated();
    } catch (e: unknown) {
      Alert.alert('Erro', errorMessage(e, 'Não foi possível criar a prestação.'));
    } finally {
      setSaving(false);
    }
  }

  const quickDeadlines: Array<[string, () => string]> = [
    ['+15 dias', () => formatDateFromOffset(15)],
    ['+30 dias', () => formatDateFromOffset(30)],
    ['Fim do mês', formatEndOfMonth],
  ];

  return (
    <FormModal visible={visible} onClose={onClose} title={initial ? 'Duplicar Prestação de Contas' : 'Nova Prestação de Contas'}>
      <ScrollView keyboardShouldPersistTaps="handled">
        <Text style={styles.label}>Título *</Text>
        <TextInput
          style={styles.input}
          value={title}
          onChangeText={setTitle}
          placeholder="Ex: Prestação de Contas 2026 - 1º Trimestre"
          placeholderTextColor="#94a3b8"
        />

        <Text style={[styles.label, { marginTop: 12 }]}>Descrição</Text>
        <TextInput
          style={[styles.input, { minHeight: 74, textAlignVertical: 'top' }]}
          value={desc}
          onChangeText={setDesc}
          multiline
          placeholder="Observações ou detalhes desta prestação (opcional)"
          placeholderTextColor="#94a3b8"
        />

        <Text style={[styles.label, { marginTop: 12 }]}>Prazo limite (DD/MM/AAAA)</Text>
        <TextInput
          style={styles.input}
          value={deadline}
          onChangeText={(txt) => setDeadline(formatDateMask(txt))}
          keyboardType="number-pad"
          maxLength={10}
          placeholder="DD/MM/AAAA"
          placeholderTextColor="#94a3b8"
        />
        <View style={{ flexDirection: 'row', gap: 6, marginTop: 7, flexWrap: 'wrap' }}>
          {quickDeadlines.map(([label, compute]) => (
            <TouchableOpacity key={label} style={styles.permQuickBtn} onPress={() => setDeadline(compute())}>
              <Text style={styles.permQuickBtnText}>{label}</Text>
            </TouchableOpacity>
          ))}
          {deadline !== '' && (
            <TouchableOpacity style={styles.permQuickBtn} onPress={() => setDeadline('')}>
              <Text style={[styles.permQuickBtnText, { color: '#b91c1c' }]}>Limpar</Text>
            </TouchableOpacity>
          )}
        </View>

        <View style={styles.profileDivider} />

        <Text style={styles.profileSectionTitle}>
          CAMPOS / ITENS QUE SERÃO USADOS ({enabledItems.length}/{ALL_CHECKLIST_ITEMS.length})
        </Text>

        <ChecklistPicker items={ALL_CHECKLIST_ITEMS} selected={enabledItems} onChange={setEnabledItems} />

        <View style={styles.formModalActions}>
          <TouchableOpacity style={styles.formModalCancelBtn} onPress={onClose} disabled={saving}>
            <Text style={styles.formModalCancelText}>Cancelar</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.formModalSaveBtn, saving && { opacity: 0.7 }]}
            onPress={handleCreate}
            disabled={saving}
          >
            {saving ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.formModalSaveText}>Criar Prestação</Text>}
          </TouchableOpacity>
        </View>
      </ScrollView>
    </FormModal>
  );
}
