import React, { useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  RefreshControl,
  SafeAreaView,
  ScrollView,
  StyleProp,
  Text,
  TouchableOpacity,
  View,
  ViewStyle,
} from 'react-native';
import { StatusBar } from 'expo-status-bar';
import * as DocumentPicker from 'expo-document-picker';
import { FontAwesome5 } from '@expo/vector-icons';
import { AccountabilityDetail, ChecklistItem, apiDeleteAttachment, apiUploadAttachment } from '../../api';
import { ImagePreviewModal } from '../../components/ImagePreviewModal';
import { COLORS, getStatusStyle } from '../../theme/colors';
import { styles } from '../../theme/styles';
import { IMAGE_EXTS, errorMessage, formatFileSize, joinApiUrl, padItem } from '../../utils/format';
import { ImageSource, pickImage } from '../../utils/pickImage';

type Attachment = ChecklistItem['attachments'][number];

function attachmentIcon(ext: string): { name: string; color: string } {
  if (ext === 'pdf') return { name: 'file-pdf', color: '#dc2626' };
  if (IMAGE_EXTS.includes(ext)) return { name: 'file-image', color: COLORS.shieldBlue };
  if (ext === 'zip') return { name: 'file-archive', color: '#b45309' };
  return { name: 'file-alt', color: '#b45309' };
}

const uploadRowBtn: StyleProp<ViewStyle> = {
  flex: 1,
  marginTop: 0,
  paddingVertical: 13,
  flexDirection: 'row',
  justifyContent: 'center',
  alignItems: 'center',
  gap: 7,
};

