'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthHydrated, useAuthStore } from '@/lib/store/authStore';

/**
 * Redirects to /login when there is no token. Returns true once it is safe to
 * render protected content (the persisted session has loaded and a token exists).
 */
export function useRequireAuth(): boolean {
  const router = useRouter();
  const token = useAuthStore((s) => s.token);
  const hasHydrated = useAuthHydrated();

  useEffect(() => {
    if (hasHydrated && !token) {
      router.replace('/login');
    }
  }, [hasHydrated, token, router]);

  return hasHydrated && Boolean(token);
}
