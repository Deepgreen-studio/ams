# Data Mapping Engine (Phase 2.5)

## Overview

Enterprise Field Mapping System for AMS.

Stores reusable mapping profiles per integration, maps external↔internal fields, applies transforms/defaults/custom rules, and exposes preview + validation APIs for a Visual Mapping Builder UI.

**Future modules MUST use this engine** via:

- `App\Shared\Services\Mapping\MappingEngine` for pure transform/validate logic
- `App\Domains\Integrations\Services\DataMappingService::transformWithProfile()` for persisted profiles

Never implement ad-hoc field remapping in business modules.

## Example

EasyCarbs payload:

```json
{ "customer_name": "Ada Lovelace", "weight": "62.5" }
```

Mapped to:

```json
{
  "Users": { "first_name": "Ada" },
  "Health": { "weight": 62.5 }
}
```

Field pairs:

| External | Internal | Transform |
|----------|----------|-----------|
| `customer_name` | `Users.first_name` | `split_first` |
| `weight` | `Health.weight` | `cast_float` |

## Support

- Map external fields
- Map internal fields (dotted paths, nested output)
- Transform data (trim, case, cast, date, replace, prefix/suffix, split, lookup, template)
- Custom rules (`min`, `max`, `equals`, `regex`, `in`, length checks, …)
- Required fields
- Default values

## Database

- `data_mappings` — mapping profiles (company + integration scoped)
- `data_mapping_fields` — every field mapping stored as a row

## Shared Engine

```
Shared/Services/Mapping/
  MappingEngine.php
  FieldMapper.php
  DataTransformer.php
  MappingValidator.php
  RuleEngine.php
  DTOs/FieldMappingRuleDto.php
  DTOs/MappingResultDto.php
```

## API Endpoints

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/api/v1/mappings` | integrations.view | List profiles |
| GET | `/api/v1/mappings/catalogs` | integrations.view | Transform + internal field catalogs |
| POST | `/api/v1/mappings` | integrations.create | Create profile + fields |
| GET/PUT/DELETE | `/api/v1/mappings/{uuid}` | view/update/delete | Profile CRUD |
| POST | `/api/v1/mappings/{uuid}/preview` | integrations.view | Mapping preview |
| POST | `/api/v1/mappings/{uuid}/validate` | integrations.view | Validation |

## Frontend

Sidebar **Mappings**:

- Mapping list
- Visual Mapping Builder (create/edit)
- Field Selector (datalist from catalogs + external schema)
- Transformation rules per field
- Mapping Preview + Validation on detail page

## Permissions

Uses `integrations.view|create|update|delete|manage`.

## Usage from Sync / future modules

```php
$output = app(DataMappingService::class)->transformWithProfile(
    $mappingUuid,
    $externalPayload,
    'inbound',
);
```

Or compose rules directly:

```php
$result = app(MappingEngine::class)->map($source, $rules, 'inbound');
```

## Testing

```bash
php artisan migrate
php artisan test --filter=DataMappingEngineTest
```
