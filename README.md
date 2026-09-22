# Pure PHP REST API

API REST escrita em **PHP puro**, sem framework. Service container PSR-11 com autowiring, roteador próprio, camada de serviço e repositórios, tudo coberto por testes.

Nasceu como desafio técnico e virou um exercício de arquitetura: reconstruir à mão o que um framework entrega pronto, para entender cada peça.

---

## 🏗️ Arquitetura

```
app/
├── Contracts/      interfaces de serviços, repositórios e do core
├── Entities/       entidades de domínio
├── Enums/          tipos fechados (status de matrícula, de turma)
├── Exceptions/     exceções HTTP mapeadas para status code
├── Http/           controllers finos, sem regra de negócio
├── Providers/      registro de bindings no container
├── Repositories/   acesso a dados
├── Services/       regra de negócio
└── Support/        o core: Container, Dispatcher, Request, Response,
                    RequestValidator, ExceptionHandler, Logger, Config
```

O que o `Support/` resolve, sem framework:

* **`Container`** — service container PSR-11 com resolução automática de dependências via Reflection. Registra bindings por interface e instancia o grafo inteiro sozinho.
* **`Dispatcher`** — roteador com suporte a parâmetros de rota e prefixos de grupo.
* **`ExceptionHandler`** — exceções de domínio viram resposta HTTP com o status correto, num ponto só.
* **`RequestValidator`** — validação de entrada antes de chegar no controller.
* **`Logger`** — Monolog atrás da interface PSR-3.

Controllers dependem de interfaces, nunca de implementações. Trocar o repositório de MySQL por outro é registrar outro binding no provider.

---

## 💪 Rodando localmente

**Pré-requisito:** Docker + Docker Compose

```bash
git clone https://github.com/LucasFrts/pure-php-rest-api.git
cd pure-php-rest-api
docker compose up -d
docker compose exec app php database/migrate.php
docker compose exec app php database/seed.php
```

> O `composer install` roda automaticamente na construção da imagem.

A API sobe em `http://localhost:8080`.

### Testes

```bash
docker compose exec app ./vendor/bin/phpunit
```

Testes unitários e de integração com PHPUnit.

---

## 📬 Documentação dos endpoints

A collection está em `bruno/`, no formato do [Bruno](https://www.usebruno.com/) — padrão aberto, similar ao Postman, sem depender de conta em nuvem. Abra a pasta como collection, selecione o environment e execute.

---

## ⚙️ Endpoints

Todos sob o prefixo `/api/v1`.

### Cursos
| Método | Rota | O que faz |
|---|---|---|
| `GET` | `/cursos` | listar cursos |
| `POST` | `/cursos` | criar curso |
| `PUT` | `/cursos/{id}` | atualizar curso |
| `DELETE` | `/cursos/{id}` | remover curso |

### Turmas
| Método | Rota | O que faz |
|---|---|---|
| `GET` | `/turmas` | listar turmas |
| `GET` | `/cursos/{cursoId}/turmas` | listar turmas por curso |
| `POST` | `/cursos/{cursoId}/turmas` | criar turma |
| `PUT` | `/turmas/{id}` | atualizar turma |
| `DELETE` | `/turmas/{id}` | remover turma |

### Usuários
| Método | Rota | O que faz |
|---|---|---|
| `GET` | `/usuarios` | listar usuários |
| `POST` | `/usuarios` | criar usuário |
| `DELETE` | `/usuarios/{id}` | remover usuário |

### Matrículas
| Método | Rota | O que faz |
|---|---|---|
| `POST` | `/matriculas` | criar matrícula |
| `PATCH` | `/matriculas/{id}/status` | atualizar status |
| `DELETE` | `/matriculas/{id}` | remover matrícula |
| `GET` | `/usuarios/{usuarioId}/matriculas` | listar matrículas do usuário |

---

## 📦 Stack

PHP 8.3 · Composer · Docker · MySQL · Monolog (PSR-3) · PHPUnit · Bruno

Padrões aplicados: PSR-11 (container), PSR-3 (log), SOLID, Service Layer, Repository, Dependency Injection, Enums.

---

## 🚀 Retrospectiva

No início não planejei algo tão complexo. Peguei o gosto pela arquitetura e foi gratificante ver tudo crescendo de forma organizada com OOP e bons padrões.

O ponto negativo foi a quantidade de arquivos e a complexidade de alguns módulos — principalmente o core, que concentra bastante responsabilidade e pode ser intimidador para quem lê pela primeira vez. Se fosse refazer, quebraria o `Support/` em pacotes menores com fronteiras mais explícitas.
