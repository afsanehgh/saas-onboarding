import { apiClient } from './client';
import type { Recommendations } from './personalization';

export type StepType = 'welcome' | 'form' | 'selection' | 'tour' | 'action';

export interface OnboardingStep {
  step_number: number;
  name: string;
  title: string;
  description: string;
  type: StepType;
  fields?: string[];
  options?: string[];
}

export interface OnboardingProgress {
  progress_id?: number;
  current_step?: number;
  total_steps: number;
  completed_steps?: number[];
  skipped_steps?: number[];
  status: 'not_started' | 'in_progress' | 'completed';
  completion_percentage: number;
  next_step?: OnboardingStep | null;
  started_at?: string | null;
  completed_at?: string | null;
}

type StepData = Record<string, string>;

interface Envelope<T> {
  success: boolean;
  data: T;
}

export const OnboardingService = {
  async start() {
    const { data } = await apiClient.post<Envelope<OnboardingProgress>>('/onboarding/start');
    return data.data;
  },

  async getProgress() {
    const { data } = await apiClient.get<Envelope<OnboardingProgress>>('/onboarding/progress');
    return data.data;
  },

  async completeStep(stepNumber: number, stepData: StepData = {}) {
    const { data } = await apiClient.post<Envelope<OnboardingProgress & { recommendations: Recommendations }>>(
      `/onboarding/steps/${stepNumber}/complete`,
      { step_data: stepData },
    );
    return data.data;
  },

  async skipStep(stepNumber: number) {
    const { data } = await apiClient.post<Envelope<OnboardingProgress>>(`/onboarding/steps/${stepNumber}/skip`);
    return data.data;
  },
};
