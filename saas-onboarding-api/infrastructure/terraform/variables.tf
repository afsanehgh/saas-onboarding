variable "aws_region" {
  description = "AWS region for infrastructure"
  type        = string
  default     = "ap-southeast-2"
}

variable "environment" {
  description = "Environment name (dev, staging, production)"
  type        = string
  default     = "dev"
}

variable "project_name" {
  description = "Project name"
  type        = string
  default     = "saas-platform"
}

variable "vpc_cidr" {
  description = "CIDR block for VPC"
  type        = string
  default     = "10.0.0.0/16"
}

variable "availability_zones" {
  description = "List of availability zones"
  type        = list(string)
  default     = ["ap-southeast-2a", "ap-southeast-2b", "ap-southeast-2c"]
}

variable "db_instance_class" {
  description = "RDS instance class"
  type        = string
  # Aurora PostgreSQL does not support db.t3.micro; db.t4g.medium is the smallest burstable class.
  default     = "db.t4g.medium"
}

variable "db_allocated_storage" {
  description = "RDS allocated storage in GB"
  type        = number
  default     = 20
}

variable "msk_broker_count" {
  description = "Number of MSK brokers"
  type        = number
  default     = 3
}

variable "msk_broker_instance_type" {
  description = "MSK broker instance type"
  type        = string
  default     = "kafka.t3.small"
}

variable "db_master_password" {
  description = "RDS master password"
  type        = string
  sensitive   = true
}

variable "redis_auth_token" {
  description = "Redis auth token"
  type        = string
  sensitive   = true
}

variable "aurora_engine_version" {
  description = "Aurora PostgreSQL engine version. List supported versions with: aws rds describe-db-engine-versions --engine aurora-postgresql"
  type        = string
  default     = "16.6"
}

variable "aurora_parameter_group_family" {
  description = "Parameter group family matching aurora_engine_version"
  type        = string
  default     = "aurora-postgresql16"
}

variable "msk_kafka_version" {
  description = "Apache Kafka version for Amazon MSK"
  type        = string
  default     = "3.6.0"
}
