<?php

namespace App\Domains\Compliance\Enums;

enum BreachActionType: string
{
    case Containment = 'containment';
    case Recovery = 'recovery';
    case Investigation = 'investigation';
    case Remediation = 'remediation';
    case StatusChange = 'status_change';
    case RiskAssessment = 'risk_assessment';
    case ImpactAnalysis = 'impact_analysis';
    case RootCause = 'root_cause';
    case LessonsLearned = 'lessons_learned';
    case Notification = 'notification';
    case Other = 'other';

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
            self::Containment => 'Containment',
            self::Recovery => 'Recovery',
            self::Investigation => 'Investigation',
            self::Remediation => 'Remediation',
            self::StatusChange => 'Status Change',
            self::RiskAssessment => 'Risk Assessment',
            self::ImpactAnalysis => 'Impact Analysis',
            self::RootCause => 'Root Cause Analysis',
            self::LessonsLearned => 'Lessons Learned',
            self::Notification => 'Notification',
            self::Other => 'Other',
        };
    }
}
