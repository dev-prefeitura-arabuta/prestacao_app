import React from 'react';
import { FlatList, RefreshControl, Text, View } from 'react-native';
import { AuditLogRow, apiGetAuditHistory } from '../../api';
import { useScreenData } from '../../hooks/useScreenData';
import { styles } from '../../theme/styles';

export function AuditScreen({ token }: { token: string }) {
  const { data, loading, reload } = useScreenData<AuditLogRow[]>(() => apiGetAuditHistory(token), []);

  return (
    <FlatList
      data={data}
      keyExtractor={(item) => String(item.id)}
      contentContainerStyle={{ padding: 14 }}
      refreshControl={<RefreshControl refreshing={loading} onRefresh={reload} />}
      ListEmptyComponent={<Text style={styles.emptyText}>Nenhum registro de auditoria.</Text>}
      renderItem={({ item }) => (
        <View style={styles.itemCard}>
          <View style={styles.itemTop}>
            <Text style={styles.itemNum}>{item.user_name}</Text>
            <Text style={styles.accDeadline}>{item.created_at}</Text>
          </View>
          <Text style={styles.itemTitle}>{item.action}</Text>
        </View>
      )}
    />
  );
}
