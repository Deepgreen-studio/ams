<?php

namespace App\Domains\Customers\Support;

use App\Domains\Customers\Models\Industry;

final class IndustryCatalog
{
    /**
     * Controlled industry master data. Sub-industries reference a parent code.
     *
     * @return list<array{code: string, name: string, is_other?: bool, children?: list<array{code: string, name: string}>}>
     */
    public static function definitions(): array
    {
        return [
            [
                'code' => 'technology',
                'name' => 'Technology',
                'children' => [
                    ['code' => 'software', 'name' => 'Software'],
                    ['code' => 'saas', 'name' => 'Software as a Service'],
                    ['code' => 'cybersecurity', 'name' => 'Cybersecurity'],
                    ['code' => 'telecommunications', 'name' => 'Telecommunications'],
                ],
            ],
            [
                'code' => 'financial_services',
                'name' => 'Financial Services',
                'children' => [
                    ['code' => 'banking', 'name' => 'Banking'],
                    ['code' => 'insurance', 'name' => 'Insurance'],
                ],
            ],
            [
                'code' => 'healthcare',
                'name' => 'Healthcare',
                'children' => [
                    ['code' => 'healthcare_providers', 'name' => 'Healthcare Providers'],
                    ['code' => 'life_sciences', 'name' => 'Life Sciences'],
                ],
            ],
            ['code' => 'retail', 'name' => 'Retail'],
            ['code' => 'education', 'name' => 'Education'],
            ['code' => 'manufacturing', 'name' => 'Manufacturing'],
            ['code' => 'professional_services', 'name' => 'Professional Services'],
            ['code' => 'government', 'name' => 'Government and Public Sector'],
            ['code' => 'media', 'name' => 'Media and Entertainment'],
            ['code' => 'energy', 'name' => 'Energy and Utilities'],
            ['code' => 'transportation', 'name' => 'Transportation and Logistics'],
            ['code' => 'hospitality', 'name' => 'Hospitality'],
            ['code' => 'other', 'name' => 'Other', 'is_other' => true],
        ];
    }

    public static function sync(): void
    {
        $sort = 0;

        foreach (self::definitions() as $definition) {
            $sort += 10;
            $parent = Industry::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'parent_id' => null,
                    'name' => $definition['name'],
                    'is_other' => (bool) ($definition['is_other'] ?? false),
                    'is_active' => true,
                    'sort_order' => $sort,
                ]
            );

            $childSort = 0;

            foreach ($definition['children'] ?? [] as $child) {
                $childSort += 10;
                Industry::query()->updateOrCreate(
                    ['code' => $child['code']],
                    [
                        'parent_id' => $parent->id,
                        'name' => $child['name'],
                        'is_other' => false,
                        'is_active' => true,
                        'sort_order' => $childSort,
                    ]
                );
            }
        }
    }
}
