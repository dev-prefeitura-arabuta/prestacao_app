import React from 'react';
import { Text, TouchableOpacity, View } from 'react-native';
import { BrandShieldIcon } from '../../components/Brand';
import { styles } from '../../theme/styles';

/** Barra superior das telas de login / esqueci minha senha. */
export function GuestTopBar({
  onBrandPress,
  backLabel,
  onBack,
}: {
  onBrandPress: () => void;
  backLabel: string;
  onBack: () => void;
}) {
  return (
    <View style={styles.topBar}>
      <TouchableOpacity style={styles.topBarBrand} onPress={onBrandPress}>
        <BrandShieldIcon size={32} />
        <Text style={styles.topBarTitle}>Prestação de Contas</Text>
      </TouchableOpacity>
      <TouchableOpacity style={styles.topBarBackBtn} onPress={onBack}>
        <Text style={styles.topBarBackText}>{backLabel}</Text>
      </TouchableOpacity>
    </View>
  );
}
