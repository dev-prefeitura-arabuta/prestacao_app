import React from 'react';
import { Image } from 'react-native';

export const BRAND_SHIELD_IMG = require('../../assets/brand-shield.png');
export const SISTEMA_LOGO_IMG = require('../../assets/sistema-logo.png');

export function BrandShieldIcon({ size = 28 }: { size?: number }) {
  return (
    <Image source={BRAND_SHIELD_IMG} style={{ width: size, height: size, resizeMode: 'contain' }} />
  );
}

export function DesktopLogo({ height = 78 }: { height?: number }) {
  return (
    <Image
      source={SISTEMA_LOGO_IMG}
      style={{ width: Math.round(height * 2.7), maxWidth: '100%', height, resizeMode: 'contain' }}
    />
  );
}
