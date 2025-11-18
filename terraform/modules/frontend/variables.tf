variable "project_name" {
  description = "Project name"
  type        = string
}

variable "environment" {
  description = "Environment name"
  type        = string
}

variable "domain_name" {
  description = "Domain name for the frontend"
  type        = string
  default     = ""
}

variable "certificate_arn" {
  description = "ARN of SSL certificate for CloudFront"
  type        = string
  default     = ""
}

variable "create_route53" {
  description = "Whether to create Route53 DNS record"
  type        = bool
  default     = false
}

variable "route53_zone_id" {
  description = "Route53 hosted zone ID"
  type        = string
  default     = ""
}
