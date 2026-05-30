terraform {
  required_version = ">= 1.3.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
  default_tags {
    tags = {
      Project     = "ZameScent"
      Environment = "Production"
      Owner       = "InfraestructuraIII"
      Professor   = "Ing. Mario German Castillo Ramirez"
    }
  }
}

# ==============================================================================
# 1. NETWORK TOPOLOGY LAYER (VPC & SUBNETS)
# ==============================================================================

resource "aws_vpc" "main_vpc" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true
  enable_dns_support   = true
  tags = {
    Name = "Zame-VPC"
  }
}

resource "aws_internet_gateway" "igw" {
  vpc_id = aws_vpc.main_vpc.id
  tags = {
    Name = "Zame-IGW"
  }
}

# Public Subnets (For ALB and Bastion Host)
resource "aws_subnet" "public_1" {
  vpc_id                  = aws_vpc.main_vpc.id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = "${var.aws_region}a"
  map_public_ip_on_launch = true
  tags = {
    Name = "Zame-Public-Subnet-1a"
  }
}

resource "aws_subnet" "public_2" {
  vpc_id                  = aws_vpc.main_vpc.id
  cidr_block              = "10.0.2.0/24"
  availability_zone       = "${var.aws_region}b"
  map_public_ip_on_launch = true
  tags = {
    Name = "Zame-Public-Subnet-1b"
  }
}

# Private Subnets (For E-Commerce Web Servers)
resource "aws_subnet" "private_1" {
  vpc_id            = aws_vpc.main_vpc.id
  cidr_block        = "10.0.10.0/24"
  availability_zone = "${var.aws_region}a"
  tags = {
    Name = "Zame-Private-Subnet-1a"
  }
}

resource "aws_subnet" "private_2" {
  vpc_id            = aws_vpc.main_vpc.id
  cidr_block        = "10.0.11.0/24"
  availability_zone = "${var.aws_region}b"
  tags = {
    Name = "Zame-Private-Subnet-1b"
  }
}

# Database Subnets (For RDS)
resource "aws_subnet" "db_1" {
  vpc_id            = aws_vpc.main_vpc.id
  cidr_block        = "10.0.20.0/24"
  availability_zone = "${var.aws_region}a"
  tags = {
    Name = "Zame-DB-Subnet-1a"
  }
}

resource "aws_subnet" "db_2" {
  vpc_id            = aws_vpc.main_vpc.id
  cidr_block        = "10.0.21.0/24"
  availability_zone = "${var.aws_region}b"
  tags = {
    Name = "Zame-DB-Subnet-1b"
  }
}

# Route Tables & Routes
resource "aws_route_table" "public_rt" {
  vpc_id = aws_vpc.main_vpc.id
  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.igw.id
  }
  tags = {
    Name = "Zame-Public-RouteTable"
  }
}

resource "aws_route_table_association" "pub1" {
  subnet_id      = aws_subnet.public_1.id
  route_table_id = aws_route_table.public_rt.id
}

resource "aws_route_table_association" "pub2" {
  subnet_id      = aws_subnet.public_2.id
  route_table_id = aws_route_table.public_rt.id
}

# NAT Gateway (For Private Subnet outbound access: updates, npm packages, S3)
resource "aws_eip" "nat_eip" {
  domain     = "vpc"
  depends_on = [aws_internet_gateway.igw]
  tags = {
    Name = "Zame-NAT-EIP"
  }
}

resource "aws_nat_gateway" "nat_gw" {
  allocation_id = aws_eip.nat_eip.id
  subnet_id     = aws_subnet.public_1.id
  tags = {
    Name = "Zame-NAT-Gateway"
  }
}

resource "aws_route_table" "private_rt" {
  vpc_id = aws_vpc.main_vpc.id
  route {
    cidr_block     = "0.0.0.0/0"
    nat_gateway_id = aws_nat_gateway.nat_gw.id
  }
  tags = {
    Name = "Zame-Private-RouteTable"
  }
}

resource "aws_route_table_association" "priv1" {
  subnet_id      = aws_subnet.private_1.id
  route_table_id = aws_route_table.private_rt.id
}

resource "aws_route_table_association" "priv2" {
  subnet_id      = aws_subnet.private_2.id
  route_table_id = aws_route_table.private_rt.id
}

# ==============================================================================
# 2. SECURITY GROUPS (NETWORK ACL REFERENCE SHIELD)
# ==============================================================================

