# CRM / ERP (Laravel 12)

Sistema interno para gestao comercial e operacional, com foco atual em clientes, orcamentos e catalogo de artigos.

## Estado Atual (2026-03-27)

### Implementado

- Autenticacao completa com Laravel Breeze (login, registo, recuperacao de password, verificacao de email, perfil).
- Gestao de permissos com Spatie (`roles` + `permissions`) e middleware por rota.
- Dashboard autenticado.
- Clientes: CRUD com pesquisa e filtros.
- Orcamentos:
  - CRUD de cabecalho.
  - Serie documental ativa obrigatoria para criacao.
  - Estados com transicoes controladas (`draft`, `created`, `sent`, `waiting_response`, `accepted`, `rejected`).
  - Linhas de orcamento (adicionar, editar, remover).
  - Recalculo automatico de totais.
  - PDF via DomPDF.
  - Envio por email com SMTP definido por empresa.
  - Registo de historico de envios (`budget_email_logs`).
  - Snapshot de dados de empresa e cliente no momento de fecho.
- Catalogo:
  - Artigos (produto/servico) com CRUD e filtros.
  - Familias, marcas, unidades, taxas de IVA e motivos de isencao.
  - Upload de ficheiros por artigo (JPG/PNG/WEBP/PDF), thumbnails e imagem principal.
- Configuracoes:
  - Dados da empresa (inclui logo e dados bancarios).
  - Configuracao SMTP por empresa + envio de email de teste.
  - Series de documentos.
  - Condicoes de pagamento.

### Em estado base (placeholder de UI)

- Obras (`/obras`)
- Stock (`/stock`)
- Utilizadores (`/utilizadores`)

### Fora do escopo atual

- Faturacao certificada AT (nao implementado neste projeto).
- API publica dedicada (rotas atuais sao web).

## Stack Tecnologica

### Backend

- PHP `^8.2`
- Laravel `^12.0`
- Spatie Laravel Permission `^6.25`
- DomPDF `^3.1`
- Doctrine DBAL `^4.4`

### Frontend

- Blade
- Template admin Porto (areas principais)
- Tailwind + Vite (layouts Breeze/auth/profile)
- Alpine.js

### Dados e runtime

- MySQL (recomendado para este projeto)
- Sessao/Cache/Queue por base de dados (defaults do `.env.example`)
- Filesystem local/public

## Arquitetura e Convencoes

- Separacao de validacao com `FormRequest`.
- Regras de negocio de orcamentos extraidas para `app/Actions/Budgets`.
- Isolamento por utilizador (`owner_id`) nos modelos de dominio.
- Controlo de acesso por:
  - middleware de permissao nas rotas
  - verificacao de ownership em controllers
- Upload seguro de ficheiros com validacao por MIME real e limites.

## Modulos e Rotas Principais

- Dashboard
- Clientes (`customers.*`)
- Orcamentos (`budgets.*`)
- Linhas de orcamento (`budgets.items.*`)
- Catalogo:
  - Artigos (`items.*`, `items.files.*`)
  - Familias (`item-families.*`)
  - Marcas (`brands.*`)
  - Unidades (`units.*`)
  - Taxas (`tax-rates.*`)
  - Motivos de isencao (`tax-exemption-reasons.*`)
- Sistema/Configuracoes:
  - Perfil da empresa (`company-profile.*`)
  - Series (`document-series.*`)
  - Condicoes de pagamento (`payment-terms.*`)

Comando para listar tudo:

```bash
php artisan route:list --except-vendor
```

No estado atual sao mostradas 93 rotas.

## Perfis e Permissoes (seed)

Seeder principal cria roles:

- `admin`
- `tecnico`
- `comercial`
- `funcionario`

Permissoes existentes incluem, entre outras:

- `customers.*`
- `budgets.*` (inclui `budgets.update`)
- `items.*`
- `jobs.*`
- `stock.*`
- `users.*`
- `settings.manage`

## Seeders Disponiveis

Incluidos no `DatabaseSeeder`:

- `RolesAndPermissionsSeeder`
- `AdminUserSeeder`
- `TaxExemptionReasonSeeder`
- `TaxRateSeeder`
- `UnitSeeder`

Disponiveis mas nao chamados automaticamente:

- `PaymentTermSeeder`
- `DocumentSeriesSeeder`

## Requisitos

- PHP 8.2+
- Composer
- Node.js + npm (compatibilidade com Vite 7)
- MySQL/MariaDB
- Extensoes PHP usuais do Laravel
- Extensao `gd` ativa (necessaria para gerar thumbnails de imagens de artigos)

## Instalacao

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurar no `.env`:

- Base de dados (`DB_*`)
- Utilizador admin inicial (usado pelo `AdminUserSeeder`):
  - `ADMIN_USER_NAME` (opcional, default: `Administrador`)
  - `ADMIN_USER_EMAIL` (obrigatorio para criar admin)
  - `ADMIN_USER_PASSWORD` (obrigatorio para criar admin)

Migrar e popular:

```bash
php artisan migrate
php artisan db:seed
```

Opcional (dados base adicionais):

```bash
php artisan db:seed --class=PaymentTermSeeder
php artisan db:seed --class=DocumentSeriesSeeder
```

Frontend:

```bash
npm install
npm run dev
```

Ou build de producao:

```bash
npm run build
```

Link de storage publico (logo da empresa, etc):

```bash
php artisan storage:link
```

## Primeiro Arranque Recomendado

1. Entrar com o admin criado por seed.
2. Configurar "Dados da Empresa".
3. Configurar SMTP da empresa (e testar envio).
4. Criar uma serie ativa para `budget` em "Series de Documentos".
5. Criar condicoes de pagamento.
6. Criar clientes e artigos.
7. Criar orcamento e adicionar linhas.

## Comandos Uteis

```bash
# Ambiente de desenvolvimento completo (server + queue + logs + vite)
composer run dev

# Servidor local simples
php artisan serve

# Logs
php artisan pail

# Queue worker/listener
php artisan queue:listen

# Testes
php artisan test
```

## Testes e Limitacoes Conhecidas

- A suite atual falha em ambiente de teste SQLite (`:memory:`) por migracao especifica de MySQL:
  - `2026_03_23_200605_alter_status_column_on_budgets_table.php`
  - usa `ALTER TABLE ... MODIFY status ENUM(...)`, que nao e suportado por SQLite.
- `tests/Feature/ExampleTest.php` ainda assume o comportamento inicial de homepage (espera `200` em `/`).

Para ambiente local e producao, usar MySQL/MariaDB.

## Estrutura Resumida do Projeto

```text
app/
  Actions/Budgets/
  Http/Controllers/
  Http/Requests/
  Mail/
  Models/
config/
database/
  migrations/
  seeders/
resources/views/
routes/
```

## Notas Finais

- Projeto em evolucao ativa.
- README atualizado para refletir o codigo existente nesta data.
