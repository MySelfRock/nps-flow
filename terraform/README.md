# NPSFlow - Infraestrutura AWS com Terraform

Este diretório contém a infraestrutura como código (IaC) para deploy do sistema NPSFlow na AWS usando Terraform.

## 📋 Arquitetura

A infraestrutura consiste em:

### Backend (Laravel)
- **ECS Fargate**: Containers gerenciados para o backend Laravel
- **Application Load Balancer (ALB)**: Distribuição de tráfego com SSL/TLS
- **RDS PostgreSQL 15**: Banco de dados gerenciado Multi-AZ (produção)
- **ElastiCache Redis**: Cache e gerenciamento de filas
- **Auto Scaling**: Escalabilidade automática baseada em CPU e memória

### Frontend (React)
- **S3**: Armazenamento estático
- **CloudFront**: CDN global com SSL/TLS
- **Route53**: DNS (opcional)

### Networking
- **VPC**: Rede isolada com subnets públicas e privadas
- **NAT Gateways**: Acesso à internet para recursos privados
- **Security Groups**: Controle de tráfego entre componentes

### Monitoramento
- **CloudWatch**: Logs e métricas
- **CloudWatch Alarms**: Alertas para CPU, memória, storage, etc.
- **VPC Flow Logs**: Monitoramento de tráfego de rede

## 📁 Estrutura de Diretórios

```
terraform/
├── main.tf                    # Configuração principal
├── variables.tf               # Definições de variáveis
├── outputs.tf                 # Outputs da infraestrutura
├── terraform.tfvars.example   # Exemplo de valores de variáveis
├── README.md                  # Este arquivo
├── modules/
│   ├── vpc/                   # Módulo VPC e networking
│   ├── security-groups/       # Módulo de security groups
│   ├── rds/                   # Módulo RDS PostgreSQL
│   ├── redis/                 # Módulo ElastiCache Redis
│   ├── alb/                   # Módulo Application Load Balancer
│   ├── ecs/                   # Módulo ECS para backend
│   ├── frontend/              # Módulo S3 + CloudFront
│   └── ecr/                   # Módulo ECR repositories
└── scripts/
    ├── deploy-backend.sh      # Script para deploy do backend
    ├── deploy-frontend.sh     # Script para deploy do frontend
    └── init-db.sh             # Script para inicializar database
```

## 🚀 Pré-requisitos

### 1. Instalar Ferramentas
```bash
# Terraform
brew install terraform  # macOS
# ou
wget https://releases.hashicorp.com/terraform/1.6.0/terraform_1.6.0_linux_amd64.zip

# AWS CLI
brew install awscli  # macOS
# ou
pip install awscli

# Docker (para build de imagens)
https://docs.docker.com/get-docker/
```

### 2. Configurar AWS CLI
```bash
aws configure
# AWS Access Key ID: [sua-access-key]
# AWS Secret Access Key: [sua-secret-key]
# Default region name: us-east-1
# Default output format: json
```

### 3. Criar Certificados SSL no ACM

**Para o ALB (mesma região):**
```bash
# Via AWS Console ou CLI
aws acm request-certificate \
  --domain-name api.npsflow.com \
  --validation-method DNS \
  --region us-east-1
```

**Para o CloudFront (obrigatoriamente us-east-1):**
```bash
aws acm request-certificate \
  --domain-name app.npsflow.com \
  --validation-method DNS \
  --region us-east-1
```

Após criar, valide via DNS seguindo as instruções do console AWS.

### 4. Criar S3 Bucket para Terraform State

```bash
# Criar bucket para armazenar o state
aws s3api create-bucket \
  --bucket npsflow-terraform-state \
  --region us-east-1

# Habilitar versionamento
aws s3api put-bucket-versioning \
  --bucket npsflow-terraform-state \
  --versioning-configuration Status=Enabled

# Criar tabela DynamoDB para locks
aws dynamodb create-table \
  --table-name npsflow-terraform-locks \
  --attribute-definitions AttributeName=LockID,AttributeType=S \
  --key-schema AttributeName=LockID,KeyType=HASH \
  --provisioned-throughput ReadCapacityUnits=5,WriteCapacityUnits=5 \
  --region us-east-1
```

## ⚙️ Configuração

### 1. Copiar e Configurar Variáveis

```bash
cd terraform
cp terraform.tfvars.example terraform.tfvars
```

Edite `terraform.tfvars` com seus valores:

