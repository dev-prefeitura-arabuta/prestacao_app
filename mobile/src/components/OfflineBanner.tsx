import React from 'react';
import { ActivityIndicator, StyleProp, Text, TouchableOpacity, View, ViewStyle } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { formatOfflineSince } from '../offline/screenCache';

/**
 * Aviso de modo offline: dados exibidos a partir da cópia salva e/ou anexos aguardando conexão.
 * Não renderiza nada quando está tudo sincronizado.
 */
export function OfflineBanner({
  offlineSince,
  pendingCount = 0,
  failedCount = 0,
  flushing = false,
  onRetry,
  style,
}: {
  offlineSince?: string | null;
  pendingCount?: number;
  failedCount?: number;
  flushing?: boolean;
  onRetry?: () => void;
  style?: StyleProp<ViewStyle>;
}) {
  if (!offlineSince && pendingCount === 0 && failedCount === 0) return null;

  const lines: string[] = [];
  if (offlineSince) lines.push(`Sem conexão — exibindo dados salvos em ${formatOfflineSince(offlineSince)}.`);
  if (pendingCount > 0) lines.push(`${pendingCount} anexo(s) aguardando conexão. O envio é automático.`);
  if (failedCount > 0) lines.push(`${failedCount} anexo(s) recusado(s) pelo servidor. Abra o item para ver o motivo.`);

  return (
    <View
      style={[
        {
          flexDirection: 'row',
          alignItems: 'center',
          gap: 10,
          backgroundColor: failedCount > 0 && pendingCount === 0 ? '#fef2f2' : '#fffbeb',
          borderColor: failedCount > 0 && pendingCount === 0 ? '#fecaca' : '#fde68a',
          borderWidth: 1,
          borderRadius: 10,
          padding: 10,
          marginBottom: 12,
        },
        style,
      ]}
    >
      <FontAwesome5 name={offlineSince ? 'wifi' : 'cloud-upload-alt'} size={15} color="#92400e" />
      <View style={{ flex: 1 }}>
        {lines.map((line) => (
          <Text key={line} style={{ fontSize: 12, color: '#78350f', lineHeight: 17 }}>
            {line}
          </Text>
        ))}
      </View>
      {pendingCount > 0 && onRetry ? (
        <TouchableOpacity
          onPress={onRetry}
          disabled={flushing}
          style={{ backgroundColor: '#b45309', borderRadius: 8, paddingVertical: 7, paddingHorizontal: 11 }}
        >
          {flushing ? (
            <ActivityIndicator size="small" color="#ffffff" />
          ) : (
            <Text style={{ color: '#ffffff', fontSize: 12, fontWeight: '700' }}>Enviar agora</Text>
          )}
        </TouchableOpacity>
      ) : null}
    </View>
  );
}
