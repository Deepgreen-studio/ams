<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Integrations\Contracts\IncomingWebhookHandlerInterface;
use App\Domains\Integrations\Contracts\IntegrationConnectorInterface;
use App\Domains\Integrations\Models\Integration;

class ConnectorRegistry
{
    /**
     * @param iterable<IntegrationConnectorInterface> $connectors
     */
    public function __construct(
        private readonly iterable $connectors,
    ) {}

    /**
     * @return list<IntegrationConnectorInterface>
     */
    public function all(): array
    {
        $connectors = [];
        foreach ($this->connectors as $connector) {
            $connectors[] = $connector;
        }

        usort($connectors, function (IntegrationConnectorInterface $left, IntegrationConnectorInterface $right): int {
            $priority = $right->priority() <=> $left->priority();

            return $priority !== 0 ? $priority : strcmp($left->key(), $right->key());
        });

        return $connectors;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function catalog(): array
    {
        return array_map(fn (IntegrationConnectorInterface $connector): array => $this->definition($connector), $this->all());
    }

    /**
     * @return list<IntegrationConnectorInterface>
     */
    public function forIntegration(Integration $integration): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (IntegrationConnectorInterface $connector): bool => $connector->matchesIntegration($integration),
        ));
    }

    /**
     * Product webhook handlers, highest priority first.
     *
     * @return list<IncomingWebhookHandlerInterface>
     */
    public function webhookHandlers(): array
    {
        $handlers = [];
        foreach ($this->all() as $connector) {
            if ($connector instanceof IncomingWebhookHandlerInterface) {
                $handlers[] = $connector;
            }
        }

        return $handlers;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(IntegrationConnectorInterface $connector): array
    {
        return [
            'key' => $connector->key(),
            'label' => $connector->label(),
            'description' => $connector->description(),
            'capabilities' => $connector->capabilities(),
            'priority' => $connector->priority(),
            'handles_webhooks' => $connector instanceof IncomingWebhookHandlerInterface,
        ];
    }
}
