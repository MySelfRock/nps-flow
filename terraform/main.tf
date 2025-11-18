terraform {
  required_version = ">= 1.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  # Backend configuration for state storage
  backend "s3" {
    bucket         = "npsflow-terraform-state"
    key            = "production/terraform.tfstate"
    region         = "us-east-1"
    encrypt        = true
    dynamodb_table = "npsflow-terraform-locks"
  }
}

provider "aws" {
  region = var.aws_region

  default_tags {
    tags = {
      Project     = "NPSFlow"
      Environment = var.environment
      ManagedBy   = "Terraform"
    }
  }
}

# VPC and Networking
module "vpc" {
  source = "./modules/vpc"

  project_name        = var.project_name
  environment         = var.environment
  vpc_cidr            = var.vpc_cidr
  availability_zones  = var.availability_zones
  public_subnet_cidrs = var.public_subnet_cidrs
  private_subnet_cidrs = var.private_subnet_cidrs
}

# Security Groups
module "security_groups" {
  source = "./modules/security-groups"

  project_name = var.project_name
  environment  = var.environment
  vpc_id       = module.vpc.vpc_id
}

# RDS PostgreSQL Database
module "rds" {
  source = "./modules/rds"

  project_name           = var.project_name
  environment            = var.environment
  vpc_id                 = module.vpc.vpc_id
  private_subnet_ids     = module.vpc.private_subnet_ids
  db_security_group_id   = module.security_groups.db_security_group_id
  db_instance_class      = var.db_instance_class
  db_allocated_storage   = var.db_allocated_storage
  db_name                = var.db_name
  db_username            = var.db_username
  db_password            = var.db_password
  db_backup_retention    = var.db_backup_retention
  multi_az               = var.environment == "production" ? true : false
}

# ElastiCache Redis
module "redis" {
  source = "./modules/redis"

  project_name          = var.project_name
  environment           = var.environment
  vpc_id                = module.vpc.vpc_id
  private_subnet_ids    = module.vpc.private_subnet_ids
  redis_security_group_id = module.security_groups.redis_security_group_id
  redis_node_type       = var.redis_node_type
  redis_num_cache_nodes = var.redis_num_cache_nodes
}

# Application Load Balancer
module "alb" {
  source = "./modules/alb"

  project_name         = var.project_name
  environment          = var.environment
  vpc_id               = module.vpc.vpc_id
  public_subnet_ids    = module.vpc.public_subnet_ids
  alb_security_group_id = module.security_groups.alb_security_group_id
  certificate_arn      = var.certificate_arn
}

# ECS Cluster for Backend
module "ecs" {
  source = "./modules/ecs"

  project_name            = var.project_name
  environment             = var.environment
  vpc_id                  = module.vpc.vpc_id
  private_subnet_ids      = module.vpc.private_subnet_ids
  ecs_security_group_id   = module.security_groups.ecs_security_group_id
  alb_target_group_arn    = module.alb.target_group_arn

  # Database configuration
  db_host                 = module.rds.db_endpoint
  db_name                 = var.db_name
  db_username             = var.db_username
  db_password             = var.db_password

  # Redis configuration
  redis_host              = module.redis.redis_endpoint

  # Application configuration
  app_image               = var.backend_docker_image
  app_port                = 8000
  desired_count           = var.backend_desired_count
  cpu                     = var.backend_cpu
  memory                  = var.backend_memory

  # Environment variables
  app_env                 = var.environment
  app_key                 = var.app_key
  jwt_secret              = var.jwt_secret
  cors_allowed_origins    = var.cors_allowed_origins
}

# S3 and CloudFront for Frontend
module "frontend" {
  source = "./modules/frontend"

  project_name       = var.project_name
  environment        = var.environment
  domain_name        = var.frontend_domain_name
  certificate_arn    = var.cloudfront_certificate_arn
  create_route53     = var.create_route53_records
  route53_zone_id    = var.route53_zone_id
}

# ECR Repositories
module "ecr" {
  source = "./modules/ecr"

  project_name = var.project_name
  environment  = var.environment
}
