<?php

namespace Tests\Unit;

use App\Services\EventPublisher;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EventPublisherTest extends TestCase
{
    public function test_log_driver_writes_the_event_envelope(): void
    {
        Log::spy();

        $publisher = new EventPublisher(['driver' => 'log']);

        $this->assertTrue($publisher->publishOnboardingEvent('onboarding.started', ['progress_id' => 7], 42));

        Log::shouldHaveReceived('info')->once()->withArgs(function (string $message, array $context) {
            return $context['topic'] === EventPublisher::TOPIC_ONBOARDING
                && $context['payload']['event_type'] === 'onboarding.started'
                && $context['payload']['data'] === ['progress_id' => 7]
                && $context['payload']['metadata']['user_id'] === 42;
        });
    }

    public function test_publishing_failures_are_logged_not_thrown(): void
    {
        Log::spy();

        // Kafka driver without brokers (or without the rdkafka extension) must fail gracefully.
        $publisher = new EventPublisher(['driver' => 'kafka', 'kafka' => ['brokers' => null]]);

        $this->assertFalse($publisher->publishUserEvent('user.registered', [], 1));

        Log::shouldHaveReceived('error')->once();
    }

    public function test_every_event_gets_a_unique_id(): void
    {
        $publisher = new EventPublisher(['driver' => 'null']);

        $a = $publisher->buildPayload(['event_type' => 'x']);
        $b = $publisher->buildPayload(['event_type' => 'x']);

        $this->assertNotSame($a['event_id'], $b['event_id']);
    }
}
