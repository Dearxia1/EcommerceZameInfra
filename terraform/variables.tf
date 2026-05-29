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

variable "epayco_mock" {
  type        = bool
  description = "Approve payments locally without contacting ePayco. Use only for local/dev demos."
  default     = false
}

variable "epayco_test_mode" {
  type        = bool
  description = "Use ePayco test mode."
  default     = true
}

variable "epayco_public_key" {
  type        = string
  description = "ePayco public API key. Provide through terraform.tfvars or TF_VAR_epayco_public_key."
  default     = ""
  sensitive   = true
}

variable "epayco_private_key" {
  type        = string
  description = "ePayco private API key. Provide through terraform.tfvars or TF_VAR_epayco_private_key."
  default     = ""
  sensitive   = true
}

variable "epayco_test_price_divisor" {
  type        = number
  description = "Divides product prices while EPAYCO_TEST_MODE is enabled to stay below test-account limits."
  default     = 10
}

variable "epayco_test_max_amount" {
  type        = number
  description = "Maximum checkout amount allowed while EPAYCO_TEST_MODE is enabled."
  default     = 200000
}

variable "epayco_response_url" {
  type        = string
  description = "Browser return URL after ePayco payment attempts."
  default     = ""
}

variable "epayco_confirmation_url" {
  type        = string
  description = "Server-to-server ePayco confirmation URL."
  default     = ""
}

variable "s3_presigned_url" {
  type        = string
  description = "Pre-signed URL to download the application bundle directly from S3 without IAM permissions."
  default     = ""
}

