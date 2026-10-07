'use client';

import { useState, type FormEvent } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input, Label } from '@/components/ui/input';
import { AuthService } from '@/lib/api/auth';
import { getErrorMessage } from '@/lib/api/client';
import { useAuthStore } from '@/lib/store/authStore';

interface AuthFormProps {
  mode: 'login' | 'register';
}

export function AuthForm({ mode }: AuthFormProps) {
  const router = useRouter();
  const setAuth = useAuthStore((s) => s.setAuth);
  const [form, setForm] = useState({ name: '', company: '', email: '', password: '' });
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const isRegister = mode === 'register';

  const update = (field: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setForm((f) => ({ ...f, [field]: e.target.value }));

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      const { user, token } = isRegister
        ? await AuthService.register({
            name: form.name,
            email: form.email,
            password: form.password,
            company: form.company || undefined,
          })
        : await AuthService.login(form.email, form.password);
      setAuth(user, token);
      router.push(user.onboarding_completed ? '/dashboard' : '/onboarding');
    } catch (err) {
      setError(getErrorMessage(err));
      setSubmitting(false);
    }
  };

  return (
    <Card className="mx-auto w-full max-w-md">
      <CardHeader>
        <CardTitle>{isRegister ? 'Create your account' : 'Log in'}</CardTitle>
        <CardDescription>
          {isRegister
            ? 'Sign up to start the guided onboarding.'
            : 'Demo account: demo@example.com / password'}
        </CardDescription>
      </CardHeader>
      <form onSubmit={handleSubmit} noValidate>
        <CardContent className="space-y-4">
          {isRegister && (
            <>
              <div className="space-y-2">
                <Label htmlFor="name">Name</Label>
                <Input id="name" autoComplete="name" required value={form.name} onChange={update('name')} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="company">Company (optional)</Label>
                <Input id="company" autoComplete="organization" value={form.company} onChange={update('company')} />
              </div>
            </>
          )}
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" autoComplete="email" required value={form.email} onChange={update('email')} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="password">Password</Label>
            <Input
              id="password"
              type="password"
              autoComplete={isRegister ? 'new-password' : 'current-password'}
              required
              minLength={isRegister ? 8 : undefined}
              value={form.password}
              onChange={update('password')}
            />
          </div>
          {error && (
            <p role="alert" className="text-sm text-destructive">
              {error}
            </p>
          )}
        </CardContent>
        <CardFooter className="flex-col gap-3">
          <Button type="submit" className="w-full" disabled={submitting}>
            {submitting ? 'Please wait…' : isRegister ? 'Create account' : 'Log in'}
          </Button>
          <p className="text-sm text-muted-foreground">
            {isRegister ? 'Already have an account? ' : 'New here? '}
            <Link href={isRegister ? '/login' : '/register'} className="font-medium text-primary hover:underline">
              {isRegister ? 'Log in' : 'Create an account'}
            </Link>
          </p>
        </CardFooter>
      </form>
    </Card>
  );
}
