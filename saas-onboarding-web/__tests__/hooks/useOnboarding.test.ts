import { act, renderHook, waitFor } from '@testing-library/react';
import { useOnboarding } from '@/lib/hooks/useOnboarding';
import { OnboardingService } from '@/lib/api/onboarding';

jest.mock('@/lib/api/onboarding', () => ({
  OnboardingService: {
    start: jest.fn(),
    getProgress: jest.fn(),
    completeStep: jest.fn(),
    skipStep: jest.fn(),
  },
}));

const service = OnboardingService as jest.Mocked<typeof OnboardingService>;

describe('useOnboarding', () => {
  beforeEach(() => {
    jest.resetAllMocks();
    service.getProgress.mockResolvedValue({ status: 'not_started', total_steps: 5, completion_percentage: 0 });
  });

  it('loads progress on mount', async () => {
    const { result } = renderHook(() => useOnboarding());
    expect(result.current.loading).toBe(true);

    await waitFor(() => expect(result.current.loading).toBe(false));
    expect(result.current.progress?.status).toBe('not_started');
  });

  it('updates progress after completing a step', async () => {
    service.completeStep.mockResolvedValue({
      status: 'in_progress',
      current_step: 2,
      total_steps: 5,
      completion_percentage: 20,
      recommendations: { features: [], content: [], actions: [], timing: { optimal_hour_utc: 9, timezone: 'UTC', frequency: 'daily' } },
    });
    const { result } = renderHook(() => useOnboarding());
    await waitFor(() => expect(result.current.loading).toBe(false));

    await act(async () => {
      await result.current.completeStep(1, { seen: 'yes' });
    });

    expect(service.completeStep).toHaveBeenCalledWith(1, { seen: 'yes' });
    expect(result.current.progress?.current_step).toBe(2);
  });

  it('exposes a readable error when the API call fails', async () => {
    service.skipStep.mockRejectedValue(new Error('Step 4 is not yet available.'));
    const { result } = renderHook(() => useOnboarding());
    await waitFor(() => expect(result.current.loading).toBe(false));

    await act(async () => {
      await result.current.skipStep(4);
    });

    expect(result.current.error).toBe('Step 4 is not yet available.');
  });
});
