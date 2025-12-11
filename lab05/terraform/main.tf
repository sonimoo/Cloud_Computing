terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  required_version = ">= 1.0"
}

provider "aws" {
  region = var.region
}

# ============================
# SECURITY GROUP: WEB
# ============================
resource "aws_security_group" "web_sg" {
  name        = "web-security-group-tf"
  description = "Allow HTTP and SSH"
  vpc_id      = var.vpc_id

  ingress {
    description = "HTTP"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = [var.http_cidr]
  }

  ingress {
    description = "SSH"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = [var.ssh_cidr]
  }

  egress {
    description = "All outbound"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# ============================
# SECURITY GROUP: DB
# ============================
resource "aws_security_group" "db_sg" {
  name        = "db-mysql-security-group-tf"
  description = "Allow MySQL only from web-sg"
  vpc_id      = var.vpc_id

  ingress {
    description     = "MySQL from Web-SG"
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.web_sg.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# ============================
# EC2 INSTANCE
# ============================
resource "aws_instance" "web" {
  ami           = var.ami_id
  instance_type = var.instance_type

  subnet_id                   = var.subnet_id
  vpc_security_group_ids      = [aws_security_group.web_sg.id]
  associate_public_ip_address = true

  key_name = var.key_name

  user_data = <<-EOF
    #!/bin/bash
    apt update -y
    apt install -y mysql-client
  EOF

  tags = {
    Name = "project-ec2-tf"
  }
}
