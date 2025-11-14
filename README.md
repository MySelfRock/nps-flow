# NPSFlow - Sistema de Pesquisas de Satisfação (NPS/CSAT/CES)

Sistema SaaS multi-tenant para envio automatizado de pesquisas de satisfação, coleta de respostas por e-mail/WhatsApp, dashboards com métricas e alertas configuráveis.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Tecnologias](#tecnologias)
- [Arquitetura](#arquitetura)
- [Instalação](#instalação)
- [Estrutura do Banco de Dados](#estrutura-do-banco-de-dados)
- [API Endpoints](#api-endpoints)
- [Modelos e Relacionamentos](#modelos-e-relacionamentos)
- [Desenvolvimento](#desenvolvimento)

## 🎯 Visão Geral

**NPSFlow** é uma plataforma SaaS para gestão de pesquisas de satisfação focada em PMEs, clínicas, lojas e prestadores de serviço. O sistema permite:

- ✅ Envio automatizado de pesquisas NPS, CSAT e CES
- ✅ Coleta de respostas via e-mail/WhatsApp
- ✅ Dashboards com métricas em tempo real
- ✅ Exportação de dados (CSV/PDF)
- ✅ Alertas configuráveis para baixas pontuações
- ✅ Multi-tenancy com isolamento de dados
- ✅ Sistema de cobrança integrado (Stripe)

## 🛠 Tecnologias

### Backend
- **Laravel 12** - Framework PHP
- **PostgreSQL 15** - Banco de dados principal
- **Redis** - Cache e filas
- **JWT Auth** - Autenticação via tokens
- **Docker** - Containerização

### Frontend (Planejado)
- **React 18** - Interface de usuário
- **Vite** - Build tool
- **TailwindCSS** - Estilização

### Serviços Externos
- **Mailgun/AWS SES** - Envio de e-mails
- **Twilio/360dialog** - WhatsApp (opcional)
- **Stripe** - Pagamentos

## 🏗 Arquitetura

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   React     │────▶│   Laravel    │────▶│ PostgreSQL  │
│  Frontend   │     │   API        │     │  Database   │
└─────────────┘     └──────────────┘     └─────────────┘
                            │
                    ┌───────┴───────┐
                    │               │
                ┌───▼────┐    ┌────▼────┐
                │ Redis  │    │ Mailgun │
                │ Cache  │    │  /SES   │
                └────────┘    └─────────┘
```

### Componentes Principais

1. **API Gateway** (Nginx) - Roteamento de requisições
2. **Application Servers** (Laravel) - Lógica de negócio
3. **Worker Pool** - Processamento assíncrono (envio de e-mails, relatórios)
4. **Database** - PostgreSQL com multi-tenancy
5. **Cache/Queue** - Redis para sessões e filas
6. **Storage** - S3-compatible para arquivos

## 🚀 Instalação

### Pré-requisitos

- Docker & Docker Compose
- Git

### Passos

1. **Clone o repositório**
```bash
git clone <repository-url>
cd nps-flow
```

2. **Configure as variáveis de ambiente**
```bash
cp .env.example .env
# Edite o arquivo .env com suas configurações
```

3. **Inicie os containers**
```bash
docker-compose up -d
```

4. **Execute as migrations**
```bash
docker-compose exec backend php artisan migrate
```

5. **Popule dados de demonstração** (opcional)
```bash
docker-compose exec backend php artisan db:seed --class=DemoDataSeeder
```

6. **Acesse a aplicação**
- Backend API: http://localhost:8000
- Frontend: http://localhost:3000
- MailHog (Email testing): http://localhost:8025
- PostgreSQL: localhost:5432
- Redis: localhost:6379

### Testando o Envio de Emails

O ambiente de desenvolvimento usa **MailHog** para capturar emails localmente:
- Acesse http://localhost:8025 para visualizar os emails enviados
- Todos os emails são interceptados, nenhum é enviado para destinatários reais
- Use os dados de demonstração para testar o fluxo completo

## 📊 Estrutura do Banco de Dados

### Tabelas Principais

#### `tenants`
Gerenciamento de empresas (multi-tenant)
```sql
- id (uuid, PK)
- name (string)
- cnpj (string, nullable)
- plan (string: free, starter, pro, enterprise)
- billing_customer_id (string, nullable)
- timestamps
```

#### `users`
Usuários do sistema com roles
```sql
- id (uuid, PK)
- tenant_id (uuid, FK)
- name (string)
- email (string, unique)
- password (string, hashed)
- role (string: super_admin, admin, manager, viewer)
- last_login_at (timestamp)
- timestamps
```

#### `campaigns`
Campanhas de pesquisa
```sql
- id (uuid, PK)
- tenant_id (uuid, FK)
- name (string)
- type (string: NPS, CSAT, CES, CUSTOM)
- message_template (json)
- sender_email (string)
- sender_name (string)
- scheduled_at (timestamp)
- status (string: draft, scheduled, sending, sent, paused)
- settings (json)
- created_by (uuid, FK users)
- timestamps
```

#### `recipients`
Destinatários das pesquisas
```sql
- id (uuid, PK)
- tenant_id (uuid, FK)
- campaign_id (uuid, FK, nullable)
- external_id (string)
- name (string)
- email (string)
- phone (string)
- token (string, unique) - para link de resposta
- status (string: pending, sent, responded, failed)
- tags (json)
- timestamps
```

#### `responses`
Respostas das pesquisas
```sql
- id (uuid, PK)
- tenant_id (uuid, FK)
- campaign_id (uuid, FK)
- recipient_id (uuid, FK)
- score (integer 0-10 para NPS)
- answers (json)
- comment (text)
- metadata (json: ip, user_agent, etc)
- timestamps
```

#### `sends`
Histórico de envios
```sql
- id (uuid, PK)
- tenant_id (uuid, FK)
- campaign_id (uuid, FK)
- recipient_id (uuid, FK)
- channel (string: email, whatsapp)
- status (string: pending, sent, delivered, failed, bounced)
- provider_message_id (string)
- attempts (integer)
- last_attempt_at (timestamp)
- error_message (text)
- timestamps
```

#### `alerts`
Configuração de alertas
```sql
- id (uuid, PK)
- tenant_id (uuid, FK)
- campaign_id (uuid, FK, nullable)
- condition (json: threshold, etc)
- notify_emails (json: array of emails)
- webhook_url (string)
- enabled (boolean)
- timestamps
```

## 🔌 API Endpoints

### Autenticação
```
POST   /api/v1/auth/signup         - Criar conta
POST   /api/v1/auth/login          - Login (retorna JWT)
POST   /api/v1/auth/refresh        - Refresh token
POST   /api/v1/auth/logout         - Logout
GET    /api/v1/auth/me             - Usuário atual
```

### Tenants & Users
```
GET    /api/v1/tenants/me          - Dados do tenant
GET    /api/v1/users               - Listar usuários
POST   /api/v1/users               - Criar usuário
GET    /api/v1/users/{id}          - Detalhes do usuário
PUT    /api/v1/users/{id}          - Atualizar usuário
DELETE /api/v1/users/{id}          - Deletar usuário
```

### Campaigns
```
GET    /api/v1/campaigns           - Listar campanhas
POST   /api/v1/campaigns           - Criar campanha
GET    /api/v1/campaigns/{id}      - Detalhes da campanha
PUT    /api/v1/campaigns/{id}      - Atualizar campanha
DELETE /api/v1/campaigns/{id}      - Deletar campanha
POST   /api/v1/campaigns/{id}/start   - Iniciar envio
POST   /api/v1/campaigns/{id}/stop    - Parar envio
```

### Recipients
```
GET    /api/v1/campaigns/{id}/recipients        - Listar destinatários
POST   /api/v1/campaigns/{id}/recipients        - Adicionar destinatário
POST   /api/v1/campaigns/{id}/recipients/upload - Upload CSV
DELETE /api/v1/campaigns/{id}/recipients/{rid}  - Remover destinatário
```

### Responses (Público)
```
GET    /r/{token}                  - Página de resposta (HTML)
POST   /r/{token}/response         - Submeter resposta
```

### Dashboard & Reports
```
GET    /api/v1/reports/nps         - Métricas e tendências NPS
       Query params:
       - campaign_id: Filtrar por campanha específica
       - start_date: Data inicial (YYYY-MM-DD)
       - end_date: Data final (YYYY-MM-DD)

       Retorna:
       - overall: NPS geral, taxa de resposta, promoters/passives/detractors
       - score_distribution: Distribuição de notas 0-10
       - trends: Evolução mensal do NPS (últimos 6 meses)
       - detractor_comments: Top 10 comentários de detratores
       - campaigns: Breakdown por campanha

GET    /api/v1/reports/responses   - Listagem detalhada de respostas
       Query params (filtros):
       - campaign_id: Campanha específica
       - campaign_type: NPS, CSAT, CES, CUSTOM
       - min_score, max_score: Faixa de pontuação
       - category: promoter, passive, detractor
       - start_date, end_date: Período
       - tags: Tags de destinatários
       - search: Busca em comentários
       - has_comment: true/false
       - sort_by: created_at, score
       - sort_order: asc, desc
       - per_page: Paginação (default: 50)

       Retorna: Respostas paginadas com detalhes completos

GET    /api/v1/reports/export      - Exportar dados
       Query params:
       - format: csv, json (default: csv)
       - type: responses, summary (default: responses)
       - (+ todos os filtros de /reports/responses)

       Retorna:
       - CSV stream para download
       - JSON formatado para processamento
```

## 📦 Modelos e Relacionamentos

### Tenant (Multi-tenancy)
```php
Tenant
├─ hasMany: users
├─ hasMany: campaigns
├─ hasMany: recipients
├─ hasMany: responses
├─ hasMany: sends
├─ hasMany: alerts
├─ hasMany: auditLogs
└─ hasMany: billingSubscriptions

Métodos:
- isOnPlan(string $plan): bool
- isPremium(): bool
```

### User (Autenticação)
```php
User implements JWTSubject
├─ belongsTo: tenant
├─ hasMany: createdCampaigns
└─ hasMany: auditLogs

Métodos:
- isSuperAdmin(): bool
- isAdmin(): bool
- canManage(): bool
- updateLastLogin(): void
- getJWTIdentifier()
- getJWTCustomClaims()
```

### Campaign
```php
Campaign
├─ belongsTo: tenant
├─ belongsTo: creator (User)
├─ hasMany: recipients
├─ hasMany: responses
├─ hasMany: sends
└─ hasMany: alerts

Métodos:
- isNPS(): bool
- isDraft(): bool
- isSent(): bool
- canBeSent(): bool
- getNPSScore(): ?float
- getResponseRate(): float
```

### Recipient
```php
Recipient
├─ belongsTo: tenant
├─ belongsTo: campaign
├─ hasOne: response
└─ hasMany: sends

Métodos:
- hasResponded(): bool
- getResponseLink(): string
- markAsResponded(): void
Auto-gera token único na criação
```

### Response
```php
Response
├─ belongsTo: tenant
├─ belongsTo: campaign
└─ belongsTo: recipient

Métodos:
- isPromoter(): bool (score >= 9)
- isPassive(): bool (score 7-8)
- isDetractor(): bool (score <= 6)
- getCategory(): string
```

## 📧 Sistema de Envio de Emails

### Arquitetura

O sistema usa **Laravel Queues** com Redis para processamento assíncrono de emails:

```
CampaignController::start()
        ↓
    SendCampaignJob (dispatched to queue)
        ↓
    For each recipient → SendEmailJob (dispatched with delay)
        ↓
    SurveyEmail (mailable)
        ↓
    SMTP (MailHog/Mailgun/SES)
```

### Jobs Implementados

#### SendCampaignJob
- **Responsabilidade**: Orquestrar o envio de uma campanha completa
- **Ações**:
  - Busca todos os recipients com status `pending` ou `failed`
  - Despacha um `SendEmailJob` para cada recipient
  - Aplica rate limiting (2 segundos entre dispatches)
  - Atualiza status da campanha para `sending`
- **Timeout**: 600 segundos

#### SendEmailJob
- **Responsabilidade**: Enviar email individual para um recipient
- **Ações**:
  - Verifica se recipient já respondeu (skip se sim)
  - Cria/atualiza registro `Send` com tentativas
  - Envia email via `SurveyEmail` mailable
  - Atualiza status do `Send` e `Recipient`
  - Loga sucesso/falha
- **Retries**: 3 tentativas
- **Backoff**: 1min, 5min, 15min
- **Timeout**: 60 segundos

### Email Template (SurveyEmail)

**Placeholders suportados**:
- `{{name}}` - Nome do destinatário
- `{{email}}` - Email do destinatário
- `{{link}}` - Link único para resposta
- `{{campaign_name}}` - Nome da campanha

**Exemplo de template**:
```
Olá {{name}},

Em uma escala de 0 a 10, quanto você recomendaria nossa empresa?

Clique aqui para responder: {{link}}

Obrigado!
```

### Monitoramento

**Logs**:
```bash
docker-compose logs -f queue
```

**Queue Status**:
```bash
docker-compose exec backend php artisan queue:work --verbose
```

**Failed Jobs**:
```bash
docker-compose exec backend php artisan queue:failed
docker-compose exec backend php artisan queue:retry all
```

### Configuração de Email

**Desenvolvimento** (MailHog):
```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_ENCRYPTION=null
```

**Produção** (Mailgun):
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your-api-key
```

**Produção** (AWS SES):
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
```

## 🧪 Desenvolvimento

### Executar Testes
```bash
docker-compose exec backend php artisan test
```

### Executar Migrations
```bash
docker-compose exec backend php artisan migrate
```

### Rollback Migrations
```bash
docker-compose exec backend php artisan migrate:rollback
```

### Queue Worker
```bash
docker-compose exec backend php artisan queue:work
```

### Logs
```bash
docker-compose logs -f backend
```

## 🔐 Segurança

- ✅ Senhas com bcrypt
- ✅ JWT com expiração curta (15min) + refresh token
- ✅ Rate limiting em endpoints públicos
- ✅ Proteção CSRF
- ✅ Validação de dados de entrada
- ✅ Multi-tenancy com isolamento por tenant_id
- ✅ HTTPS obrigatório em produção

## 📝 Cálculo de NPS

```
NPS = (% Promotores - % Detratores) × 100

Promotores: score 9-10
Passivos: score 7-8
Detratores: score 0-6
```

Exemplo implementado em `Campaign::getNPSScore()`:
```php
$promoters = $responses->where('score', '>=', 9)->count();
$detractors = $responses->where('score', '<=', 6)->count();
$total = $responses->count();
return (($promoters - $detractors) / $total) * 100;
```

## 📈 Roadmap

### MVP (Sprint 1-4) ✅ Em Progresso
- [x] Setup inicial com Docker
- [x] Database schema e migrations
- [x] Models Eloquent com relacionamentos
- [ ] Autenticação JWT
- [ ] CRUD de campanhas
- [ ] Sistema de envio de e-mails
- [ ] Página pública de resposta
- [ ] Dashboard básico

### Pós-MVP
- [ ] Envio por WhatsApp
- [ ] Agendamento recorrente
- [ ] Relatórios PDF avançados
- [ ] Integração com CRMs via webhooks
- [ ] White-label por tenant
- [ ] Multi-idioma

## 📄 Licença

MIT License - veja LICENSE para detalhes.

---

**NPSFlow** - Transforme feedback em ação! 🚀
