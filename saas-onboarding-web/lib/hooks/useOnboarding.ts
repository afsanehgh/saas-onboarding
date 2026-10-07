'use client';

import { useCallback, useEffect, useState } from 'react';
import { OnboardingService, type OnboardingProgress } from '@/lib/api/onboarding';
import { getErrorMessage } from '@/lib/api/client';

export function useOnboarding() {
  const [progress, setProgress] = useState<OnboardingProgress | null>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const run = useCallback(async (action: () => Promise<OnboardingProgress>) => {
    setSubmitting(true);
    setError(null);
    try {
      const next = await action();
      setProgress(next);
      return next;
    } catch (err) {
      setError(getErrorMessage(err));
      return null;
    } finally {
      setSubmitting(false);
    }
  }, []);

  const refetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setProgress(await OnboardingService.getProgress());
    } catch (err) {
      setError(getErrorMessage(err, 'Failed to load onboarding progress.'));
    } finally {
      setLoading(false);
    }
  }, []);

  // Initial load. State is only set once the request settles, never synchronously in the effect.
  useEffect(() => {
    let cancelled = false;
    OnboardingService.getProgress()
      .then((p) => !cancelled && setProgress(p))
      .catch((err) => !cancelled && setError(getErrorMessage(err, 'Failed to load onboarding progress.')))
      .finally(() => !cancelled && setLoading(false));
    return () => {
      cancelled = true;
    };
  }, []);

  return {
    progress,
    loading,
    submitting,
    error,
    refetch,
    startOnboarding: () => run(() => OnboardingService.start()),
    completeStep: (step: number, data?: Record<string, string>) => run(() => OnboardingService.completeStep(step, data)),
    skipStep: (step: number) => run(() => OnboardingService.skipStep(step)),
  };
}
