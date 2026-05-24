output "alb_dns_name" {
  description = "The public DNS endpoint of the Application Load Balancer"
  value       = aws_lb.app_alb.dns_name
}

output "bastion_public_ip" {
  description = "The public IP address of the Bastion jump host"
  value       = aws_instance.bastion.public_ip
}

output "rds_endpoint" {
  description = "The connection endpoint for the AWS RDS MySQL database"
  value       = aws_db_instance.rds_db.endpoint
}
