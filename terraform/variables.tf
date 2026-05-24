variable "aws_region" {
  type        = string
  description = "AWS region for infrastructure deployment"
  default     = "us-east-1"
}

variable "instance_type" {
  type        = string
  description = "EC2 instance size for web applications"
  default     = "t2.micro"
}

variable "db_instance_class" {
  type        = string
  description = "AWS RDS database instance sizing"
  default     = "db.t3.micro"
}

variable "db_name" {
  type        = string
  description = "Database schema name"
  default     = "zame_db"
}

variable "db_user" {
  type        = string
  description = "Database master administrator user"
  default     = "admin"
}

variable "db_pass" {
  type        = string
  description = "Database master administrator secure password"
  default     = "SecurePass123!"
  sensitive   = true
}

variable "alert_email" {
  type        = string
  description = "Email endpoint to subscribe for CloudWatch alarm notifications"
  default     = "infraalertas@zame.com.co"
}
