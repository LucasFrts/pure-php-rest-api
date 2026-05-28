# dot-group API

**dot-group API** é uma API REST desenvolvida em PHP puro para um processo seletivo. Sem frameworks — apenas padrões PSR, orientação a objeto e boas práticas de arquitetura.

---

## 💪 Instruções para rodar localmente

### Pré-requisitos

* Docker + Docker Compose

### Passos para rodar

1. Clone o repositório:

   ```bash
   git clone https://github.com/seu-usuario/dot-group.git
   cd dot-group
   ```

2. Suba os containers:

   ```bash
   docker compose up -d
   ```

   > As dependências PHP (composer install) são instaladas automaticamente durante a construção da imagem Docker. Você não precisa rodar `composer install` manualmente.

3. Rode as migrations:

   ```bash
   docker compose exec app php database/migrate.php
   ```

4. Rode os seeders:

   ```bash
   docker compose exec app php database/seed.php
   ```

5. A API estará disponível em `http://localhost:8080`.

### Rodando os testes

```bash
docker compose exec app ./vendor/bin/phpunit
```

---

## 📬 Documentação dos Endpoints (Bruno)

A API foi documentada utilizando o **Bruno**. Para testar os endpoints de forma fácil:

1. Instale a extensão **Bruno** no VS Code.
2. Abra a pasta `bruno/` do repositório como uma collection.
3. Selecione o environment desejado e execute as requisições.

> O Bruno usa o formato padrão de collections (similar ao Postman), sem dependência de conta em nuvem.

---

## 🧐 Processo de desenvolvimento

O objetivo foi aproveitar o processo seletivo para colocar em prática, em conjunto, coisas que havia trabalhado de forma isolada — e ver como elas se encaixam numa API completa.

Os principais conceitos explorados foram:

* **Service Container (PSR-11)** — injeção de dependências com autowiring via reflection
* **Roteamento PHP puro** — sem frameworks, com suporte a parâmetros de rota e prefixos
* **SOLID e desacoplamento via interfaces** — contratos bem definidos entre camadas
* **Entidades** — organização e representação dos dados de domínio
* **Service Layer e Repositories** — separação de responsabilidades entre regras de negócio e acesso a dados
* **Orientação a objeto** — comunicação entre serviços, aproveitando polimorfismo e encapsulamento

O que começou simples foi crescendo naturalmente, e foi satisfatório ver tudo escalando com uma arquitetura coesa.

---

## ⚙️ Funcionalidades implementadas

Todos os endpoints estão sob o prefixo `/api/v1`.

### Cursos

* [x] `GET /cursos` — listar cursos
* [x] `POST /cursos` — criar curso
* [x] `PUT /cursos/{id}` — atualizar curso
* [x] `DELETE /cursos/{id}` — remover curso

### Turmas

* [x] `POST /cursos/{cursoId}/turmas` — criar turma
* [x] `PUT /turmas/{id}` — atualizar turma
* [x] `DELETE /turmas/{id}` — remover turma
* [x] `GET /turmas` — listar turmas
* [x] `GET /cursos/{cursoId}/turmas` — listar turmas por curso

### Usuários

* [x] `GET /usuarios` — listar usuários
* [x] `POST /usuarios` — criar usuário
* [x] `DELETE /usuarios/{id}` — remover usuário

### Matrículas

* [x] `POST /matriculas` — criar matrícula
* [x] `DELETE /matriculas/{id}` — remover matrícula
* [x] `PATCH /matriculas/{id}/status` — atualizar status da matrícula
* [x] `GET /usuarios/{usuarioId}/matriculas` — listar matrículas por usuário

---

## 🧰 Testes

```bash
docker compose exec app ./vendor/bin/phpunit
```

Cobertura de testes unitários e de integração via PHPUnit.

---

## 📦 Tecnologias utilizadas

* PHP 8.3
* Composer
* Docker + Docker Compose
* MySQL
* Monolog (PSR-3)
* PHPUnit
* Bruno (documentação de API)

---

## 🚀 Retrospectiva

Foi uma boa experiência. No início não planejei algo tão complexo, mas peguei o gosto pela arquitetura e foi gratificante ver tudo crescendo de forma organizada com OOP e bons padrões.

O ponto negativo foi a quantidade de arquivos e a complexidade de alguns módulos — principalmente o core da API, que concentra bastante responsabilidade e pode ser intimidador para quem está lendo pela primeira vez.
