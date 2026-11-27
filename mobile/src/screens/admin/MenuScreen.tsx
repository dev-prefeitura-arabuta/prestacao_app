import React, { useState } from 'react';
import { RefreshControl, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { apiGetAnnualHistory } from '../../api';
import { MenuButton } from '../../components/ui';
import { useScreenData } from '../../hooks/useScreenData';
import { AppScreen, HistoryYears } from '../../navigation/types';
import { styles } from '../../theme/styles';

/** Menu principal do administrador (substitui a sidebar do desktop). */
export function MenuScreen({
  token,
  onNavigate,
}: {
  token: string;
  onNavigate: (target: AppScreen, year?: string | null) => void;
}) {
  // Anos do histórico anual para o submenu abre/fecha
  const { data: years, loading, reload } = useScreenData<HistoryYears>(() => apiGetAnnualHistory(token), {});
  const [annualExpanded, setAnnualExpanded] = useState(false);

  return (
    <ScrollView contentContainerStyle={styles.menuScroll} refreshControl={<RefreshControl refreshing={loading} onRefresh={reload} />}>
      <Text style={styles.menuSectionLabel}>MENU PRINCIPAL</Text>

      <MenuButton
        highlight
        icon="file-invoice"
        iconSize={17}
        title="Prestações de Contas"
        subtitle="Checklist ativo, prazos e envio de anexos"
        onPress={() => onNavigate('accountabilities')}
      />

      <MenuButton
        icon="calendar-alt"
        title="Histórico Anual"
        subtitle="Prestações finalizadas por ano"
        expanded={annualExpanded}
        onPress={() => setAnnualExpanded((p) => !p)}
      />

      {annualExpanded && (
        <View style={styles.subMenuBox}>
          <TouchableOpacity style={styles.subMenuBtn} onPress={() => onNavigate('annual_history', null)}>
            <Text style={styles.subMenuText}>Todos os Anos</Text>
            <Text style={styles.subMenuCount}>{Object.keys(years).length}</Text>
          </TouchableOpacity>
          {Object.entries(years).map(([yr, list]) => (
            <TouchableOpacity key={yr} style={styles.subMenuBtn} onPress={() => onNavigate('annual_history', yr)}>
              <Text style={styles.subMenuText}>{yr}</Text>
              <Text style={styles.subMenuCount}>{list.length}</Text>
            </TouchableOpacity>
          ))}
        </View>
      )}

      <MenuButton
        icon="history"
        title="Histórico de Auditoria"
        subtitle="Registro de ações e movimentações"
        onPress={() => onNavigate('audit')}
      />
    </ScrollView>
  );
}
