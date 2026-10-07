'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { BarChart3, BookOpen, ListChecks, RefreshCw, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { usePersonalization } from '@/lib/hooks/usePersonalization';
import { useAuthStore } from '@/lib/store/authStore';

const ALL_FEATURES = ['dashboard', 'analytics', 'integrations', 'automation', 'collaboration', 'reports'];

const SEGMENT_LABELS: Record<string, string> = {
  new_user: 'New user',
  casual_user: 'Casual user',
  power_user: 'Power user',
  at_risk_user: 'At risk',
  standard_user: 'Standard user',
};

export function Dashboard() {
  const user = useAuthStore((s) => s.user);
  const { data, loading, error, regenerate, trackBehavior, trackInteraction } = usePersonalization();

  useEffect(() => {
    trackBehavior({ action: 'page_view', page_url: '/dashboard', feature: 'dashboard' });
  }, [trackBehavior]);

  // Using a feature records behaviour, then refreshes recommendations so the change is visible.
  const openFeature = async (feature: string, fromRecommendation = false) => {
    if (fromRecommendation) await trackInteraction('features', feature, 'clicked');
    await trackBehavior({ action: 'feature_used', feature, page_url: `/features/${feature}` });
    await regenerate();
  };

  if (loading) {
    return <p className="py-16 text-center text-muted-foreground">Loading your recommendations...</p>;
  }

  if (error || !data) {
    return (
      <p role="alert" className="py-16 text-center text-destructive">
        {error ?? 'No recommendations available.'}
      </p>
    );
  }

  const { recommendations, user_segment } = data;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Welcome back{user?.name ? `, ${user.name}` : ''}</h1>
          <p className="text-sm text-muted-foreground">
            Segment: <span className="font-medium text-foreground">{SEGMENT_LABELS[user_segment] ?? user_segment}</span>
            {' · '}Best time to reach you: {String(recommendations.timing.optimal_hour_utc).padStart(2, '0')}:00 UTC
          </p>
        </div>
        <div className="flex gap-2">
          <Link href="/onboarding">
            <Button variant="outline" size="sm">Onboarding</Button>
          </Link>
          <Button variant="outline" size="sm" onClick={regenerate}>
            <RefreshCw className="h-4 w-4" aria-hidden />
            Refresh
          </Button>
        </div>
      </div>

      <div className="grid gap-6 md:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <Sparkles className="h-4 w-4" aria-hidden /> Features to try
            </CardTitle>
            <CardDescription>Based on what you haven&apos;t used yet</CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            {recommendations.features.length === 0 && (
              <p className="text-sm text-muted-foreground">You&apos;ve explored every feature.</p>
            )}
            {recommendations.features.map((f) => (
              <div key={f.key} className="flex items-center justify-between rounded-md border p-3">
                <span className="text-sm font-medium">{f.name}</span>
                <Button size="sm" variant="ghost" onClick={() => openFeature(f.key, true)}>
                  Open
                </Button>
              </div>
            ))}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <BookOpen className="h-4 w-4" aria-hidden /> Suggested reading
            </CardTitle>
            <CardDescription>Picked for your segment</CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            {recommendations.content.map((c) => (
              <div key={c.id} className="rounded-md border p-3">
                <p className="text-sm font-medium">{c.title}</p>
                <p className="text-xs capitalize text-muted-foreground">{c.type.replace(/_/g, ' ')}</p>
              </div>
            ))}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <ListChecks className="h-4 w-4" aria-hidden /> Next actions
            </CardTitle>
            <CardDescription>To get the most out of the platform</CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            {recommendations.actions.length === 0 && (
              <p className="text-sm text-muted-foreground">Nothing outstanding.</p>
            )}
            {recommendations.actions.map((a) => (
              <div key={a.action} className="rounded-md border p-3 text-sm">
                {a.title}
              </div>
            ))}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <BarChart3 className="h-4 w-4" aria-hidden /> Feature explorer
          </CardTitle>
          <CardDescription>
            Each click is sent to the API as a behaviour event and the recommendations above update.
          </CardDescription>
        </CardHeader>
        <CardContent className="grid grid-cols-2 gap-2 sm:grid-cols-3">
          {ALL_FEATURES.map((feature) => (
            <Button key={feature} variant="outline" className="capitalize" onClick={() => openFeature(feature)}>
              {feature}
            </Button>
          ))}
        </CardContent>
      </Card>
    </div>
  );
}
