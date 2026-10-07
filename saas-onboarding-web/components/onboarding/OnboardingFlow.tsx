'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Check, ChevronRight, SkipForward } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input, Label } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import type { OnboardingStep } from '@/lib/api/onboarding';
import { useOnboarding } from '@/lib/hooks/useOnboarding';
import { cn } from '@/lib/utils';

const humanise = (value: string) => value.replace(/_/g, ' ');

const TOUR_ITEMS = [
  { title: 'Dashboard', body: 'A single view of your activity and next best actions.' },
  { title: 'Personalised recommendations', body: 'Suggestions that adapt as you use the product.' },
  { title: 'Analytics and reports', body: 'Track progress and share results with your team.' },
];

export function OnboardingFlow() {
  const router = useRouter();
  const { progress, loading, submitting, error, startOnboarding, completeStep, skipStep } = useOnboarding();
  const [stepData, setStepData] = useState<Record<string, string>>({});

  if (loading) {
    return <p className="py-16 text-center text-muted-foreground">Loading onboarding...</p>;
  }

  if (!progress) {
    return (
      <p role="alert" className="py-16 text-center text-destructive">
        {error ?? 'Could not load onboarding.'}
      </p>
    );
  }

  if (progress.status === 'not_started') {
    return (
      <Card className="mx-auto max-w-2xl">
        <CardHeader>
          <CardTitle>Welcome aboard</CardTitle>
          <CardDescription>
            {progress.total_steps} short steps to set up a personalised experience.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Button onClick={() => startOnboarding()} size="lg" className="w-full" disabled={submitting}>
            Start Onboarding
          </Button>
        </CardContent>
      </Card>
    );
  }

  if (progress.status === 'completed') {
    return (
      <Card className="mx-auto max-w-2xl">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Check className="h-6 w-6 text-green-600" aria-hidden />
            Onboarding Complete!
          </CardTitle>
          <CardDescription>Your recommendations are ready on the dashboard.</CardDescription>
        </CardHeader>
        <CardContent className="flex flex-col gap-3 sm:flex-row">
          <Button onClick={() => router.push('/dashboard')} size="lg" className="flex-1">
            Go to Dashboard
          </Button>
          <Button onClick={() => startOnboarding()} size="lg" variant="outline" disabled={submitting}>
            Restart
          </Button>
        </CardContent>
      </Card>
    );
  }

  const step = progress.next_step;
  const current = progress.current_step ?? 1;
  const isLast = current === progress.total_steps;

  const advance = async (action: 'complete' | 'skip') => {
    const result = action === 'complete' ? await completeStep(current, stepData) : await skipStep(current);
    if (result) setStepData({});
  };

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="text-base">Onboarding progress</CardTitle>
            <span className="text-sm text-muted-foreground">
              Step {current} of {progress.total_steps}
            </span>
          </div>
          <Progress value={progress.completion_percentage} className="mt-3" />
          <p className="pt-1 text-sm text-muted-foreground">{Math.round(progress.completion_percentage)}% complete</p>
        </CardHeader>
      </Card>

      {step && (
        <Card>
          <CardHeader>
            <CardTitle>{step.title}</CardTitle>
            <CardDescription>{step.description}</CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            <StepBody step={step} data={stepData} onChange={setStepData} />

            {error && (
              <p role="alert" className="text-sm text-destructive">
                {error}
              </p>
            )}

            <div className="flex justify-between pt-2">
              <Button variant="outline" onClick={() => advance('skip')} disabled={current === 1 || submitting}>
                <SkipForward className="h-4 w-4" aria-hidden />
                Skip
              </Button>
              <Button onClick={() => advance('complete')} disabled={submitting || !isStepValid(step, stepData)}>
                {isLast ? 'Complete' : 'Next Step'}
                {!isLast && <ChevronRight className="h-4 w-4" aria-hidden />}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

function isStepValid(step: OnboardingStep, data: Record<string, string>): boolean {
  if (step.type === 'form') return (step.fields ?? []).every((f) => data[f]?.trim());
  if (step.type === 'selection') return Boolean(data.selected_option);
  return true;
}

interface StepBodyProps {
  step: OnboardingStep;
  data: Record<string, string>;
  onChange: (data: Record<string, string>) => void;
}

function StepBody({ step, data, onChange }: StepBodyProps) {
  switch (step.type) {
    case 'welcome':
      return (
        <p className="text-muted-foreground">
          We&apos;ll ask a couple of quick questions, show you around, and then tailor your dashboard to what you need.
        </p>
      );

    case 'form':
      return (
        <div className="space-y-4">
          {step.fields?.map((field) => (
            <div key={field} className="space-y-2">
              <Label htmlFor={`field-${field}`} className="capitalize">
                {humanise(field)}
              </Label>
              <Input
                id={`field-${field}`}
                value={data[field] ?? ''}
                placeholder={`Your ${humanise(field)}`}
                onChange={(e) => onChange({ ...data, [field]: e.target.value })}
              />
            </div>
          ))}
        </div>
      );

    case 'selection':
      return (
        <div className="space-y-3" role="radiogroup" aria-label={step.title}>
          {step.options?.map((option) => {
            const selected = data.selected_option === option;
            return (
              <button
                key={option}
                type="button"
                role="radio"
                aria-checked={selected}
                onClick={() => onChange({ ...data, selected_option: option })}
                className={cn(
                  'w-full rounded-md border p-4 text-left capitalize transition-colors',
                  selected ? 'border-primary bg-primary/10' : 'hover:border-primary/50',
                )}
              >
                {humanise(option)}
              </button>
            );
          })}
        </div>
      );

    case 'tour':
      return (
        <ul className="space-y-3">
          {TOUR_ITEMS.map((item) => (
            <li key={item.title} className="rounded-md border p-4">
              <p className="font-medium">{item.title}</p>
              <p className="text-sm text-muted-foreground">{item.body}</p>
            </li>
          ))}
        </ul>
      );

    case 'action':
      return (
        <p className="text-muted-foreground">
          Finish onboarding to open your dashboard. Every feature you open there is tracked and fed back into your
          recommendations.
        </p>
      );

    default:
      return null;
  }
}
