<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Service;

class BouncerResultNormalizer
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array{status:string, score:int|null, reason:string, toxic:string, toxicity:int|null, provider:string, raw:string}
     */
    public function normalize(array $payload): array
    {
        $status = strtolower((string) ($payload['status'] ?? $payload['result'] ?? 'unknown'));
        $score  = null;
        if (isset($payload['score']) && is_numeric($payload['score'])) {
            $rawScore = (float) $payload['score'];
            $score    = $rawScore <= 1 ? (int) round($rawScore * 100) : (int) round($rawScore);
        }
        $toxicity = isset($payload['toxicity']) && is_numeric($payload['toxicity']) ? (int) $payload['toxicity'] : null;

        return [
            'status'   => '' !== $status ? $status : 'unknown',
            'score'    => $score,
            'reason'   => (string) ($payload['reason'] ?? $payload['sub_status'] ?? ''),
            'toxic'    => $this->normalizeToxic($payload['toxic'] ?? null),
            'toxicity' => $toxicity,
            'provider' => (string) ($payload['provider'] ?? ''),
            'raw'      => (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @param mixed $value
     */
    private function normalizeToxic($value): string
    {
        if (true === $value) {
            return 'yes';
        }

        if (false === $value) {
            return 'no';
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'true', 'yes'], true)) {
            return 'yes';
        }

        if (in_array($normalized, ['0', 'false', 'no'], true)) {
            return 'no';
        }

        return 'unknown';
    }
}