# ALB SG: Allow inbound port 80 from internet
resource "aws_security_group" "alb_sg" {
  name        = "zame-alb-security-group"
  description = "Controls HTTP traffic entering the ALB load balancer"
  vpc_id      = aws_vpc.main_vpc.id

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "Zame-ALB-SG"
  }
}

# Web Server SG: Allow port 3000 from ALB only, outbound access to internet (NAT)
resource "aws_security_group" "web_sg" {
  name        = "zame-web-security-group"
  description = "Allows traffic on port 3000 from ALB and general egress"
  vpc_id      = aws_vpc.main_vpc.id

  ingress {
    from_port       = 3000
    to_port         = 3000
    protocol        = "tcp"
    security_groups = [aws_security_group.alb_sg.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "Zame-WebServers-SG"
  }
}

# Bastion SG: SSH access (Can be locked down to admin IP)
resource "aws_security_group" "bastion_sg" {
  name        = "zame-bastion-security-group"
  description = "Allows SSH and SSM jump management access"
  vpc_id      = aws_vpc.main_vpc.id

  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"] # In real world, restrict to Admin CIDR
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "Zame-Bastion-SG"
  }
}

# Database SG: Port 3306 from Web instances and Bastion only
resource "aws_security_group" "db_sg" {
  name        = "zame-database-security-group"
  description = "Protects RDS database instance, allowing port 3306 exclusively"
  vpc_id      = aws_vpc.main_vpc.id

  ingress {
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.web_sg.id, aws_security_group.bastion_sg.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "Zame-Database-SG"
  }
}

# ==============================================================================
# 3. AWS RDS DATABASE INSTANCE
# ==============================================================================

resource "aws_db_subnet_group" "db_subnet_grp" {
  name       = "zame-db-subnet-group"
  subnet_ids = [aws_subnet.db_1.id, aws_subnet.db_2.id]
  tags = {
    Name = "Zame-DB-Subnet-Group"
  }
}

resource "aws_db_instance" "rds_db" {
  identifier             = "zame-scent-mysql-prod"
  engine                 = "mysql"
  engine_version         = "8.0"
  instance_class         = var.db_instance_class
  allocated_storage      = 20
  max_allocated_storage  = 100
  db_name                = var.db_name
  username               = var.db_user
  password               = var.db_pass
  db_subnet_group_name   = aws_db_subnet_group.db_subnet_grp.name
  vpc_security_group_ids = [aws_security_group.db_sg.id]
  skip_final_snapshot    = true
  multi_az               = false # Sandbox rules explicitly forbid Multi-AZ

  tags = {
    Name = "Zame-RDS-Database"
  }
}

# ==============================================================================
# 4. S3 STORAGE BUCKET (CODE DEPLOYMENT BUNDLES & ASSETS)
# ==============================================================================

# LOCAL VARIABLES FOR SANDBOX BYPASS (S3 is managed via CLI to avoid GetBucketObjectLockConfiguration errors)
locals {
  s3_bucket_name = "zame-scent-assets-daniel-mejia-v2" # Change this to any globally unique S3 bucket name
}

# The aws_s3_bucket resource is created manually via the AWS CLI to bypass restricted Vocareum Sandbox permissions.
# resource "aws_s3_bucket" "static_assets" {
#   bucket        = "zame-scent-assets-${random_id.bucket_suffix.hex}"
#   force_destroy = true
# }
# 
# resource "aws_s3_bucket_public_access_block" "block_public" {
#   bucket                  = aws_s3_bucket.static_assets.id
#   block_public_acls       = true
#   block_public_policy     = true
#   ignore_public_acls      = true
#   restrict_public_buckets = true
# }

# ==============================================================================
# 5. COMPUTE & LOAD BALANCING (ALB, IAM & ASG)
# ==============================================================================

# Application Load Balancer
resource "aws_lb" "app_alb" {
  name               = "zame-application-load-balancer"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb_sg.id]
  subnets            = [aws_subnet.public_1.id, aws_subnet.public_2.id]
  tags = {
    Name = "Zame-ALB"
  }
}