```hcl
# Exemplo mínimo para começar
environment = "production"

# Database
db_password = "SUA_SENHA_FORTE_AQUI"

# Certificados SSL (ARNs dos certificados criados no ACM)
certificate_arn            = "arn:aws:acm:us-east-1:123456789012:certificate/xxxxx"
cloudfront_certificate_arn = "arn:aws:acm:us-east-1:123456789012:certificate/yyyyy"

# Application Keys (gere com: php artisan key:generate e openssl rand -base64 32)
app_key    = "base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
jwt_secret = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"

# Docker Image (inicialmente use uma tag temporária, será atualizado depois)
backend_docker_image = "123456789012.dkr.ecr.us-east-1.amazonaws.com/npsflow-production-backend:latest"

# Domain
frontend_domain_name = "app.npsflow.com"
cors_allowed_origins = "https://app.npsflow.com"
```

### 2. Gerar Application Keys

```bash
# No diretório do backend Laravel
cd ../backend
php artisan key:generate --show
# Copie o output para app_key em terraform.tfvars

# Gerar JWT Secret
openssl rand -base64 32
# Copie o output para jwt_secret em terraform.tfvars
```

## 🏗️ Deploy da Infraestrutura

### 1. Inicializar Terraform

```bash
cd terraform
terraform init
```

### 2. Validar Configuração

```bash
terraform validate
terraform plan
```

### 3. Aplicar Infraestrutura

```bash
# Para ambiente de produção
terraform apply

# Para ambiente específico
terraform apply -var="environment=staging"
```

Aguarde 10-15 minutos para a criação completa da infraestrutura.

### 4. Capturar Outputs Importantes

```bash
# Listar todos os outputs
terraform output

# Outputs importantes:
terraform output ecr_backend_repository_url
terraform output alb_dns_name
terraform output cloudfront_domain_name
terraform output db_endpoint  # sensível
terraform output redis_endpoint  # sensível
```

## 🐳 Deploy da Aplicação

### Backend (Laravel)

#### 1. Fazer Login no ECR

```bash
# Obter URL do repositório ECR
ECR_URL=$(terraform output -raw ecr_backend_repository_url | cut -d'/' -f1)

# Fazer login
aws ecr get-login-password --region us-east-1 | \
  docker login --username AWS --password-stdin $ECR_URL
```

#### 2. Build e Push da Imagem Docker

Primeiro, crie o Dockerfile no diretório backend (veja seção Dockerfile abaixo).

```bash
cd ../backend

# Build da imagem
docker build -t npsflow-backend .

# Tag para ECR
ECR_REPO=$(cd ../terraform && terraform output -raw ecr_backend_repository_url)
docker tag npsflow-backend:latest $ECR_REPO:latest
docker tag npsflow-backend:latest $ECR_REPO:v1.0.0

# Push para ECR
docker push $ECR_REPO:latest
docker push $ECR_REPO:v1.0.0
```

#### 3. Executar Migrations

```bash
# Conectar ao banco via bastion host ou usar ECS Exec
aws ecs execute-command \
  --cluster npsflow-production-cluster \
  --task <TASK_ID> \
  --container app \
  --interactive \
  --command "php artisan migrate --force"
```

### Frontend (React)

#### 1. Build da Aplicação

```bash
cd ../frontend

# Configurar variável de ambiente com a URL do backend
echo "VITE_API_URL=https://api.npsflow.com" > .env.production

# Build
npm run build
```

#### 2. Deploy para S3

```bash
# Obter nome do bucket
S3_BUCKET=$(cd ../terraform && terraform output -raw frontend_s3_bucket)

# Sync para S3
aws s3 sync dist/ s3://$S3_BUCKET/ --delete

# Invalidar cache do CloudFront
DISTRIBUTION_ID=$(cd ../terraform && terraform output -raw cloudfront_distribution_id)
aws cloudfront create-invalidation \
  --distribution-id $DISTRIBUTION_ID \
  --paths "/*"
```

## 🔒 Segurança

### Secrets Management

Senhas e secrets estão armazenados em:
- **Terraform State**: Criptografado no S3
- **AWS Secrets Manager**: Credenciais do banco de dados
- **ECS Task Definition**: Secrets referenciadas via ARN

### Network Security

- Backend está em subnets **privadas**
- Database e Redis estão em subnets **privadas**
- Apenas o ALB e NAT Gateway estão em subnets **públicas**
- Security Groups restringem tráfego apenas ao necessário
- SSL/TLS obrigatório em todas as comunicações externas

### IAM Roles

- **ECS Task Execution Role**: Permissões para pull de imagens ECR e acesso a Secrets Manager
- **ECS Task Role**: Permissões para a aplicação acessar S3, SES, etc.

## 📊 Monitoramento

### CloudWatch Dashboards

