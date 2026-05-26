# Docker Infrastructure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a working Docker stack (Nginx + PHP-FPM 8.4 + MySQL 8.4) that serves the PHP API on port 8080.

**Architecture:** Nginx receives HTTP on port 8080 and proxies PHP requests via FastCGI to the `app` service (PHP-FPM). The `app` container is built from a custom Dockerfile based on `php:8.4-fpm` with `pdo_mysql` and `mbstring` extensions. MySQL 8.4 runs as a separate service with a named volume for persistence.

**Tech Stack:** Docker, PHP 8.4-FPM, Nginx Alpine, MySQL 8.4, Composer

---

## File Map

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `Dockerfile` | PHP 8.4-FPM image with extensions + composer install |
| Create | `docker-compose.yml` | Orchestrate app, nginx, mysql services |
| Create | `docker/nginx/default.conf` | Nginx reverse proxy config for PHP-FPM |

---

### Task 1: Create Nginx config

**Files:**
- Create: `docker/nginx/default.conf`

- [ ] **Step 1: Create the directory**

```bash
mkdir -p docker/nginx
```

- [ ] **Step 2: Create `docker/nginx/default.conf`**

```nginx
server {
    listen 80;
    root /var/www/html;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add docker/nginx/default.conf
git commit -m "infra: add nginx fastcgi config"
```

---

### Task 2: Create Dockerfile

**Files:**
- Create: `Dockerfile`

- [ ] **Step 1: Create `Dockerfile`**

```dockerfile
FROM php:8.4-fpm

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install pdo pdo_mysql mbstring

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 9000
```

- [ ] **Step 2: Verify build succeeds**

```bash
docker build -t dot-group-app .
```

Expected: Build completes with no errors. Last line should be something like `naming to docker.io/library/dot-group-app`.

- [ ] **Step 3: Commit**

```bash
git add Dockerfile
git commit -m "infra: add PHP 8.4-FPM Dockerfile"
```

---

### Task 3: Create docker-compose.yml

**Files:**
- Create: `docker-compose.yml`

- [ ] **Step 1: Create `docker-compose.yml`**

```yaml
services:
  app:
    build: .
    volumes:
      - .:/var/www/html
    networks:
      - app-network
    depends_on:
      - mysql

  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - app-network
    depends_on:
      - app

  mysql:
    image: mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: dot_group
      MYSQL_USER: dot
      MYSQL_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - app-network

networks:
  app-network:
    driver: bridge

volumes:
  mysql_data:
```

- [ ] **Step 2: Start the stack**

```bash
docker compose up -d
```

Expected: All 3 containers start. No `Exited` status.

- [ ] **Step 3: Verify all containers are running**

```bash
docker compose ps
```

Expected output (STATUS column should show `Up` or `running` for all):
```
NAME                    IMAGE           STATUS
dot-group-app-1         dot-group-app   Up
dot-group-nginx-1       nginx:alpine    Up
dot-group-mysql-1       mysql:8.4       Up
```

- [ ] **Step 4: Verify the API responds**

```bash
curl -s http://localhost:8080/turmas
```

Expected:
```json
{"message":"só o file"}
```

- [ ] **Step 5: Verify MySQL is reachable from app container**

```bash
docker compose exec app php -r "
new PDO('mysql:host=mysql;dbname=dot_group', 'dot', 'secret');
echo 'OK';
"
```

Expected: `OK`

- [ ] **Step 6: Commit**

```bash
git add docker-compose.yml
git commit -m "infra: add docker compose with app, nginx and mysql services"
```