resource "aws_lb_target_group" "app_tg" {
  name        = "zame-web-app-target-group"
  port        = 3000
  protocol    = "HTTP"
  vpc_id      = aws_vpc.main_vpc.id
  target_type = "instance"

  health_check {
    path                = "/health"
    protocol            = "HTTP"
    port                = "3000"
    interval            = 30
    timeout             = 5
    healthy_threshold   = 3
    unhealthy_threshold = 3
  }
}

resource "aws_lb_listener" "http_listener" {
  load_balancer_arn = aws_lb.app_alb.arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.app_tg.arn
  }
}

# Custom IAM creation is blocked in Vocareum/AWS Academy.
# We will use the pre-created "LabInstanceProfile" instead of defining custom roles/policies.
#
# resource "aws_iam_role" "ec2_ssm_role" { ... }
# resource "aws_iam_role_policy_attachment" "ssm_attach" { ... }
# resource "aws_iam_policy" "s3_read_policy" { ... }
# resource "aws_iam_role_policy_attachment" "s3_attach" { ... }
# resource "aws_iam_instance_profile" "ec2_instance_profile" { ... }

# Launch Template for Auto Scaling Group
data "aws_ami" "amazon_linux_2023" {
  most_recent = true
  owners      = ["amazon"]
  filter {
    name   = "name"
    values = ["al2023-ami-*-x86_64"]
  }
}

resource "aws_launch_template" "web_lt" {
  name_prefix   = "zame-web-launch-template-"
  image_id      = data.aws_ami.amazon_linux_2023.id
  instance_type = var.instance_type

  iam_instance_profile {
    name = "c203071a5182623l15249430t1w375573133880-SsmRoleInstanceProfile-l8WlyA0kx8oX"
  }

  block_device_mappings {
    device_name = "/dev/xvda"
    ebs {
      volume_size           = 30
      volume_type           = "gp2"
      encrypted             = true
      delete_on_termination = true
    }
  }

  network_interfaces {
    associate_public_ip_address = false
    security_groups             = [aws_security_group.web_sg.id]
  }

  # Render user_data provisioning variables
  user_data = base64encode(templatefile("user_data.sh", {
    s3_bucket_name            = local.s3_bucket_name
    rds_endpoint_host         = split(":", aws_db_instance.rds_db.endpoint)[0]
    rds_db_name               = var.db_name
    rds_db_user               = var.db_user
    rds_db_pass               = var.db_pass
    epayco_mock               = var.epayco_mock
    epayco_test_mode          = var.epayco_test_mode
    epayco_public_key         = var.epayco_public_key
    epayco_private_key        = var.epayco_private_key
    epayco_test_price_divisor = var.epayco_test_price_divisor
    epayco_test_max_amount    = var.epayco_test_max_amount
    epayco_response_url       = var.epayco_response_url != "" ? var.epayco_response_url : "http://${aws_lb.app_alb.dns_name}/checkout.html"
    epayco_confirmation_url   = var.epayco_confirmation_url != "" ? var.epayco_confirmation_url : "http://${aws_lb.app_alb.dns_name}/api/payments/epayco/confirmation"
    s3_presigned_url          = var.s3_presigned_url
  }))

  lifecycle {
    create_before_destroy = true
  }

  tag_specifications {
    resource_type = "instance"
    tags = {
      Name        = "Zame-Web-Server-ASG"
      Project     = "ZameScent"
      Environment = "Production"
      Owner       = "InfraestructuraIII"
    }
  }

  tag_specifications {
    resource_type = "volume"
    tags = {
      Name        = "Zame-Web-Server-ASG-Volume"
      Project     = "ZameScent"
      Environment = "Production"
      Owner       = "InfraestructuraIII"
    }
  }
}

# Auto Scaling Group
resource "aws_autoscaling_group" "app_asg" {
  name_prefix         = "zame-asg-"
  vpc_zone_identifier = [aws_subnet.private_1.id, aws_subnet.private_2.id]
  target_group_arns   = [aws_lb_target_group.app_tg.arn]
  force_delete        = true

  min_size         = 2
  max_size         = 4 # Cap for Sandbox budget resource compliance
  desired_capacity = 2

  launch_template {
    id      = aws_launch_template.web_lt.id
    version = "$Latest"
  }

  health_check_type         = "ELB"
  health_check_grace_period = 300

  tag {
    key                 = "Name"
    value               = "Zame-Web-ASG-Instance"
    propagate_at_launch = true
  }

  lifecycle {
    create_before_destroy = true
  }
}

