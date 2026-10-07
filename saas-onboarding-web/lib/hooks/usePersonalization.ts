'use client';

import { useCallback, useEffect, useState } from 'react';
import {
  PersonalizationService,
  type InteractionAction,
  type InteractionType,
  type RecommendationsResponse,
  type TrackBehaviorInput,
} from '@/lib/api/personalization';
import { getErrorMessage } from '@/lib/api/client';

export function usePersonalization() {
  const [data, setData] = useState<RecommendationsResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchRecommendations = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setData(await PersonalizationService.getRecommendations());
    } catch (err) {
      setError(getErrorMessage(err, 'Failed to load recommendations.'));
    } finally {
      setLoading(false);
    }
  }, []);

  const regenerate = useCallback(async () => {
    try {
      setData(await PersonalizationService.generateRecommendations());
    } catch (err) {
      setError(getErrorMessage(err, 'Failed to refresh recommendations.'));
    }
  }, []);

  // Tracking failures are logged, never shown: they must not disrupt the user.
  const trackBehavior = useCallback(async (input: TrackBehaviorInput) => {
    try {
      await PersonalizationService.trackBehavior({
        device_type: typeof window !== 'undefined' && window.innerWidth < 768 ? 'mobile' : 'desktop',
        ...input,
      });
    } catch (err) {
      console.warn('Behaviour tracking failed', err);
    }
  }, []);

  const trackInteraction = useCallback(async (type: InteractionType, id: string, action: InteractionAction) => {
    try {
      await PersonalizationService.trackRecommendationInteraction(type, id, action);
    } catch (err) {
      console.warn('Interaction tracking failed', err);
    }
  }, []);

  // Initial load. State is only set once the request settles, never synchronously in the effect.
  useEffect(() => {
    let cancelled = false;
    PersonalizationService.getRecommendations()
      .then((d) => !cancelled && setData(d))
      .catch((err) => !cancelled && setError(getErrorMessage(err, 'Failed to load recommendations.')))
      .finally(() => !cancelled && setLoading(false));
    return () => {
      cancelled = true;
    };
  }, []);

  return { data, loading, error, fetchRecommendations, regenerate, trackBehavior, trackInteraction };
}
