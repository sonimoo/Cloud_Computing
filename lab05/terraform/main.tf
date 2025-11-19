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
  region = "eu-central-1"
}

# ============================
# SECURITY GROUP: WEB
# ============================
resource "aws_security_group" "web_sg" {
  name        = "web-security-group-tf"
  description = "Allow HTTP and SSH"
  vpc_id      = "vpc-036d0e1608ab46c92"

  ingress {
    description = "HTTP"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "SSH"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
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
  vpc_id      = "vpc-036d0e1608ab46c92"

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
  ami = "ami-0a6793a25df710b06"
  instance_type = "t3.micro"

  subnet_id                   = "subnet-0ffdba1be1b9b4559"
  vpc_security_group_ids      = [aws_security_group.web_sg.id]
  associate_public_ip_address = true
  key_name                    = "k2-keypair"

  user_data = <<-EOF
    #!/bin/bash
    apt update -y
    apt install -y mysql-client
  EOF

  tags = {
    Name = "project-ec2-tf"
  }
}
