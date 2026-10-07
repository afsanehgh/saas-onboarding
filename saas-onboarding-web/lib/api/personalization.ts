import { apiClient } from './client';

export interface Recommendations {
  features: Array<{ key: string; name: string; priority: number; reason: string }>;
  content: Array<{ id: string; title: string; type: string }>;
  actions: Array<{ action: string; title: string; priority: number }>;
  timing: { optimal_hour_utc: number; timezone: string; frequency: string };
}

export interface RecommendationsResponse {
  recommendations: Recommendations;
  user_segment: string;
  generated_at: string;
}

export interface TrackBehaviorInput {
  action: string;
  feature?: string;
  page_url?: string;
  context?: Record<string, unknown>;
  duration_seconds?: number;
  device_type?: string;
  browser?: string;
}

export type InteractionType = 'features' | 'content' | 'actions';
export type InteractionAction = 'clicked' | 'dismissed' | 'completed';

interface Envelope<T> {
  success: boolean;
  data: T;
}

export const PersonalizationService = {
  async trackBehavior(input: TrackBehaviorInput) {
    const { data } = await apiClient.post<Envelope<{ behavior_id: number; action: string; tracked_at: string }>>(
      '/personalization/behavior',
      input,
    );
    return data.data;
  },

  async getRecommendations() {
    const { data } = await apiClient.get<Envelope<RecommendationsResponse>>('/personalization/recommendations');
    return data.data;
  },

  async generateRecommendations() {
    const { data } = await apiClient.post<Envelope<RecommendationsResponse>>('/personalization/recommendations/generate');
    return data.data;
  },

  async trackRecommendationInteraction(type: InteractionType, id: string, action: InteractionAction) {
    await apiClient.post('/personalization/recommendations/interaction', {
      recommendation_type: type,
      recommendation_id: id,
      action,
    });
  },
};
