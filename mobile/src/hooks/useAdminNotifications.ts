import { useEffect, useState } from 'react';
import {
  AdminNotificationItem,
  apiGetNotifications,
  apiMarkAllNotificationsRead,
  apiMarkNotificationRead,
  apiToggleNotifications,
} from '../api';

export interface AdminNotificationsState {
  enabled: boolean;
  unread: number;
  items: AdminNotificationItem[];
  refresh: () => Promise<void>;
  toggle: () => Promise<void>;
  markAllRead: () => Promise<void>;
  markRead: (it: AdminNotificationItem) => void;
}

const POLL_MS = 30000;

/** Notificações do sininho do administrador; atualiza a cada 30s enquanto estiverem ativas. */
export function useAdminNotifications(token: string | null, isAdmin: boolean): AdminNotificationsState {
  const [enabled, setEnabled] = useState(true);
  const [unread, setUnread] = useState(0);
  const [items, setItems] = useState<AdminNotificationItem[]>([]);

  async function refresh() {
    if (!token) return;
    try {
      const data = await apiGetNotifications(token);
      setEnabled(data.enabled);
      setUnread(data.unread_count);
      setItems(data.items);
    } catch {
      // ignora se offline
    }
  }

  useEffect(() => {
    if (!token || !isAdmin) return;
    refresh();
    const timer = setInterval(() => {
      if (enabled) refresh();
    }, POLL_MS);
    return () => clearInterval(timer);
  }, [token, isAdmin, enabled]);

  async function toggle() {
    if (!token) return;
    const next = !enabled;
    setEnabled(next);
    try {
      setEnabled(await apiToggleNotifications(token, next));
      await refresh();
    } catch {
      // mantém estado otimista
    }
  }

  async function markAllRead() {
    if (!token) return;
    await apiMarkAllNotificationsRead(token);
    setUnread(0);
    setItems((prev) => prev.map((it) => ({ ...it, is_read: true })));
  }

  function markRead(it: AdminNotificationItem) {
    if (!token || it.is_read) return;
    apiMarkNotificationRead(token, it.id);
    setItems((prev) => prev.map((x) => (x.id === it.id ? { ...x, is_read: true } : x)));
    setUnread((u) => Math.max(0, u - 1));
  }

  return { enabled, unread, items, refresh, toggle, markAllRead, markRead };
}
