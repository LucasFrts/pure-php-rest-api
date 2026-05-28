# README Design — dot-group API

**Date:** 2026-05-28  
**Topic:** Rewrite README.md following tray-vendas-main narrative pattern

---

## Goal

Replace the current technical-reference README with a narrative/personal style README, matching the structure and tone of `tray-vendas-main/README.md`. Keep all setup information, add retrospective and learning motivation sections, and document the Bruno API collection.

---

## Structure

### 1. Title + Description

Project name + 2–3 sentence intro: pure PHP REST API built for a selection process (dot-group), no frameworks, PSR standards only.

### 2. Instruções para rodar localmente

**Pré-requisitos:**
- Docker + Docker Compose

**Passos:**
1. Clone o repositório
2. `docker compose up -d`
3. `docker compose exec app php database/migrate.php`
4. `docker compose exec app php database/seed.php`
5. Testes: `./vendor/bin/phpunit`

### 3. Documentação dos Endpoints (Bruno)

Short section recommending Bruno VSCode extension. User opens the `bruno/` folder as a collection — standard Postman/collection format. Endpoints are pre-configured and ready to use.

### 4. Processo de desenvolvimento

Narrative about the experience: the goal was to use a selection process as an opportunity to put together things practiced in isolation — service container (PSR-11), pure PHP routing, SOLID principles, decoupling via interfaces, entities for data organization, service and repository classes, OOP communication advantages. The project grew in complexity organically.

### 5. Funcionalidades implementadas

Checklist of all endpoints under `/api/v1`:

**Cursos**
- [x] `GET /cursos` — listar cursos
- [x] `POST /cursos` — criar curso
- [x] `PUT /cursos/{id}` — atualizar curso
- [x] `DELETE /cursos/{id}` — remover curso

**Turmas**
- [x] `POST /cursos/{cursoId}/turmas` — criar turma
- [x] `PUT /turmas/{id}` — atualizar turma
- [x] `DELETE /turmas/{id}` — remover turma
- [x] `GET /turmas` — listar turmas
- [x] `GET /cursos/{cursoId}/turmas` — listar turmas por curso

**Usuários**
- [x] `GET /usuarios` — listar usuários
- [x] `POST /usuarios` — criar usuário
- [x] `DELETE /usuarios/{id}` — remover usuário

**Matrículas**
- [x] `POST /matriculas` — criar matrícula
- [x] `DELETE /matriculas/{id}` — remover matrícula
- [x] `PATCH /matriculas/{id}/status` — atualizar status
- [x] `GET /usuarios/{usuarioId}/matriculas` — listar matrículas por usuário

### 6. Testes

`./vendor/bin/phpunit` — unit + integration test suite.

### 7. Tecnologias

- PHP 8.3
- Composer
- Docker + Docker Compose
- MySQL
- Monolog (PSR-3)
- PHPUnit
- Bruno (API docs)

### 8. Retrospectiva

**Prós:** Boa experiência geral. A arquitetura escalou bem — foi satisfatório ver tudo crescendo com OOP e bons padrões.  
**Contras:** Quantidade de arquivos e complexidade de alguns módulos, principalmente o core da API.

---

## Out of Scope

- Keeping the current technical architecture docs (code examples, DI container reference) — those are replaced entirely
- No separate docs file for the old content
