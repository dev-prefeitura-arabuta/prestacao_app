export const COLORS = {
  bg: '#f8fafc',
  bgSoft: '#f1f5f9',
  card: '#ffffff',
  border: '#e2e8f0',
  navy: '#0f172a',
  shieldDark: '#072c5e',
  shieldBlue: '#0b4ea2',
  orange: '#dd4814',
  orangeBright: '#f97316',
  orangeSoft: '#fff7ed',
  orangeBorder: '#fed7aa',
  orangeText: '#c2410c',
  text: '#1e293b',
  muted: '#64748b',
  success: '#16a34a',
};

export const STATUS_STYLE: Record<string, { label: string; bg: string; fg: string }> = {
  pendente: { label: 'Pendente', bg: '#fef3c7', fg: '#92400e' },
  enviado: { label: 'Enviado', bg: '#dbeafe', fg: '#1d4ed8' },
  confirmado: { label: 'Confirmado', bg: '#dcfce7', fg: '#15803d' },
  rejeitado: { label: 'Rejeitado', bg: '#fee2e2', fg: '#b91c1c' },
  prazo_vencido: { label: 'Prazo vencido', bg: '#fee2e2', fg: '#991b1b' },
};

export function getStatusStyle(status: string) {
  return STATUS_STYLE[status] || STATUS_STYLE.pendente;
}
