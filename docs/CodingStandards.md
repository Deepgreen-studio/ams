# Coding Standards

Primary reference (existing): **`docs/Coding-Standards.md`**

## Backend (must)

- PSR-12
- Constructor DI, typed properties, return types
- Form Requests + API Resources
- Repository + Service layers
- Enums for permissions/status where used
- No business logic in Controllers

## Frontend (must)

- Vue 3 Composition API (`<script setup>`)
- Pinia stores per module
- Axios services for HTTP
- Tailwind for styling
- Keep pages thin; reuse components

## API Response Contract

Success:

```json
{ "success": true, "message": "", "data": {} }
```

Validation:

```json
{ "success": false, "message": "Validation Failed", "errors": {} }
```

Error:

```json
{ "success": false, "message": "Unexpected Error" }
```

## Tooling

```bash
cd backend && vendor/bin/pint
cd backend && php artisan test
```
