import { fireEvent, render, screen } from '@testing-library/react';
import { OnboardingFlow } from '@/components/onboarding/OnboardingFlow';
import { useOnboarding } from '@/lib/hooks/useOnboarding';

jest.mock('next/navigation', () => ({ useRouter: () => ({ push: jest.fn() }) }));
jest.mock('@/lib/hooks/useOnboarding', () => ({ useOnboarding: jest.fn() }));

const mockedUseOnboarding = useOnboarding as jest.Mock;

function mockHook(overrides: Record<string, unknown>) {
  const value = {
    progress: null,
    loading: false,
    submitting: false,
    error: null,
    startOnboarding: jest.fn(),
    completeStep: jest.fn().mockResolvedValue({}),
    skipStep: jest.fn().mockResolvedValue({}),
    refetch: jest.fn(),
    ...overrides,
  };
  mockedUseOnboarding.mockReturnValue(value);
  return value;
}

const inProgress = (step: object, current = 1) => ({
  status: 'in_progress',
  current_step: current,
  total_steps: 5,
  completion_percentage: (current - 1) * 20,
  next_step: { step_number: current, ...step },
});

describe('OnboardingFlow', () => {
  it('renders the loading state', () => {
    mockHook({ loading: true });
    render(<OnboardingFlow />);
    expect(screen.getByText('Loading onboarding...')).toBeInTheDocument();
  });

  it('shows the API error when progress cannot be loaded', () => {
    mockHook({ error: 'Cannot reach the API' });
    render(<OnboardingFlow />);
    expect(screen.getByRole('alert')).toHaveTextContent('Cannot reach the API');
  });

  it('starts onboarding when the start button is clicked', () => {
    const hook = mockHook({ progress: { status: 'not_started', total_steps: 5, completion_percentage: 0 } });
    render(<OnboardingFlow />);
    fireEvent.click(screen.getByText('Start Onboarding'));
    expect(hook.startOnboarding).toHaveBeenCalled();
  });

  it('renders the completion message', () => {
    mockHook({ progress: { status: 'completed', total_steps: 5, completion_percentage: 100 } });
    render(<OnboardingFlow />);
    expect(screen.getByText('Onboarding Complete!')).toBeInTheDocument();
  });

  it('keeps Next disabled until every profile field is filled, then submits the data', () => {
    const hook = mockHook({
      progress: inProgress(
        { name: 'profile_setup', title: 'Set up your profile', description: '', type: 'form', fields: ['name', 'company'] },
        2,
      ),
    });
    render(<OnboardingFlow />);

    const next = screen.getByRole('button', { name: /next step/i });
    expect(next).toBeDisabled();

    fireEvent.change(screen.getByLabelText('name'), { target: { value: 'Jane' } });
    fireEvent.change(screen.getByLabelText('company'), { target: { value: 'Acme' } });
    expect(next).toBeEnabled();

    fireEvent.click(next);
    expect(hook.completeStep).toHaveBeenCalledWith(2, { name: 'Jane', company: 'Acme' });
  });

  it('requires a choice on selection steps', () => {
    mockHook({
      progress: inProgress(
        { name: 'goal_selection', title: 'Goal', description: '', type: 'selection', options: ['automate_workflows'] },
        3,
      ),
    });
    render(<OnboardingFlow />);

    const next = screen.getByRole('button', { name: /next step/i });
    expect(next).toBeDisabled();
    fireEvent.click(screen.getByRole('radio', { name: 'automate workflows' }));
    expect(next).toBeEnabled();
  });

  it('does not allow skipping the welcome step', () => {
    mockHook({ progress: inProgress({ name: 'welcome', title: 'Welcome', description: '', type: 'welcome' }) });
    render(<OnboardingFlow />);
    expect(screen.getByRole('button', { name: /skip/i })).toBeDisabled();
  });
});
