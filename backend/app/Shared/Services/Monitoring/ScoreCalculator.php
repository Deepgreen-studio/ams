<?php

namespace App\Shared\Services\Monitoring;

/**
 * Calculates composite health / performance scores for the Integration Hub.
 */
class ScoreCalculator
{
    /**
     * @param  array<string, mixed>  $metrics
     */
    public function healthScore(array $metrics): int
    {
        $availability = (float) ($metrics['availability_rate'] ?? 0);
        $auth = (float) ($metrics['authentication_success_rate'] ?? 0);
        $webhook = (float) ($metrics['webhook_success_rate'] ?? 0);
        $queue = (float) ($metrics['queue_health_score'] ?? 0);
        $errorRate = (float) ($metrics['error_rate'] ?? 0);

        $score = (
            ($availability * 0.30)
            + ($auth * 0.20)
            + ($webhook * 0.25)
            + ($queue * 0.15)
            + (max(0, 100 - $errorRate) * 0.10)
        );

        return (int) max(0, min(100, round($score)));
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    public function performanceScore(array $metrics): int
    {
        $avgMs = (float) ($metrics['avg_response_ms'] ?? 0);
        $p95Ms = (float) ($metrics['p95_response_ms'] ?? $avgMs);
        $errorRate = (float) ($metrics['error_rate'] ?? 0);

        $latencyScore = match (true) {
            $avgMs <= 200 => 100,
            $avgMs <= 500 => 90,
            $avgMs <= 1000 => 75,
            $avgMs <= 2000 => 55,
            $avgMs <= 5000 => 35,
            default => 15,
        };

        $p95Score = match (true) {
            $p95Ms <= 400 => 100,
            $p95Ms <= 1000 => 85,
            $p95Ms <= 2500 => 65,
            $p95Ms <= 5000 => 40,
            default => 20,
        };

        $score = ($latencyScore * 0.45) + ($p95Score * 0.35) + (max(0, 100 - $errorRate) * 0.20);

        return (int) max(0, min(100, round($score)));
    }

    public function statusFromRate(float $rate): string
    {
        return match (true) {
            $rate >= 99 => 'healthy',
            $rate >= 95 => 'degraded',
            $rate > 0 => 'unhealthy',
            default => 'unknown',
        };
    }

    public function queueHealthScore(int $pending, int $failed, int $running): int
    {
        $pressure = $pending + ($failed * 5);
        $score = 100 - min(100, ($pressure * 2) + ($running > 50 ? 10 : 0));

        return (int) max(0, min(100, $score));
    }
}
