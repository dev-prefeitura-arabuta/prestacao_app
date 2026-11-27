import React from 'react';
import { RefreshControl, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { apiGetAnnualHistory } from '../../api';
import { useScreenData } from '../../hooks/useScreenData';
import { HistoryYears } from '../../navigation/types';
import { styles } from '../../theme/styles';

/** Prestações finalizadas agrupadas por exercício (opcionalmente filtrado por um ano). */
export function AnnualHistoryScreen({
  token,
  year,
  openingDetail,
  onOpenDetail,
}: {
  token: string;
  year: string | null;
  openingDetail: boolean;
  onOpenDetail: (id: number) => void;
}) {
  const { data: years, loading, reload } = useScreenData<HistoryYears>(() => apiGetAnnualHistory(token), {});

  return (
    <ScrollView
      contentContainerStyle={{ padding: 14 }}
      refreshControl={<RefreshControl refreshing={loading || openingDetail} onRefresh={reload} />}
    >
      {Object.keys(years).length === 0 ? (
        <Text style={styles.emptyText}>Nenhuma prestação finalizada no histórico anual.</Text>
      ) : (
        Object.entries(years)
          .filter(([yr]) => !year || yr === year)
          .map(([yr, list]) => (
            <View key={yr} style={styles.itemCard}>
              <Text style={styles.yearTitle}>
                Exercício {yr} ({list.length})
              </Text>
              {list.map((acc) => (
                <TouchableOpacity key={acc.id} style={styles.historyRow} onPress={() => onOpenDetail(acc.id)}>
                  <Text style={styles.historyRowTitle}>• {acc.title}</Text>
                  <Text style={styles.historyRowSub}>Prazo: {acc.deadline} • Finalizada</Text>
                </TouchableOpacity>
              ))}
            </View>
          ))
      )}
    </ScrollView>
  );
}
