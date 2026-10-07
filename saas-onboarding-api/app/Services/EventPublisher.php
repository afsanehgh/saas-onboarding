<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RdKafka\Conf;
use RdKafka\Producer;
use RuntimeException;
use Throwable;

/**
 * Publishes domain events (user, onboarding, behaviour, personalisation) to a
 * message broker.
 *
 * Drivers (set EVENT_DRIVER in .env):
 *  - "log"   (default) writes each event to the application log. No broker needed,
 *            which keeps local development and CI self-contained.
 *  - "kafka" sends events to Apache Kafka / Amazon MSK. Requires the PHP rdkafka
 *            extension and KAFKA_BROKERS to be set.
 *  - "null"  discards events.
 *
 * Publishing never throws: a broker outage is logged and must not break the
 * user-facing request that triggered the event.
 */
class EventPublisher
{
    public const TOPIC_USER = 'user-events';

    public const TOPIC_ONBOARDING = 'onboarding-events';

    public const TOPIC_BEHAVIOR = 'behavior-events';

    public const TOPIC_PERSONALIZATION = 'personalization-events';

    protected string $driver;

    protected ?object $producer = null;

    public function __construct(protected array $config = [])
    {
        $this->driver = $config['driver'] ?? 'log';
    }

    /**
     * Publish an event to a topic. Returns false if the event could not be sent.
     */
    public function publish(string $topic, array $event): bool
    {
        $payload = $this->buildPayload($event);

        try {
            match ($this->driver) {
                'kafka' => $this->sendToKafka($topic, $payload),
                'null' => null,
                default => Log::info('Domain event', ['topic' => $topic, 'payload' => $payload]),
            };

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to publish domain event', [
                'topic' => $topic,
                'event_type' => $payload['event_type'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function publishUserEvent(string $eventType, array $data, ?int $userId = null): bool
    {
        return $this->publish(self::TOPIC_USER, compact('eventType', 'data', 'userId'));
    }

    public function publishOnboardingEvent(string $eventType, array $data, int $userId): bool
    {
        return $this->publish(self::TOPIC_ONBOARDING, compact('eventType', 'data', 'userId'));
    }

    public function publishBehaviorEvent(string $eventType, array $data, int $userId): bool
    {
        return $this->publish(self::TOPIC_BEHAVIOR, compact('eventType', 'data', 'userId'));
    }

    public function publishPersonalizationEvent(string $eventType, array $data, int $userId): bool
    {
        return $this->publish(self::TOPIC_PERSONALIZATION, compact('eventType', 'data', 'userId'));
    }

    /**
     * Build the event envelope shared by every topic.
     */
    public function buildPayload(array $event): array
    {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return [
            'event_id' => (string) Str::uuid(),
            'event_type' => $event['eventType'] ?? $event['event_type'] ?? 'unknown',
            'event_version' => '1.0',
            'timestamp' => now()->toIso8601String(),
            'data' => $event['data'] ?? [],
            'metadata' => [
                'source' => config('app.name'),
                'user_id' => $event['userId'] ?? $event['user_id'] ?? null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ],
        ];
    }

    /**
     * Send a message to Kafka using the rdkafka extension. The user ID is used
     * as the message key so events for one user stay ordered within a partition.
     */
    protected function sendToKafka(string $topic, array $payload): void
    {
        if (! class_exists(Producer::class)) {
            throw new RuntimeException('EVENT_DRIVER=kafka requires the rdkafka PHP extension.');
        }

        $producer = $this->kafkaProducer();
        $kafkaTopic = $producer->newTopic($this->topicName($topic));
        $key = isset($payload['metadata']['user_id']) ? (string) $payload['metadata']['user_id'] : null;

        $kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($payload, JSON_THROW_ON_ERROR), $key);
        $producer->poll(0);

        $result = $producer->flush((int) ($this->config['kafka']['flush_timeout_ms'] ?? 2000));

        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new RuntimeException('Kafka flush did not complete (code '.$result.').');
        }
    }

    protected function kafkaProducer(): object
    {
        if ($this->producer) {
            return $this->producer;
        }

        $kafka = $this->config['kafka'] ?? [];

        if (empty($kafka['brokers'])) {
            throw new RuntimeException('KAFKA_BROKERS is not set.');
        }

        $conf = new Conf;
        $conf->set('metadata.broker.list', $kafka['brokers']);
        $conf->set('security.protocol', $kafka['security_protocol'] ?? 'PLAINTEXT');

        if (! empty($kafka['sasl_mechanism'])) {
            $conf->set('sasl.mechanisms', $kafka['sasl_mechanism']);
            $conf->set('sasl.username', (string) ($kafka['sasl_username'] ?? ''));
            $conf->set('sasl.password', (string) ($kafka['sasl_password'] ?? ''));
        }

        return $this->producer = new Producer($conf);
    }

    protected function topicName(string $topic): string
    {
        $prefix = $this->config['topic_prefix'] ?? '';

        return $prefix === '' ? $topic : $prefix.'.'.$topic;
    }
}
