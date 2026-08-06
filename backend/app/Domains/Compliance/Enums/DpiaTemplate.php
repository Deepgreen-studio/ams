<?php

namespace App\Domains\Compliance\Enums;

enum DpiaTemplate: string
{
    case Standard = 'standard';
    case HighRiskProcessing = 'high_risk_processing';
    case NewTechnology = 'new_technology';
    case SpecialCategory = 'special_category';
    case ThirdParty = 'third_party';
    case Custom = 'custom';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard DPIA',
            self::HighRiskProcessing => 'High-Risk Processing',
            self::NewTechnology => 'New Technology',
            self::SpecialCategory => 'Special Category Data',
            self::ThirdParty => 'Third-Party Processing',
            self::Custom => 'Custom Template',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function wizardDefaults(): array
    {
        return match ($this) {
            self::Standard => [
                'steps' => ['context', 'processing', 'risks', 'mitigation', 'review'],
                'focus' => 'General personal data processing assessment',
            ],
            self::HighRiskProcessing => [
                'steps' => ['context', 'processing', 'rights', 'risks', 'mitigation', 'review'],
                'focus' => 'Systematic monitoring or large-scale processing',
            ],
            self::NewTechnology => [
                'steps' => ['context', 'technology', 'processing', 'risks', 'mitigation', 'review'],
                'focus' => 'AI, biometrics, or novel processing technology',
            ],
            self::SpecialCategory => [
                'steps' => ['context', 'legal_basis', 'processing', 'risks', 'mitigation', 'review'],
                'focus' => 'Special category / sensitive personal data',
            ],
            self::ThirdParty => [
                'steps' => ['context', 'processor', 'transfers', 'risks', 'mitigation', 'review'],
                'focus' => 'Vendor / processor / international transfer assessment',
            ],
            self::Custom => [
                'steps' => ['context', 'processing', 'risks', 'mitigation', 'review'],
                'focus' => 'Custom organization-defined assessment',
            ],
        };
    }
}
