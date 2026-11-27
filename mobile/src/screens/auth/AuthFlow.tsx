import React, { useState } from 'react';
import { MobileUser } from '../../api';
import { GuestScreen } from '../../navigation/types';
import { ForgotPasswordScreen } from './ForgotPasswordScreen';
import { LoginScreen } from './LoginScreen';
import { WelcomeScreen } from './WelcomeScreen';

/** Navegação das telas de visitante: boas-vindas → login ⇄ esqueci minha senha. */
export function AuthFlow({
  apiUrl,
  email,
  onEmailChange,
  onLoggedIn,
}: {
  apiUrl: string;
  /** E-mail fica no App para continuar preenchido após o logout. */
  email: string;
  onEmailChange: (email: string) => void;
  onLoggedIn: (token: string, user: MobileUser) => void;
}) {
  const [screen, setScreen] = useState<GuestScreen>('home');

  if (screen === 'home') {
    return <WelcomeScreen onEnter={() => setScreen('login')} />;
  }

  if (screen === 'forgot') {
    return (
      <ForgotPasswordScreen
        apiUrl={apiUrl}
        initialEmail={email.trim()}
        onHome={() => setScreen('home')}
        onBackToLogin={() => setScreen('login')}
        onPasswordReset={(resetEmail) => {
          onEmailChange(resetEmail);
          setScreen('login');
        }}
      />
    );
  }

  return (
    <LoginScreen
      apiUrl={apiUrl}
      email={email}
      onEmailChange={onEmailChange}
      onHome={() => setScreen('home')}
      onForgotPassword={() => setScreen('forgot')}
      onLoggedIn={onLoggedIn}
    />
  );
}
