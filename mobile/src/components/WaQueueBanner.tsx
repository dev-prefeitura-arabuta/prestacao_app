import React from 'react';
import { ActivityIndicator, StyleProp, Text, TouchableOpacity, View, ViewStyle } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { styles } from '../theme/styles';
import type { WaQueueState } from '../hooks/useWaRetryQueue';

/** Aviso de cobranças de WhatsApp salvas em cache, com botões "Reenviar" e limpar. */
export function WaQueueBanner({
  wa,
  subtitle,
  style,
}: {
  wa: WaQueueState;
  subtitle: string;
  style?: StyleProp<ViewStyle>;
}) {
  if (wa.queue.length === 0) return null;
  return (
    <View style={[styles.waQueueBanner, style]}>
      <View style={{ flex: 1 }}>
        <Text style={styles.waQueueTitle}>{wa.queue.length} cobrança(s) em cache</Text>
        <Text style={styles.waQueueSub}>{subtitle}</Text>
      </View>
      <TouchableOpacity style={styles.waQueueRetryBtn} onPress={() => wa.flush(false)} disabled={wa.flushing}>
        {wa.flushing ? (
          <ActivityIndicator size="small" color="#ffffff" />
        ) : (
          <Text style={styles.waQueueRetryText}>Reenviar</Text>
        )}
      </TouchableOpacity>
      <TouchableOpacity style={styles.waQueueClearBtn} onPress={wa.clear}>
        <FontAwesome5 name="times" size={12} color="#92400e" />
      </TouchableOpacity>
    </View>
  );
}
