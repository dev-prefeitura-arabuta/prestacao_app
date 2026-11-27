import React from 'react';
import { Image, Modal, Text, TouchableOpacity, View } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { styles } from '../theme/styles';

export function ImagePreviewModal({
  image,
  onClose,
}: {
  image: { uri: string; name: string } | null;
  onClose: () => void;
}) {
  return (
    <Modal visible={image !== null} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.previewModalBg}>
        <View style={styles.previewModalHeader}>
          <Text style={styles.previewModalTitle} numberOfLines={1}>
            {image?.name || 'Visualização'}
          </Text>
          <TouchableOpacity style={styles.previewModalCloseBtn} onPress={onClose}>
            <FontAwesome5 name="times" size={16} color="#ffffff" />
            <Text style={styles.previewModalCloseText}>Fechar</Text>
          </TouchableOpacity>
        </View>
        {image && <Image source={{ uri: image.uri }} style={styles.previewModalImage} resizeMode="contain" />}
      </View>
    </Modal>
  );
}
