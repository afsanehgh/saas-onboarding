'use client';

import { Dashboard } from '@/components/dashboard/Dashboard';
import { useRequireAuth } from '@/lib/hooks/useRequireAuth';

export default function DashboardPage() {
  const ready = useRequireAuth();
  return ready ? <Dashboard /> : null;
}
