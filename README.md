# CRM / ERP Laravel

Sistema web interno para gestao comercial e operacional, desenvolvido em Laravel, com foco atual em clientes, orcamentos, catalogo de artigos e controlo de acesso por permissoes.

Objetivo do projeto:
- Centralizar operacao comercial (clientes e orcamentos).
- Suportar ciclo de vida do orcamento (criacao, linhas, estado, PDF e envio).
- Estruturar base de ERP com multi-tenant logico por `owner_id`.

# Funcionalidades

Funcionalidades implementadas no estado atual do codigo:

- Autenticacao com Laravel Breeze:
- Login, registo, recuperacao de password, verificacao de email, perfil de utilizador.

- Gestao de clientes:
- CRUD completo (`index/create/store/show/edit/update/destroy`).
- Pesquisa e filtros.
- Isolamento por `owner_id`.

- Orcamentos (budgets):
- CRUD de cabecalho.
- Numeracao por serie documental (`document_series`) com incremento controlado.
- Estados com transicoes controladas:
- `draft`, `created`, `sent`, `waiting_response`, `accepted`, `rejected`.
- Linhas de orcamento (`budget_items`):
- Adicionar, editar e remover linhas.
- Snapshot de dados do artigo nas linhas (nome, preco, IVA, etc.).
- Recalculo automatico de totais (`subtotal`, `discount_total`, `tax_total`, `total`).
- Snapshot documental no orcamento ao fechar (`captureDocumentSnapshot()`):
- Dados da empresa e do cliente ficam congelados no documento.
- Geracao de PDF de orcamento (DomPDF).
- Envio de email com anexo PDF:
- SMTP dinamico por empresa (`company_profiles`).
- Log de envios em `budget_email_logs`.

- Catalogo de artigos:
- CRUD de artigos/servicos (`items`) com tipo `product`/`service`.
- Validacao de regras de stock (ex.: `max_stock >= min_stock` quando aplicavel).
- Filtros no index por pesquisa, tipo, estado, familia e marca.
- Upload de anexos do artigo:
- Imagens e PDF.
- Validacao por MIME real.
- Thumbnails para imagens.
- Definicao de imagem principal.
- Remocao de anexos.
- Servir ficheiros via rota autenticada (`items.files.show`) em vez de URL publica direta.

- Catalogos auxiliares:
- Familias de artigos (`item_families`).
- Marcas (`brands`).
- Unidades (`units`).
- Taxas de IVA (`tax_rates`).
- Motivos de isencao (`tax_exemption_reasons`).

- Configuracoes:
- Perfil da empresa (`company_profiles`) com dados legais/comerciais.
- Configuracao SMTP da empresa + email de teste.
- Series documentais (`document_series`).
- Condicoes de pagamento (`payment_terms`).

- Activity Log (auditoria):
- Registo estruturado de eventos de dominio.
- Ecran de consulta com filtros.

- Sistema de permissoes:
- Spatie Laravel Permission (`roles` + `permissions`).
- Middleware por rota (`permission:*`).
- Policies por modelo.

- Multi-tenant logico:
- Isolamento por `owner_id` nos modelos de dominio relevantes.

# Stack Tecnologica

- PHP: `^8.2`
- Laravel: `^12.0`
- Base de dados:
- MySQL/MariaDB recomendado para execucao normal.
- SQLite aparece no `.env.example`, mas existe migracao com SQL especifico MySQL para `ENUM` (ver nota em "Desenvolvimento").

Packages relevantes:
- `spatie/laravel-permission` (ACL por roles/permissoes)
- `barryvdh/laravel-dompdf` (PDF de orcamentos)
- `doctrine/dbal` (alteracoes de schema)
- `laravel/breeze` (auth scaffolding)

Frontend:
- Blade
- Vite
- Tailwind CSS (layout Breeze)
- Alpine.js

# Arquitetura do Projeto

Organizacao atual:

- Controllers (`app/Http/Controllers`)
- Camada HTTP/orquestracao por modulo (`CustomerController`, `BudgetController`, `ItemController`, etc.).

- Models (`app/Models`)
- Entidades de dominio (`Customer`, `Budget`, `BudgetItem`, `Item`, `ItemFile`, `ActivityLog`, etc.).
- Uso de relacoes Eloquent, casts e scopes.

- FormRequests (`app/Http/Requests`)
- Validacao de input por contexto (customers, budgets, items, activity logs, auth).
- Regras multi-tenant e de negocio aplicadas no request quando relevante.

- Actions (`app/Actions/Budgets`)
- Logica de negocio de orcamentos separada:
- `AddItemToBudgetAction`
- `UpdateBudgetItemAction`
- `RecalculateBudgetTotalsAction`
- `ChangeBudgetStatusAction`

- Services (`app/Services`)
- `ActivityLogService` centraliza gravacao de logs de atividade.

- Policies (`app/Policies`)
- Autorizacao por modelo para varios modulos (`BudgetPolicy`, `ItemPolicy`, `BrandPolicy`, etc.).

- Views Blade (`resources/views`)
- Estrutura por modulo (`customers`, `budgets`, `items`, `activity-logs`, `settings`/catalogos).

# Seguranca

Medidas implementadas no codigo:

- Autorizacao por Policy:
- Ex.: `BudgetPolicy`, `ItemPolicy` e outras, com verificacao de ownership (`owner_id`) quando aplicavel.

