# ⚡ CRM / ERP - Gestão de Obras e Instalações Elétricas

Sistema web interno para gestão completa de uma empresa de eletricidade e obras.

---

# 🎯 Objetivo

Criar uma plataforma simples, prática e escalável para gerir todo o fluxo de trabalho:

- CRM (clientes e oportunidades)
- Orçamentos
- Gestão de obras
- Materiais e stock
- Fornecedores
- Conta corrente
- Agenda
- Integração com Telegram
- Apoio com IA

⚠️ Nota: Este sistema **NÃO emite faturação certificada pela AT**.  
Serve apenas para gestão interna, controlo operacional e apoio comercial.

---

# 🛠️ Tecnologias

## Backend
- Laravel (PHP)

## Base de dados
- MySQL

## Frontend
- Blade
- Bootstrap
- Template: Porto Admin

## Integrações previstas
- Telegram Bot API
- OpenAI API (interpretação de mensagens)
- Google Calendar

## Ambiente de desenvolvimento
- XAMPP
- Composer
- Node.js / NPM
- VS Code

---

## 📦 Módulos do Sistema
1. CRM
    Gestão de leads e oportunidades
    Histórico de interações
    Tarefas e follow-ups
2. Clientes
    Particulares e empresas
    NIF opcional (suporte a consumidor final)
    Contactos e moradas
    Condições comerciais
3. Orçamentos
    Criação e gestão
    PDF
    Conversão em obra
4. Obras
    Gestão completa de projetos
    Estados
    Materiais consumidos
    Fotos e documentos
5. Materiais / Stock
    Gestão de artigos
    Movimentos de stock
    Alertas de stock
6. Fornecedores
    Gestão de fornecedores
    Pedidos de cotação
    Encomendas
7. Conta Corrente
    Débitos e créditos
    Histórico financeiro
8. Agenda
    Marcações
    Integração com calendário
9. Telegram Bot
    Registo de consumos
    Criação de notas
    Consulta de dados
    Upload de fotos
10. IA
    Interpretação de mensagens
    Apoio na criação de conteúdos

---


## 🔐 Segurança

O sistema segue boas práticas de segurança:

- Autenticação com Laravel Breeze
- Permissões com Spatie
- Proteção contra:
    - SQL Injection
    - XSS
    - CSRF
- Validação com Form Requests
- Password hashing seguro
- Upload seguro de ficheiros
- Logs de ações críticas
- Rate limiting
- Proteção de rotas e APIs
- Webhooks seguros (Telegram)
- Gestão de tokens via .env
- Estratégia de backups

---


## 🧠 Regras de desenvolvimento

1. Código
    Sempre uniforme, seguro e comentado
    Evitar duplicação de lógica
    Seguir padrões Laravel
2. Arquitetura
    Controllers leves
    Lógica em Services (quando necessário)
    Validação em Requests
3. Base de dados
    Estrutura clara e escalável
    Uso de índices
    Soft deletes quando aplicável
4. Interface
    Simples e rápida
    Otimizada para telemóvel (uso em obra)

---


## 🔄 Roadmap
1. Fase 1 (MVP)
    Autenticação
    Layout base
    Clientes
    Dashboard
2. Fase 2
    Obras
    Orçamentos
    Stock
3. Fase 3
    Fornecedores
    Conta corrente
4. Fase 4
    Telegram
    IA
5. Fase 5
    Relatórios
    Otimização

---

## 💡 Funcionalidades futuras
- Pesquisa global
- Relatórios avançados
- Notificações internas
- Integração com faturação externa
- App mobile (possível)
