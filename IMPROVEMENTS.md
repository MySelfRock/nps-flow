# NPSFlow - Relatório de Análise e Melhorias

Data: 2025-11-14

---

## 🔴 PROBLEMAS CRÍTICOS (Segurança & Bugs)

### 1. **Missing Rate Limiting** (HIGH SEVERITY)
- **Localização:** `backend/routes/api.php`
- **Problema:** Sem rate limiting nos endpoints públicos (login, signup, response submission)
- **Risco:** Vulnerável a brute force, DDoS e abuso
- **Solução:** Adicionar throttle middleware

### 2. **Missing CORS Configuration** (MEDIUM SEVERITY)
- **Localização:** `backend/bootstrap/app.php`
- **Problema:** Sem configuração CORS para API
- **Risco:** Requisições cross-origin falharão em produção

### 3. **Missing Database Indexes** (HIGH SEVERITY)
- **Localização:** Migrations
- **Campos sem índice:**
  - `recipients.email`
  - `recipients.token`
  - `responses.score`
  - `responses.created_at`
  - `sends.status`
  - `campaigns.tenant_id, status` (composite)
- **Risco:** Queries lentas com crescimento de dados

### 4. **Campo `sent_at` Faltando** (CRITICAL BUG) ⚠️
- **Localização:** `migrations/create_campaigns_table.php`
- **Problema:** Migration não inclui campo mas código o referencia
- **Erro:** Erro de banco ao iniciar campanha

### 5. **Campo `sent_at` no Send Model** (BUG)
- **Localização:** `app/Models/Send.php`
- **Problema:** Campo não está em fillable/casts
- **Erro:** Campo não será salvo

### 6. **Template de Email Vazio** (CRITICAL BUG) ⚠️
- **Localização:** `resources/views/emails/survey-text.blade.php`
- **Problema:** Arquivo praticamente vazio (12 bytes)
- **Impacto:** Emails enviados sem conteúdo

### 7. **Mensagens de Exception Expostas** (MEDIUM SEVERITY)
- **Localização:** Múltiplos controllers
- **Problema:** Mensagens de erro raw expostas em produção
- **Risco:** Vazamento de informações sensíveis

### 8. **Refresh Token Não Implementado** (BUG)
- **Localização:** `frontend/src/api/axios.js` + `AuthController.php`
- **Problema:** Frontend espera refreshToken mas backend não retorna
- **Impacto:** Mecanismo de refresh não funciona

### 9. **Potencial XSS** (MEDIUM SEVERITY)
- **Localização:** `frontend/src/pages/Dashboard.jsx` (linha 329)
- **Problema:** Comentários renderizados sem sanitização
- **Risco:** Scripts maliciosos podem executar

### 10. **Sem Sanitização de Templates** (MEDIUM SEVERITY)
- **Localização:** `app/Mail/SurveyEmail.php`
- **Problema:** Templates JSON sem sanitização
- **Risco:** XSS em emails

---

## 🟡 MELHORIAS IMPORTANTES (Performance & UX)

### 11. **Problema N+1 Query** (PERFORMANCE)
- **Localização:** `app/Models/Campaign.php`
- **Problema:** `sends()->count()` e `responses()->count()` em loops
- **Solução:** Usar `withCount()` em queries

### 12. **Queries Ineficientes em Reports** (PERFORMANCE)
- **Localização:** `app/Http/Controllers/Api/ReportController.php`
- **Problema:** Múltiplas chamadas `count()` na mesma collection
- **Solução:** Agregação condicional em query única

### 13. **Sem Cache de Métricas** (PERFORMANCE)
- **Problema:** Cálculos NPS executados a cada load
- **Solução:** Cache Redis com invalidação em nova resposta

### 14. **Sem Limites de Paginação**
- **Problema:** Poderia retornar milhares de registros
- **Solução:** Validar `per_page` com limite máximo

### 15. **Mismatch de Campos Campaign** (BUG) ⚠️
- **Backend:** usa `name`, `type`, `message_template`
- **Frontend:** usa `title`, `description`, `starts_at`, `ends_at`
- **Impacto:** Criar/editar campanhas falhará

### 16. **Sem Validação de Date Range**
- **Localização:** `CampaignController.php`
- **Problema:** Sem validação `ends_at > starts_at`

### 17. **Loading States Faltando** (UX)
- Campaigns: sem loading ao start/stop
- Reports: sem loading ao exportar

### 18. **Sem Error Boundaries** (UX)
- **Localização:** `frontend/src/App.jsx`
- **Problema:** App inteiro quebra em erro de componente

### 19. **Validação de Senha Fraca** (SECURITY)
- **Localização:** `AuthController.php`
- **Problema:** Apenas 8 caracteres mínimo
- **Solução:** Adicionar requisitos de complexidade

### 20. **Sem Proteção CSRF em Rotas Públicas**
- **Localização:** Rota `/r/{token}`
- **Risco:** Ataques CSRF em submissões

---

## 🔵 FUNCIONALIDADES DESEJÁVEIS