- Middleware de permissao por rota:
- Ex.: `permission:customers.view`, `permission:budgets.update`, `permission:items.edit`.

- Validacao multi-tenant:
- Em `FormRequests` e consultas com `owner_id` para restringir entidades por utilizador.

- Protecao contra IDOR:
- Validacoes de associacao entre recursos relacionados (ex.: `ItemFile` pertence ao `Item`, `BudgetItem` pertence ao `Budget`) antes de operar.

- Upload de ficheiros endurecido:
- Validacao por MIME real.
- Limites de tamanho/quantidade.
- Validacao extra de imagem (`getimagesize`) e dimensoes maximas.
- Armazenamento em `local` (privado) com entrega por rota autenticada.

- XSS/Sanitizacao:
- Views Blade usam escaping por defeito (`{{ }}`).
- Quando ha output formatado, e aplicado escaping explicito antes de transformar (`nl2br(e(...))`).

- CSRF:
- Formularios protegidos com token CSRF do Laravel.

# Activity Log

Implementacao baseada em:
- Model: `ActivityLog`
- Service: `ActivityLogService`
- Controller/UI: `ActivityLogController` + `resources/views/activity-logs`

O que esta a ser registado (no estado atual):
- Orcamentos:
- `created`, `updated`, `deleted`, `status_changed`, `email_sent`
- Linhas de orcamento (`budget_item`):
- `created`, `updated`, `deleted`

Estrutura dos registos (`activity_logs`):
- `owner_id`
- `user_id`
- `action`
- `entity`
- `entity_id`
- `payload` (JSON com detalhes do evento)
- `created_at` / `updated_at`

Uso no sistema:
- Gravacao invocada em controllers de budgets e budget items apos operacoes relevantes.
- Consulta via rota protegida `activity-logs.index`.

# Instalacao

```bash
git clone <url-do-repositorio>
cd crm-erp
composer install
cp .env.example .env
php artisan key:generate
```

Configurar base de dados no `.env` (ver secao "Configuracao"), depois:

```bash
php artisan migrate
php artisan db:seed
```

Instalar assets frontend:

```bash
npm install
npm run dev
```

Iniciar aplicacao:

```bash
php artisan serve
```

# Configuracao

Base de dados:
- Definir `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Recomendado MySQL/MariaDB para este projeto.

Email SMTP:
- Existem dois niveis:
- Global `.env` (`MAIL_*`) para defaults Laravel.
- SMTP por empresa em `company_profiles` (usado no envio real de orcamentos e email de teste da empresa).

Permissoes:
- Seeder `RolesAndPermissionsSeeder` cria permissoes e roles base.
- Admin inicial via `AdminUserSeeder` (requer variaveis de admin no `.env` quando aplicavel).

# Utilizacao

Fluxo funcional tipico:

1. Entrar no sistema com utilizador autenticado.
2. Criar/validar cliente.
3. Criar orcamento.
4. Adicionar linhas de orcamento.
5. Atualizar estado do orcamento.
6. Gerar PDF e enviar por email.
7. Consultar activity logs para auditoria.

Para catalogo:

1. Criar/editar artigo.
2. Associar anexos (imagem/PDF) no ecran de edicao.
3. Definir imagem principal quando necessario.

# Permissoes

Sistema baseado em Spatie:
- Tabelas de ACL via migration `create_permission_tables`.
- Middleware `permission:*` nas rotas.
- Policies para autorizacao por modelo.

Roles base seedadas:
- `admin`
- `tecnico`
- `comercial`
- `funcionario`

Conjunto de permissoes inclui, entre outras:
- `customers.*`
- `budgets.*` (inclui `budgets.update`)
- `items.*`
- `activity-logs.view`
- `settings.manage`
- `users.*`, `jobs.*`, `stock.*`

# Estrutura de Pastas (resumo)

```text
app/
  Actions/
  Services/
  Models/
  Http/Controllers/
  Http/Requests/
  Policies/
resources/views/
routes/
database/
  migrations/
  seeders/
```

# Desenvolvimento

Convencoes visiveis no codigo:

- Controllers relativamente leves, com delegacao de regras de negocio para Actions/Services quando a complexidade aumenta (especialmente em Orcamentos).
- Validacao centralizada em FormRequests.
- Autorizacao combinando middleware de permissao + policies.
- Modelos com foco em relacoes e estado de dominio.
- Isolamento multi-tenant por `owner_id`.

Nota tecnica importante:
- A migration [2026_03_23_200605_alter_status_column_on_budgets_table.php](/c:/xampp/htdocs/crm-erp/database/migrations/2026_03_23_200605_alter_status_column_on_budgets_table.php) usa `ALTER TABLE ... MODIFY ... ENUM`, SQL especifico de MySQL.
- Isto quebra a suite quando corre com SQLite em memoria.

# Roadmap (opcional)

Nao existe ficheiro de roadmap formal no repositório. Pelo estado do codigo, areas com evolucao natural:

- Completar modulos placeholder de operacao (`obras`, `stock`, `utilizadores`) com dominio real.
- Aumentar cobertura de testes automatizados por modulo.
- Consolidar compatibilidade da suite de testes com o driver de BD usado em CI.
- Evoluir filas/jobs para envios de email em background.

# Licenca

Projeto privado para uso interno.

