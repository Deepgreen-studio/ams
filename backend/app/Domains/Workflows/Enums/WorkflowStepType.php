<?php

namespace App\Domains\Workflows\Enums;

enum WorkflowStepType: string
{
    case Start = 'start';
    case Approval = 'approval';
    case Task = 'task';
    case Condition = 'condition';
    case ParallelGateway = 'parallel_gateway';
    case End = 'end';

    public function label(): string
    {
        return match ($this) {
            self::Start => 'Start',
            self::Approval => 'Approval',
            self::Task => 'Task / Stage',
            self::Condition => 'Condition',
            self::ParallelGateway => 'Parallel Gateway',
            self::End => 'End',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