### 21. **Sistema de Alertas Não Implementado**
- TODO comentado no código
- Adicionar SendAlertJob para email/webhook

### 22. **Sem Agendamento de Campanhas**
- Campo `scheduled_at` existe mas sem scheduler
- Adicionar Laravel scheduler

### 23. **Sem Preview de Upload CSV**
- Preview antes de importar
- Erros inline com números de linha

### 24. **Analytics de Campanha Faltando**
- Open rate tracking
- Click-through rate
- Device/browser analytics

### 25. **Sem Preview de Email**
- Visualizar email renderizado antes de enviar
- Teste de envio

### 26. **Gestão de Destinatários Limitada**
- Operações em massa
- Filtros por grupos/tags
- Histórico de imports

### 27. **Sem Clonagem de Campanhas**
- Duplicar campanhas existentes
- Biblioteca de templates

### 28. **Dashboard Não Customizável**
- Seletor de período
- Comparação de campanhas
- Widgets exportáveis

### 29. **Sem Multi-idioma**
- Respostas em diferentes idiomas
- i18n para interface

### 30. **Integração Webhook Incompleta**
- Alert model tem `webhook_url` mas não implementado

---

## 🟢 QUALIDADE DE CÓDIGO

### 31. **Magic Strings Devem Ser Constantes**
```php
// Ruim
$this->status = 'draft';

// Bom
class CampaignStatus {
    const DRAFT = 'draft';
    const SENDING = 'sending';
}
```

### 32. **Type Hints Faltando**
- Adicionar tipos de retorno em todos métodos

### 33. **Error Handling Inconsistente**
- Alguns controllers com try/catch, outros não
- Padronizar tratamento de erros

### 34. **PHPDoc Comments Faltando**
- Adicionar `@param` e `@return`

### 35. **Estrutura de Response API Inconsistente**
- Alguns `response.data.data`, outros `response.data`
- Padronizar formato

### 36. **Sem TypeScript no Frontend**
- Adicionar type safety
- Melhor suporte IDE

### 37. **Lógica de Validação Repetida**
- Login.jsx e Signup.jsx com validações duplicadas
- Criar schemas compartilhados

### 38. **Sem Documentação de API**
- Adicionar Swagger/OpenAPI
- L5-Swagger recomendado

### 39. **Sem Testes Unitários**
- Adicionar PHPUnit para backend
- Adicionar Jest para frontend

### 40. **Sem Testes de Integração**
- Adicionar Laravel Dusk ou Cypress

---

## 📊 INFRAESTRUTURA

### 41. **Variáveis de Ambiente de Produção Faltando**
```
Faltam:
- JWT_SECRET (placeholder)
- CORS_ALLOWED_ORIGINS
- RATE_LIMIT_PER_MINUTE
- CACHE_DRIVER=redis
- QUEUE_CONNECTION=redis
```

### 42. **Health Check Básico**
- Endpoint `/up` não verifica dependências
- Adicionar checks de DB e Redis

### 43. **Sem Perfil Docker de Produção**
- Apenas configuração dev
- Criar docker-compose.prod.yml

### 44. **Sem Rotação de Logs**
- Logs podem encher disco
- Configurar log rotation

### 45. **Sem Estratégia de Backup**
- Sem backup de banco
- Sem procedimento de restauração

---

## 🎯 FEATURES DO PRD NÃO IMPLEMENTADAS

### 46. **Gestão de Usuários**
- Rota comentada no routes/api.php
- UserController não implementado

### 47. **Integração de Billing**
- Stripe comentado
- Tabela existe mas sem lógica

### 48. **Integração WhatsApp**
- Send model suporta mas não implementado
- Twilio/360dialog faltando

### 49. **UI de Audit Log**
- Backend loga mas sem frontend

### 50. **Página de Configurações Tenant**
- API retorna tenant mas sem UI de settings

---

## 📋 RESUMO POR PRIORIDADE

### ⚠️ CRÍTICO (Corrigir Imediatamente)
1. ✅ Campo `sent_at` em campaigns
2. ✅ Template de email
3. ✅ Índices de banco de dados
4. ✅ Rate limiting
5. ✅ Mismatch de campos campaign

### 🔴 ALTA (Antes de Produção)
6. Configurar CORS
7. Error handling adequado
8. Refresh token
9. Sanitização de input
10. Proteção CSRF

### 🟠 MÉDIA (Melhorar UX)
11. Otimizar N+1 queries
12. Camada de cache
13. Error boundaries
14. Validação de datas
15. Sistema de alertas

### 🟡 BAIXA (Débito Técnico)
16. Testes unitários
17. Documentação API
18. TypeScript
19. Constantes para magic strings
20. PHPDoc comments

---

## 📊 ESTATÍSTICAS

- **Total de Pontos:** 50
- **Críticos:** 10
- **Importantes:** 10
- **Desejáveis:** 10
- **Qualidade:** 10
- **Infraestrutura:** 5
- **Features Faltando:** 5

**Conclusão:** Projeto tem fundação sólida mas requer atenção aos problemas críticos antes de produção.