/** Um item do checklist aberto: lista de anexos, visualizar/excluir e adicionar novos. */
export function AccountabilityItemScreen({
  detail,
  item,
  token,
  apiUrl,
  isAdmin,
  loading,
  onReload,
  onBack,
  onRemind,
}: {
  detail: AccountabilityDetail;
  item: ChecklistItem;
  token: string;
  apiUrl: string;
  isAdmin: boolean;
  loading: boolean;
  onReload: () => Promise<void>;
  onBack: () => void;
  onRemind: (itemNumber: number) => void;
}) {
  const [uploading, setUploading] = useState(false);
  const [deletingAttId, setDeletingAttId] = useState<number | null>(null);
  const [previewImage, setPreviewImage] = useState<{ uri: string; name: string } | null>(null);

  const st = getStatusStyle(item.status);
  const numPad = padItem(item.item_number);
  const canEdit = isAdmin || item.status !== 'confirmado';

  async function upload(file: { uri: string; name: string; mimeType?: string }, successMsg: string) {
    setUploading(true);
    try {
      await apiUploadAttachment(token, detail.id, item.item_number, file);
      Alert.alert('Sucesso', successMsg);
      await onReload();
    } finally {
      setUploading(false);
    }
  }

  async function handleUploadPhoto(source: ImageSource) {
    try {
      const img = await pickImage(source, { quality: 0.72 }, 'Permita o acesso à câmera para fotografar o documento.');
      if (!img) return;
      const name = img.fileName || `documento_item_${item.item_number}_${Date.now()}.${img.ext}`;
      await upload(
        { uri: img.uri, name, mimeType: img.mimeType || 'image/jpeg' },
        `Foto "${name}" anexada ao Item ${item.item_number}!`
      );
    } catch (e: unknown) {
      Alert.alert('Erro no envio', errorMessage(e, 'Falha ao enviar foto.'));
    }
  }

  async function handleUploadFile() {
    try {
      const picked = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'image/jpeg', 'image/png', 'application/zip'],
        copyToCacheDirectory: true,
      });
      if (picked.canceled || !picked.assets?.length) return;
      const asset = picked.assets[0];
      await upload(
        { uri: asset.uri, name: asset.name, mimeType: asset.mimeType },
        `Arquivo "${asset.name}" anexado ao Item ${item.item_number}!`
      );
    } catch (e: unknown) {
      Alert.alert('Erro no envio', errorMessage(e, 'Falha ao enviar arquivo.'));
    }
  }

  async function handlePreview(att: Attachment) {
    const url = joinApiUrl(apiUrl, `/api/mobile/attachments/${att.id}/preview?token=${encodeURIComponent(token)}`);
    if (IMAGE_EXTS.includes((att.file_ext || '').toLowerCase())) {
      setPreviewImage({ uri: url, name: att.original_name });
      return;
    }
    try {
      await Linking.openURL(url);
    } catch {
      Alert.alert('Erro', 'Não foi possível abrir o arquivo para visualização.');
    }
  }

  async function handleDelete(attId: number) {
    setDeletingAttId(attId);
    try {
      Alert.alert('Sucesso', await apiDeleteAttachment(token, attId));
      await onReload();
    } catch (e: unknown) {
      Alert.alert('Erro', errorMessage(e, 'Não foi possível remover o anexo.'));
    } finally {
      setDeletingAttId(null);
    }
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="light" />
      <View style={styles.header}>
        <TouchableOpacity onPress={onBack} style={styles.backBtn}>
          <Text style={styles.backBtnText}>← Voltar para Itens</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>
          Item {numPad}
        </Text>
        <Text style={styles.headerSub} numberOfLines={1}>
          {detail.title}
        </Text>
      </View>

      <ScrollView
        contentContainerStyle={{ padding: 16, paddingBottom: 36 }}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={onReload} />}
      >
        <View style={styles.itemCard}>
          <View style={styles.itemTop}>
            <Text style={styles.itemNum}>Item {numPad}</Text>
            <View style={[styles.badge, { backgroundColor: st.bg }]}>
              <Text style={[styles.badgeText, { color: st.fg }]}>{st.label}</Text>
            </View>
          </View>
          <Text style={[styles.itemTitle, { fontSize: 15, lineHeight: 21 }]}>{item.title}</Text>
          {item.rejection_reason ? <Text style={styles.rejectText}>Motivo rejeição: {item.rejection_reason}</Text> : null}

          {isAdmin && item.status !== 'confirmado' && (
            <TouchableOpacity style={styles.waSmallPillBtn} onPress={() => onRemind(item.item_number)}>
              <FontAwesome5 name="whatsapp" size={13} color="#ffffff" />
              <Text style={styles.waSmallPillText}>Cobrar</Text>
            </TouchableOpacity>
          )}
        </View>

        <Text style={styles.menuSectionLabel}>ANEXOS DO ITEM ({item.attachments.length})</Text>

        {item.attachments.length === 0 ? (
          <View style={styles.itemCard}>
            <Text style={[styles.emptyText, { marginTop: 8, marginBottom: 8 }]}>
              Nenhum documento anexado neste item ainda.
            </Text>
          </View>
        ) : (
          item.attachments.map((att) => {
            const ext = (att.file_ext || '').toLowerCase();
            const icon = attachmentIcon(ext);
            const deleting = deletingAttId === att.id;
            return (
              <View key={att.id} style={styles.attCard}>
                <View style={styles.attCardTop}>
                  <View style={styles.attIconWrap}>
                    <FontAwesome5 name={icon.name} size={18} color={icon.color} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.attCardName} numberOfLines={2}>
                      {att.original_name}
                    </Text>
                    <Text style={styles.attCardMeta}>
                      {ext.toUpperCase()} • {formatFileSize(att.file_size)}
                    </Text>
                  </View>
                </View>

                <View style={styles.attActionRow}>
                  <TouchableOpacity style={styles.attViewBtn} onPress={() => handlePreview(att)}>
                    <FontAwesome5 name="eye" size={13} color="#ffffff" />
                    <Text style={styles.attViewBtnText}>Visualizar</Text>
                  </TouchableOpacity>

                  {canEdit && (
                    <TouchableOpacity
                      style={[styles.attDeleteBtn, deleting && { opacity: 0.6 }]}
                      onPress={() => handleDelete(att.id)}
                      disabled={deleting}
                    >
                      {deleting ? (
                        <ActivityIndicator size="small" color="#b91c1c" />
                      ) : (
                        <>
                          <FontAwesome5 name="trash-alt" size={13} color="#b91c1c" />
                          <Text style={styles.attDeleteBtnText}>Excluir</Text>
                        </>
                      )}
                    </TouchableOpacity>
                  )}
                </View>
              </View>
            );
          })
        )}

        {canEdit && (
          <View style={{ marginTop: 10 }}>
            <Text style={styles.menuSectionLabel}>ADICIONAR ANEXO</Text>
            <View style={{ flexDirection: 'row', gap: 8 }}>
              <TouchableOpacity
                style={[styles.uploadBtn, uploadRowBtn, { backgroundColor: COLORS.orange }]}
                onPress={() => handleUploadPhoto('camera')}
                disabled={uploading}
              >
                <FontAwesome5 name="camera" size={14} color="#ffffff" />
                <Text style={styles.uploadBtnText}>{uploading ? 'Enviando...' : 'Câmera'}</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.uploadBtn, uploadRowBtn, { backgroundColor: COLORS.shieldBlue }]}
                onPress={() => handleUploadPhoto('gallery')}
                disabled={uploading}
              >
                <FontAwesome5 name="images" size={14} color="#ffffff" />
                <Text style={styles.uploadBtnText}>Galeria</Text>
              </TouchableOpacity>
            </View>

            <TouchableOpacity
              style={[
                styles.uploadBtn,
                { marginTop: 8, paddingVertical: 11, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8 },
              ]}
              onPress={handleUploadFile}
              disabled={uploading}
            >
              <FontAwesome5 name="file-upload" size={14} color="#ffffff" />
              <Text style={styles.uploadBtnText}>Anexar Arquivo (PDF / ZIP)</Text>
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>

      <ImagePreviewModal image={previewImage} onClose={() => setPreviewImage(null)} />
    </SafeAreaView>
  );
}