# Public Bastion Host / jump box
resource "aws_instance" "bastion" {
  ami                         = data.aws_ami.amazon_linux_2023.id
  instance_type               = "t2.micro"
  subnet_id                   = aws_subnet.public_1.id
  vpc_security_group_ids      = [aws_security_group.bastion_sg.id]
  associate_public_ip_address = true
  user_data                   = file("bastion_user_data.sh")
  iam_instance_profile        = "c203071a5182623l15249430t1w375573133880-SsmRoleInstanceProfile-l8WlyA0kx8oX"

  root_block_device {
    volume_size           = 30
    volume_type           = "gp2"
    encrypted             = true
    delete_on_termination = true
  }

  volume_tags = {
    Name        = "Zame-Bastion-Host-Volume"
    Project     = "ZameScent"
    Environment = "Production"
    Owner       = "InfraestructuraIII"
  }

  tags = {
    Name = "Zame-Bastion-Host"
  }
}

# ==============================================================================
# 6. TELEMETRY, MONITORING & GOVERNANCE LAYER (CLOUDWATCH, SNS, CLOUDTRAIL)
# ==============================================================================

# SNS Topic for Alarms and Scaling Events
resource "aws_sns_topic" "alerts" {
  name = "zame-infrastructure-alerts-topic"
}

resource "aws_sns_topic_subscription" "alerts_email" {
  topic_arn = aws_sns_topic.alerts.arn
  protocol  = "email"
  endpoint  = var.alert_email
}

# CloudWatch Alarm: High CPU Utilisation (>80% for 5 mins)
resource "aws_cloudwatch_metric_alarm" "high_cpu" {
  alarm_name          = "zame-asg-high-cpu-alarm"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  evaluation_periods  = 2
  metric_name         = "CPUUtilization"
  namespace           = "AWS/EC2"
  period              = 300
  statistic           = "Average"
  threshold           = 80
  alarm_description   = "Trigger scaling policy when ASG CPU utilization exceeds 80%"
  alarm_actions       = [aws_sns_topic.alerts.arn, aws_autoscaling_policy.scale_up_policy.arn]

  dimensions = {
    AutoScalingGroupName = aws_autoscaling_group.app_asg.name
  }
}

# ASG Scale-Up Policy
resource "aws_autoscaling_policy" "scale_up_policy" {
  name                   = "zame-asg-scale-up-policy"
  scaling_adjustment     = 1
  adjustment_type        = "ChangeInCapacity"
  cooldown               = 300
  autoscaling_group_name = aws_autoscaling_group.app_asg.name
}

# CloudTrail is explicitly denied in restricted educational Sandboxes (Vocareum / AWS Academy).
# The resources below are commented out to prevent permission errors.
# 
# resource "aws_s3_bucket" "cloudtrail_logs" {
#   bucket        = "zame-cloudtrail-audit-${random_id.bucket_suffix.hex}"
#   force_destroy = true
# }
# 
# resource "aws_s3_bucket_policy" "cloudtrail_policy" {
#   bucket = aws_s3_bucket.cloudtrail_logs.id
# 
#   policy = jsonencode({
#     Version = "2012-10-17"
#     Statement = [
#       {
#         Sid    = "AWSCloudTrailAclCheck"
#         Effect = "Allow"
#         Principal = {
#           Service = "cloudtrail.amazonaws.com"
#         }
#         Action   = "s3:GetBucketAcl"
#         Resource = aws_s3_bucket.cloudtrail_logs.arn
#       },
#       {
#         Sid    = "AWSCloudTrailWrite"
#         Effect = "Allow"
#         Principal = {
#           Service = "cloudtrail.amazonaws.com"
#         }
#         Action   = "s3:PutObject"
#         Resource = "${aws_s3_bucket.cloudtrail_logs.arn}/AWSLogs/*"
#         Condition = {
#           StringEquals = {
#             "s3:x-amz-acl" = "bucket-owner-full-control"
#           }
#         }
#       }
#     ]
#   })
# }
# 
# resource "aws_cloudtrail" "audit_trail" {
#   name                          = "zame-account-audit-trail"
#   s3_bucket_name                = aws_s3_bucket.cloudtrail_logs.id
#   include_global_service_events = true
#   is_multi_region_trail         = false
#   enable_log_file_validation    = true
# 
#   depends_on = [aws_s3_bucket_policy.cloudtrail_policy]
# }
