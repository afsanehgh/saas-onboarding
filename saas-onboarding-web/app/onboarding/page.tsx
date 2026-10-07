'use client';

import { OnboardingFlow } from '@/components/onboarding/OnboardingFlow';
import { useRequireAuth } from '@/lib/hooks/useRequireAuth';

export default function OnboardingPage() {
  const ready = useRequireAuth();
  return ready ? <OnboardingFlow /> : null;
}
