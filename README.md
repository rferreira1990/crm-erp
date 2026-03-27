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

## Futuras Features (Roadmap Expandido)

Esta secao detalha o backlog recomendado para evoluir o projeto de forma estruturada.

### Fase 1 - Consolidacao funcional (curto prazo)

- Obras:
  - CRUD completo de obras.
  - Ligacao obra <-> cliente.
  - Estados da obra (planeada, em curso, suspensa, concluida, cancelada).
  - Datas previstas e reais (inicio/fim).
  - Responsavel tecnico e equipa associada.
  - Notas internas por obra.
- Stock:
  - Movimentos de entrada/saida/ajuste.
  - Saldo atual por artigo.
  - Historico de movimentos com utilizador/data.
  - Regras de validacao para impedir saldo negativo (configuravel).
  - Alertas de stock minimo.
- Utilizadores:
  - CRUD real de utilizadores.
  - Gestao de roles e permissoes na UI.
  - Ativar/desativar utilizadores.
  - Reset de password por admin.
- Orcamentos:
  - Duplicar orcamento.
  - Versionamento (V1, V2, V3).
  - Conversao de orcamento aceite em obra.
  - Exportacao PDF com templates alternativos.
  - Historico de alteracoes de estado mais detalhado.

### Fase 2 - Operacao em campo

- Diario de obra:
  - Registos diarios por tecnico.
  - Horas gastas por atividade.
  - Materiais aplicados.
  - Observacoes e ocorrencias.
- Ficheiros e media:
  - Upload de fotos por obra.
  - Associacao de documentos tecnicos (manuais, certificados).
  - Compressao e redimensionamento automatico.
  - Metadados (quem carregou, quando, tipo).
- Checklist e qualidade:
  - Checklists por tipo de obra.
  - Itens obrigatorios antes de fechar obra.
  - Registo de nao conformidades e resolucao.

### Fase 3 - Comercial e CRM avancado

- Pipeline comercial:
  - Leads, oportunidades e fases de venda.
  - Probabilidade e valor previsto.
  - Motivo de perda de oportunidade.
- Atividades comerciais:
  - Tarefas, chamadas, reunioes e lembretes.
  - Historico por cliente e por oportunidade.
  - Templates de email comercial.
- Conversao:
  - Oportunidade -> Orcamento.
  - Orcamento aceite -> Obra.

### Fase 4 - Compras e fornecedores

- Fornecedores:
  - CRUD completo.
  - Contactos, condicoes comerciais e prazos.
- Compras:
  - Pedido de cotacao (RFQ).
  - Comparacao de propostas.
  - Encomendas a fornecedor.
  - Rececao parcial/total de materiais.
  - Atualizacao automatica de stock por rececao.

### Fase 5 - Financeiro operacional (nao AT)

- Conta corrente de cliente:
  - Debitos e creditos manuais.
  - Estado de conta por periodo.
  - Registo de recebimentos.
- Conta corrente de fornecedor:
  - Registo de faturas de compra e pagamentos.
- Indicadores:
  - Mapa de valores em aberto.
  - Aging de saldos.

### Fase 6 - Agenda e planeamento

- Agenda:
  - Calendario por tecnico/equipa.
  - Planeamento de intervencoes.
  - Vista diaria, semanal e mensal.
- Alocacao de recursos:
  - Conflitos de agenda.
  - Capacidade por equipa.
  - Reagendamento drag-and-drop (futuro UI).

### Fase 7 - Integracoes

- Email:
  - Filas para envio assincorno de emails.
  - Reenvio controlado e idempotencia.
- Telegram:
  - Registo rapido de consumos por chat.
  - Upload de fotos para obra via bot.
  - Consulta rapida de estado de obra/orcamento.
- IA (assistente interno):
  - Interpretacao de notas de obra em texto livre.
  - Sugestao de descricao tecnica para orcamentos.
  - Apoio na classificacao de tickets/ocorrencias.
  - Resumo diario de atividade por equipa.
- Calendario externo:
  - Sincronizacao com Google Calendar / Microsoft 365.

### Fase 8 - Relatorios e BI

- Dashboards:
  - Orcamentos por estado, taxa de fecho, ticket medio.
  - Obras por estado, atraso medio, produtividade.
  - Artigos mais usados e rotacao de stock.
- Relatorios exportaveis:
  - CSV/XLSX/PDF por modulo.
  - Relatorios agendados por email.
- Auditoria:
  - Trilho de alteracoes criticas por entidade.
  - Consultas por periodo/utilizador/acao.

## Backlog Tecnico Recomendado

### Arquitetura e codigo

- Introduzir policies para autorizacao por modelo (alem de checks em controller).
- Normalizar Actions/Services para todos os modulos (nao apenas orcamentos).
- Criar DTOs/form objects para fluxos com payload complexo.
- Adicionar eventos de dominio (ex.: `BudgetSent`, `BudgetAccepted`).
- Criar observers para auditoria automatica.

### Base de dados

- Rever naming de migrations e corrigir ficheiros com nome duplicado/inconsistente.
- Garantir compatibilidade total com MySQL (ambiente alvo) e documentar limites de SQLite.
- Adicionar indices para consultas mais frequentes (filtros por owner/status/data).
- Definir estrategia de soft delete por modulo.

### Testes

- Corrigir migracao de `ENUM` para nao quebrar testes SQLite ou migrar suite para MySQL de teste.
- Cobertura de testes:
  - Feature tests para clientes, artigos, orcamentos e permissoes.
  - Unit tests para Actions de calculo e transicao de estado.
  - Testes de validacao para FormRequests.
- Introduzir factories completas para modelos de dominio.

### Filas e jobs

- Passar envio de email de orcamento para job em queue.
- Retry/backoff configuravel.
- Dead-letter handling para falhas recorrentes.

### Observabilidade e operacao

- Logging estruturado para fluxos criticos.
- Correlation ID por request.
- Healthcheck endpoint para monitorizacao.
- Rotina de backup e restore validada.

### Seguranca

- Harden de upload (antivirus opcional, limites por tenant, quotas).
- Rate limiting especifico em endpoints sensiveis.
- Reforco de CSP e headers de seguranca.
- 2FA para perfis administrativos (futuro).

## Melhorias de Produto (UX/UI)

- Pesquisa global multi-modulo.
- Comandos rapidos no topo (quick actions).
- Filtros guardados por utilizador.
- Modo impressao para documentos tecnicos.
- Atalhos de teclado em ecras de operacao intensa.
- Feedback visual de estados (badges padronizadas por modulo).
- Centro de notificacoes internas.

## API e Ecossistema (futuro)

- Expor API REST autenticada para integracoes.
- Webhooks de eventos principais (orcamento criado/enviado/aceite).
- Chaves API por utilizador/sistema integrador.
- Rate limit e scopes por token.
- OpenAPI/Swagger para documentacao tecnica.

## Escalabilidade e Multi-tenant

- Evoluir de isolamento logico por `owner_id` para estrategia tenant-aware mais formal.
- Politicas claras para dados partilhados vs dados por tenant.
- Quotas por tenant (ficheiros, emails, registos).
- Preparacao para jobs/queues multi-tenant com contexto isolado.

## Criterios de Priorizacao Sugeridos

- Primeiro: funcionalidades com maior impacto operacional diario (Obras + Stock + Utilizadores).
- Depois: automacao comercial (pipeline + conversoes).
- Em paralelo: qualidade tecnica (testes, queue, observabilidade).
- Por fim: integracoes externas e features avancadas de BI/IA.

## Notas Finais

- Projeto em evolucao ativa.
- README atualizado para refletir o codigo existente nesta data.
