import Link from 'next/link';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const FEATURES = [
  {
    title: 'Automated onboarding',
    description: 'A guided, resumable journey',
    body: 'Users complete or skip steps; progress and profile answers are stored by the API.',
  },
  {
    title: 'Personalisation',
    description: 'Recommendations from behaviour',
    body: 'Feature usage is tracked, users are segmented, and suggestions update as they explore.',
  },
  {
    title: 'Event-driven',
    description: 'Every action emits a domain event',
    body: 'Events go to the log locally, or to Kafka / Amazon MSK when configured.',
  },
];

export default function Home() {
  return (
    <div className="mx-auto max-w-4xl space-y-12 py-8 text-center">
      <div className="space-y-4">
        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">Onboarding that adapts to each user</h1>
        <p className="text-lg text-muted-foreground">
          A sample SaaS platform: Next.js frontend, Laravel API, PostgreSQL, Redis, and AWS infrastructure as code.
        </p>
      </div>

      <div className="grid gap-6 text-left md:grid-cols-3">
        {FEATURES.map((f) => (
          <Card key={f.title}>
            <CardHeader>
              <CardTitle className="text-lg">{f.title}</CardTitle>
              <CardDescription>{f.description}</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground">{f.body}</p>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="flex flex-col justify-center gap-3 sm:flex-row sm:gap-4">
        <Link href="/register">
          <Button size="lg" className="w-full sm:w-auto">
            Get Started
          </Button>
        </Link>
        <Link href="/login">
          <Button size="lg" variant="outline" className="w-full sm:w-auto">
            Try the demo account
          </Button>
        </Link>
      </div>
    </div>
  );
}
