'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { AuthService } from '@/lib/api/auth';
import { useAuthHydrated, useAuthStore } from '@/lib/store/authStore';

export function SiteHeader() {
  const router = useRouter();
  const { user, token, logout } = useAuthStore();
  // Render the signed-out header until the saved session has loaded, so server and client HTML match.
  const signedIn = useAuthHydrated() && Boolean(token);

  const handleLogout = async () => {
    try {
      await AuthService.logout();
    } catch {
      // The token may already be invalid; clear the local session regardless.
    }
    logout();
    router.replace('/login');
  };

  return (
    <header className="border-b bg-background">
      <div className="container flex h-14 items-center justify-between">
        <Link href={signedIn ? '/dashboard' : '/'} className="font-semibold">
          SaaS Platform
        </Link>
        {signedIn ? (
          <div className="flex items-center gap-3">
            <span className="hidden text-sm text-muted-foreground sm:inline">{user?.name}</span>
            <Button variant="ghost" size="sm" onClick={handleLogout}>
              <LogOut className="h-4 w-4" aria-hidden />
              Log out
            </Button>
          </div>
        ) : (
          <nav className="flex items-center gap-2">
            <Link href="/login">
              <Button variant="ghost" size="sm">Log in</Button>
            </Link>
            <Link href="/register">
              <Button size="sm">Sign up</Button>
            </Link>
          </nav>
        )}
      </div>
    </header>
  );
}
