import React from 'react';
import {
  ActivityIndicator,
  StyleProp,
  Text,
  TouchableOpacity,
  View,
  ViewStyle,
} from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { COLORS } from '../theme/colors';
import { styles } from '../theme/styles';

export type Feedback = { type: 'ok' | 'err'; text: string };

/** Botão laranja principal com spinner enquanto `loading`. */
export function PrimaryButton({
  label,
  loading,
  onPress,
  style,
}: {
  label: string;
  loading?: boolean;
  onPress: () => void;
  style?: StyleProp<ViewStyle>;
}) {
  return (
    <TouchableOpacity
      style={[styles.primaryBtn, style, loading && { opacity: 0.7 }]}
      onPress={onPress}
      disabled={loading}
    >
      {loading ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.primaryBtnText}>{label}</Text>}
    </TouchableOpacity>
  );
}

/** Caixa de mensagem de sucesso/erro. `strong` usa as cores mais saturadas (tela de perfil). */
export function FeedbackBox({ feedback, strong }: { feedback: Feedback; strong?: boolean }) {
  const ok = feedback.type === 'ok';
  const palette = strong
    ? { bg: ok ? '#dcfce7' : '#fee2e2', border: ok ? '#86efac' : '#fca5a5', fg: ok ? '#166534' : '#991b1b', size: 13 }
    : { bg: ok ? '#f0fdf4' : '#fef2f2', border: ok ? '#bbf7d0' : '#fecaca', fg: ok ? '#15803d' : '#b91c1c', size: 12 };
  return (
    <View style={[styles.feedbackBox, { backgroundColor: palette.bg, borderColor: palette.border }]}>
      <Text style={{ fontSize: palette.size, fontWeight: '600', color: palette.fg }}>{feedback.text}</Text>
    </View>
  );
}

/** Link "← Voltar ..." no topo das telas internas. */
export function BackLink({ label, onPress, marginBottom = 16 }: { label: string; onPress: () => void; marginBottom?: number }) {
  return (
    <TouchableOpacity style={[styles.topBarBackBtn, { alignSelf: 'flex-start', marginBottom }]} onPress={onPress}>
      <Text style={styles.topBarBackText}>{label}</Text>
    </TouchableOpacity>
  );
}

/** Botão grande de menu (ícone + título + subtítulo + seta). */
export function MenuButton({
  icon,
  iconSize = 16,
  title,
  subtitle,
  onPress,
  highlight,
  expanded,
}: {
  icon: string;
  iconSize?: number;
  title: string;
  subtitle: string;
  onPress: () => void;
  highlight?: boolean;
  /** Quando definido, mostra seta para cima/baixo (submenu) em vez de seta para a direita. */
  expanded?: boolean;
}) {
  const chevron = expanded === undefined ? 'chevron-right' : expanded ? 'chevron-up' : 'chevron-down';
  return (
    <TouchableOpacity style={[styles.menuBtn, highlight && styles.menuBtnHighlight]} onPress={onPress}>
      <View style={styles.menuBtnLeft}>
        <View style={[styles.menuIconWrap, highlight && { backgroundColor: 'rgba(255,255,255,0.2)' }]}>
          <FontAwesome5 name={icon} size={iconSize} color={highlight ? '#ffffff' : COLORS.shieldBlue} />
        </View>
        <View>
          <Text style={[styles.menuBtnTitle, highlight && { color: '#ffffff' }]}>{title}</Text>
          <Text style={[styles.menuBtnSub, highlight && { color: '#ffedd5' }]}>{subtitle}</Text>
        </View>
      </View>
      <FontAwesome5 name={chevron} size={14} color={highlight ? '#ffffff' : COLORS.muted} />
    </TouchableOpacity>
  );
}

/** Lista de itens do checklist com checkbox + atalhos "Marcar/Desmarcar Todos". */
export function ChecklistPicker({
  items,
  selected,
  onChange,
  iconSize = 16,
  quickButtonsMarginBottom = 10,
}: {
  items: Array<{ item_number: number; title: string }>;
  selected: number[];
  onChange: (next: number[]) => void;
  iconSize?: number;
  quickButtonsMarginBottom?: number;
}) {
  function toggle(num: number) {
    onChange(
      selected.includes(num) ? selected.filter((n) => n !== num) : [...selected, num].sort((a, b) => a - b)
    );
  }

  return (
    <>
      <View style={{ flexDirection: 'row', gap: 8, marginBottom: quickButtonsMarginBottom }}>
        <TouchableOpacity style={styles.permQuickBtn} onPress={() => onChange(items.map((i) => i.item_number))}>
          <Text style={styles.permQuickBtnText}>Marcar Todos</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.permQuickBtn} onPress={() => onChange([])}>
          <Text style={styles.permQuickBtnText}>Desmarcar Todos</Text>
        </TouchableOpacity>
      </View>

      {items.map((it) => {
        const checked = selected.includes(it.item_number);
        return (
          <TouchableOpacity
            key={it.item_number}
            style={[styles.permCheckRow, checked && styles.permCheckRowActive]}
            onPress={() => toggle(it.item_number)}
          >
            <FontAwesome5
              name={checked ? 'check-square' : 'square'}
              size={iconSize}
              color={checked ? COLORS.orange : '#94a3b8'}
              solid={checked}
            />
            <Text style={styles.permCheckText}>
              <Text style={{ fontWeight: '800', color: COLORS.shieldDark }}>
                Item {String(it.item_number).padStart(2, '0')}:
              </Text>{' '}
              {it.title}
            </Text>
          </TouchableOpacity>
        );
      })}
    </>
  );
}
