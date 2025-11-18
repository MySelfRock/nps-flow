variable "aws_region" {
  description = "AWS region to deploy resources"
  type        = string
  default     = "us-east-1"
}

variable "project_name" {
  description = "Project name to be used in resource naming"
  type        = string
  default     = "npsflow"
}

variable "environment" {
  description = "Environment name (dev, staging, production)"
  type        = string
}

# VPC Configuration
variable "vpc_cidr" {
  description = "CIDR block for VPC"
  type        = string
  default     = "10.0.0.0/16"
}

variable "availability_zones" {
  description = "List of availability zones"
  type        = list(string)
  default     = ["us-east-1a", "us-east-1b"]
}

variable "public_subnet_cidrs" {
  description = "CIDR blocks for public subnets"
  type        = list(string)
  default     = ["10.0.1.0/24", "10.0.2.0/24"]
}

variable "private_subnet_cidrs" {
  description = "CIDR blocks for private subnets"
  type        = list(string)
  default     = ["10.0.10.0/24", "10.0.11.0/24"]
}

# RDS Configuration
variable "db_instance_class" {
  description = "RDS instance class"
  type        = string
  default     = "db.t3.small"
}

variable "db_allocated_storage" {
  description = "Allocated storage for RDS in GB"
  type        = number
  default     = 20
}

variable "db_name" {
  description = "Database name"
  type        = string
  default     = "npsflow"
}

variable "db_username" {
  description = "Database master username"
  type        = string
  default     = "npsflow_admin"
  sensitive   = true
}

variable "db_password" {
  description = "Database master password"
  type        = string
  sensitive   = true
}

variable "db_backup_retention" {
  description = "Number of days to retain database backups"
  type        = number
  default     = 7
}

# Redis Configuration
variable "redis_node_type" {
  description = "ElastiCache Redis node type"
  type        = string
  default     = "cache.t3.micro"
}

variable "redis_num_cache_nodes" {
  description = "Number of cache nodes in the cluster"
  type        = number
  default     = 1
}

# Backend ECS Configuration
variable "backend_docker_image" {
  description = "Docker image for backend application"
  type        = string
}

variable "backend_desired_count" {
  description = "Desired number of backend tasks"
  type        = number
  default     = 2
}

variable "backend_cpu" {
  description = "CPU units for backend task"
  type        = number
  default     = 512
}

variable "backend_memory" {
  description = "Memory for backend task in MB"
  type        = number
  default     = 1024
}

# Application Configuration
variable "app_key" {
  description = "Laravel application key"
  type        = string
  sensitive   = true
}

variable "jwt_secret" {
  description = "JWT secret key"
  type        = string
  sensitive   = true
}

variable "cors_allowed_origins" {
  description = "CORS allowed origins"
  type        = string
  default     = "*"
}

# SSL Certificate
variable "certificate_arn" {
  description = "ARN of SSL certificate for ALB"
  type        = string
}

variable "cloudfront_certificate_arn" {
  description = "ARN of SSL certificate for CloudFront (must be in us-east-1)"
  type        = string
}

# Frontend Configuration
variable "frontend_domain_name" {
  description = "Domain name for frontend"
  type        = string
}

# Route53 Configuration
variable "create_route53_records" {
  description = "Whether to create Route53 DNS records"
  type        = bool
  default     = false
}

variable "route53_zone_id" {
  description = "Route53 hosted zone ID"
  type        = string
  default     = ""
}
