import type { Metadata } from 'next';
import { SiteHeader } from '@/components/layout/SiteHeader';
import './globals.css';

export const metadata: Metadata = {
  title: 'SaaS Platform — Onboarding & Personalisation',
  description: 'Sample Next.js frontend for a Laravel onboarding and personalisation API.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en-AU">
      <body className="font-sans antialiased">
        <SiteHeader />
        <main className="container py-8">{children}</main>
      </body>
    </html>
  );
}
