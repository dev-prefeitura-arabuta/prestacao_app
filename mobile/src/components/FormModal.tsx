import React from 'react';
import { Modal, Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { COLORS } from '../theme/colors';
import { styles } from '../theme/styles';

/** Modal tipo "bottom sheet" com cabeçalho (título + botão fechar). */
export function FormModal({
  visible,
  onClose,
  title,
  headerLeft,
  children,
}: {
  visible: boolean;
  onClose: () => void;
  title: string;
  /** Ícone opcional à esquerda do título. */
  headerLeft?: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.formModalBackdrop}>
        <View style={styles.formModalCard}>
          <View style={styles.formModalHeader}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, flex: 1 }}>
              {headerLeft}
              <Text style={styles.formModalTitle} numberOfLines={1}>
                {title}
              </Text>
            </View>
            <TouchableOpacity onPress={onClose}>
              <FontAwesome5 name="times" size={18} color={COLORS.muted} />
            </TouchableOpacity>
          </View>
          {children}
        </View>
      </View>
    </Modal>
  );
}
