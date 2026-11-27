import React from 'react';
import { SafeAreaView, Text, TouchableOpacity, View } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { FontAwesome5 } from '@expo/vector-icons';
import { DesktopLogo } from '../../components/Brand';
import { styles } from '../../theme/styles';

/** Tela inicial (antes do login), limpa e minimalista. */
export function WelcomeScreen({ onEnter }: { onEnter: () => void }) {
  return (
    <SafeAreaView style={styles.cleanHomeRoot}>
      <StatusBar style="dark" />

      <View style={styles.cleanHomeCenter}>
        <View style={{ alignItems: 'center', marginBottom: 24 }}>
          <DesktopLogo height={110} />
        </View>

        <View style={styles.cleanAccentLine} />

        <Text style={styles.cleanHomeTitle}>Prestação de Contas</Text>
        <Text style={styles.cleanHomeSub}>Município de Arabutã</Text>

        <TouchableOpacity style={styles.cleanHomeBtn} activeOpacity={0.86} onPress={onEnter}>
          <Text style={styles.cleanHomeBtnText}>Entrar</Text>
          <FontAwesome5 name="arrow-right" size={14} color="#ffffff" />
        </TouchableOpacity>
      </View>

      <Text style={styles.cleanHomeFooter}>© {new Date().getFullYear()} Município de Arabutã</Text>
    </SafeAreaView>
  );
}
