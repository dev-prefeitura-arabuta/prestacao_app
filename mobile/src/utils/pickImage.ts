import { Alert } from 'react-native';
import * as ImagePicker from 'expo-image-picker';

export type ImageSource = 'camera' | 'gallery';

export interface PickedImage {
  uri: string;
  fileName?: string | null;
  mimeType?: string;
  ext: 'jpg' | 'png' | 'webp';
}

/**
 * Pede permissão (câmera ou galeria), abre o seletor e devolve a imagem escolhida.
 * Retorna null se o usuário negar a permissão ou cancelar.
 */
export async function pickImage(
  source: ImageSource,
  options: ImagePicker.ImagePickerOptions,
  cameraDeniedMessage: string
): Promise<PickedImage | null> {
  if (source === 'camera') {
    const perm = await ImagePicker.requestCameraPermissionsAsync();
    if (!perm.granted) {
      Alert.alert('Permissão necessária', cameraDeniedMessage);
      return null;
    }
  } else {
    const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perm.granted) {
      Alert.alert('Permissão necessária', 'Permita o acesso à galeria para escolher uma foto.');
      return null;
    }
  }

  const opts: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], ...options };
  const result =
    source === 'camera'
      ? await ImagePicker.launchCameraAsync(opts)
      : await ImagePicker.launchImageLibraryAsync(opts);

  if (result.canceled || !result.assets?.length) return null;
  const asset = result.assets[0];
  const ext = asset.mimeType === 'image/png' ? 'png' : asset.mimeType === 'image/webp' ? 'webp' : 'jpg';
  return { uri: asset.uri, fileName: asset.fileName, mimeType: asset.mimeType, ext };
}
