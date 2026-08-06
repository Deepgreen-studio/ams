# Architecture

## Overview

AMS is an enterprise modular monolith with a separated API backend and Vue SPA frontend.

## Backend

- Laravel 12 API under `backend/`
- Domains live in `backend/app/Domains`
- Shared kernel lives in `backend/app/Shared`
- Controllers remain thin and delegate to Services
- Persistence uses Repositories

## Frontend

- Vue 3 SPA under `frontend/`
- Talks to `/api/v1` using Axios + Sanctum cookie authentication

## Request Flow

```text
Vue Page -> Auth Store/Service -> /api/v1/{domain}
-> FormRequest -> Controller -> Service -> Repository -> Model
-> API Resource -> ApiResponse JSON
```
