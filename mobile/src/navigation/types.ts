export type GuestScreen = 'home' | 'login' | 'forgot';

export type AppScreen =
  | 'menu'
  | 'accountabilities'
  | 'annual_history'
  | 'audit'
  | 'users'
  | 'settings'
  | 'profile';

/** Telas que o usuário comum (não-admin) pode acessar. */
export const USER_SCREENS: AppScreen[] = ['accountabilities', 'profile'];

export type HistoryYears = Record<
  string,
  Array<{ id: number; year: number; title: string; deadline: string }>
>;
