# Docker Infrastructure Design

**Date:** 2026-05-26  
**Status:** Approved

## Context

PHP 8.4 API (no framework, pure PHP). Entrypoint `index.php`. Routes in `routes/api.php`. Dev-only environment for a selection process project.

## Stack

| Service | Image | Role |
|---------|-------|------|
| `app` | Custom `Dockerfile` (php:8.4-fpm) | PHP-FPM process manager |
| `nginx` | `nginx:alpine` | Reverse proxy, port 8080 |
| `mysql` | `mysql:8.4` | Database |

## File Structure

```
dot-group/
├── Dockerfile
├── docker-compose.yml
└── docker/
    └── nginx/
        └── default.conf
```

## Dockerfile

- Base: `php:8.4-fpm`
- Composer binary copied from `composer:latest` via `COPY --from`
- Extensions installed: `pdo`, `pdo_mysql`, `mbstring`
- Code copied to `/var/www/html`
- Runs `composer install --no-dev`
- `WORKDIR /var/www/html`
- `EXPOSE 9000`

No opcache — dev environment.

## docker-compose.yml

### `app` service
- Build from `Dockerfile`
- Volume `.:var/www/html` for live code (no rebuild needed on change)
- Depends on `mysql`
- Environment variables for DB connection passed directly (no `.env`)

### `nginx` service
- Image: `nginx:alpine`
- Port mapping: `8080:80`
- Mounts `./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf`
- Depends on `app`

### `mysql` service
- Image: `mysql:8.4`
- Environment variables set directly in compose:
  - `MYSQL_ROOT_PASSWORD`
  - `MYSQL_DATABASE`
  - `MYSQL_USER`
  - `MYSQL_PASSWORD`
- Named volume `mysql_data` for persistence across `docker compose down/up`

### Network
Single internal network `app-network` connecting all three services.  
`nginx` → `app:9000` (FastCGI)  
`app` → `mysql:3306` (PDO)

## docker/nginx/default.conf

- Listens on port `80`
- `root /var/www/html`
- `index index.php`
- `location /`: `try_files $uri $uri/ /index.php?$query_string` — all routes fall through to `index.php`
- `location ~ \.php$`: FastCGI pass to `app:9000`, `SCRIPT_FILENAME` set to `$document_root$fastcgi_script_name`

No SSL, no cache headers — dev only.

## Decisions

- **No `.env` file**: credentials defined directly in `docker-compose.yml` (dev-only acceptable)
- **No MySQL init scripts**: migrations are the application's responsibility
- **No prod compose variant**: single environment, dev-only
- **Volume mount for code**: enables live editing without container rebuilds