Acesse o CloudWatch Console para ver:
- CPU e Memória do ECS
- Latência do ALB
- Conexões do RDS
- Cache hit rate do Redis

### Alarmes Configurados

- **RDS**: CPU > 80%, Storage < 5GB
- **Redis**: CPU > 75%, Memory > 80%, Evictions > 1000
- **ALB**: Response time > 1s, Unhealthy hosts, 5XX errors
- **ECS**: Auto scaling baseado em CPU (70%) e memória (80%)

### Logs

```bash
# Ver logs do backend
aws logs tail /ecs/npsflow-production --follow

# Ver logs do RDS
aws rds describe-db-log-files \
  --db-instance-identifier npsflow-production-postgres
```

## 💰 Custos Estimados

### Produção (Multi-AZ, Alta Disponibilidade)

| Serviço | Configuração | Custo/mês (USD) |
|---------|-------------|-----------------|
| ECS Fargate (2 tasks) | 0.5 vCPU, 1GB RAM | ~$30 |
| RDS PostgreSQL Multi-AZ | db.t3.small | ~$70 |
| ElastiCache Redis | cache.t3.micro | ~$15 |
| ALB | - | ~$25 |
| NAT Gateway (2 AZs) | - | ~$65 |
| CloudFront | 1TB transfer | ~$85 |
| S3 | 100GB | ~$2 |
| Data Transfer | - | ~$20 |
| **Total Estimado** | | **~$312/mês** |

### Staging/Dev (Configuração Reduzida)

- ~$150-200/mês com RDS single-AZ, 1 NAT Gateway, instâncias menores

## 🔄 CI/CD com GitHub Actions

Crie `.github/workflows/deploy.yml`:

```yaml
name: Deploy to AWS

on:
  push:
    branches: [main]

jobs:
  deploy-backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Configure AWS credentials
        uses: aws-actions/configure-aws-credentials@v2
        with:
          aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
          aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          aws-region: us-east-1

      - name: Login to Amazon ECR
        id: login-ecr
        uses: aws-actions/amazon-ecr-login@v1

      - name: Build and push Docker image
        env:
          ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
          ECR_REPOSITORY: npsflow-production-backend
          IMAGE_TAG: ${{ github.sha }}
        run: |
          cd backend
          docker build -t $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG .
          docker push $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG

      - name: Update ECS service
        run: |
          aws ecs update-service \
            --cluster npsflow-production-cluster \
            --service npsflow-production-service \
            --force-new-deployment

  deploy-frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - uses: actions/setup-node@v3
        with:
          node-version: '18'

      - name: Build
        run: |
          cd frontend
          npm ci
          npm run build

      - name: Deploy to S3
        run: |
          aws s3 sync frontend/dist/ s3://npsflow-production-frontend/ --delete

      - name: Invalidate CloudFront
        run: |
          aws cloudfront create-invalidation \
            --distribution-id ${{ secrets.CLOUDFRONT_DISTRIBUTION_ID }} \
            --paths "/*"
```

## 🗑️ Destruir Infraestrutura

**⚠️ ATENÇÃO: Esta ação é IRREVERSÍVEL!**

```bash
# Backup do banco de dados primeiro!
aws rds create-db-snapshot \
  --db-instance-identifier npsflow-production-postgres \
  --db-snapshot-identifier npsflow-final-backup-$(date +%Y%m%d)

# Destruir infraestrutura
terraform destroy

# Confirme digitando 'yes' quando solicitado
```

## 🆘 Troubleshooting

### ECS Tasks não iniciam

```bash
# Ver logs do task
aws ecs describe-tasks \
  --cluster npsflow-production-cluster \
  --tasks <TASK_ARN>

# Ver eventos do serviço
aws ecs describe-services \
  --cluster npsflow-production-cluster \
  --services npsflow-production-service
```

### Database Connection Failed

- Verificar security groups
- Verificar se as tasks estão nas subnets privadas corretas
- Verificar credenciais no Secrets Manager

### 502 Bad Gateway no ALB

- Verificar health check endpoint `/api/health`
- Verificar logs do container
- Verificar se o backend está ouvindo na porta 8000

## 📚 Referências

- [Terraform AWS Provider](https://registry.terraform.io/providers/hashicorp/aws/latest/docs)
- [AWS ECS Best Practices](https://docs.aws.amazon.com/AmazonECS/latest/bestpracticesguide/)
- [Laravel Deployment](https://laravel.com/docs/10.x/deployment)

## 📞 Suporte

Para problemas ou dúvidas:
1. Verifique os logs no CloudWatch
2. Revise a documentação AWS
3. Abra uma issue no repositório do projeto
