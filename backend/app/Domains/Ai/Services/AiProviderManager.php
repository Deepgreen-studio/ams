<?php

namespace App\Domains\Ai\Services;

use App\Domains\Ai\Contracts\AiProviderInterface;
use App\Domains\Ai\Models\AiProvider;
use App\Domains\Ai\Repositories\AiProviderRepository;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Container\Container;

class AiProviderManager
{
    public function __construct(
        private readonly Container $container,
        private readonly AiProviderRepository $providerRepository,
    ) {}

    public function driver(?string $driver = null, ?AiProvider $provider = null): AiProviderInterface
    {
        $driverKey = $driver
            ?: ($provider?->driver?->value ?? null)
            ?: (string) config('ai.default_driver', 'null');

        $map = config('ai.drivers', []);
        $class = $map[$driverKey] ?? null;

        if (! is_string($class) || ! class_exists($class)) {
            throw new ApiException("AI driver [{$driverKey}] is not registered.", 500);
        }

        /** @var AiProviderInterface $instance */
        $instance = $this->container->make($class);

        if ($provider) {
            $instance->configure($provider);
        }

        return $instance;
    }

    public function forProvider(AiProvider $provider): AiProviderInterface
    {
        $driver = $provider->driver?->value ?? (string) $provider->driver;

        return $this->driver($driver, $provider);
    }

    public function default(?int $companyId = null): AiProviderInterface
    {
        $provider = $this->providerRepository->findDefault($companyId);
        if ($provider) {
            return $this->forProvider($provider);
        }

        return $this->driver();
    }

    /**
     * @return list<array{value: string, label: string, class: string}>
     */
    public function registeredDrivers(): array
    {
        $drivers = [];
        foreach (config('ai.drivers', []) as $key => $class) {
            $drivers[] = [
                'value' => $key,
                'label' => str_replace('_', ' ', ucwords($key, '_')),
                'class' => $class,
            ];
        }

        return $drivers;
    }
}
