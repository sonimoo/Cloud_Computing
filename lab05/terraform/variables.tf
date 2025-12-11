variable "region" {
  description = "AWS region"
  default     = "eu-central-1"
}

variable "vpc_id" {
  description = "VPC where SGs and EC2 will be created"
  default     = "vpc-036d0e1608ab46c92"
}

variable "subnet_id" {
  description = "Public subnet for EC2"
  default     = "subnet-0ffdba1be1b9b4559"
}

variable "instance_type" {
  description = "EC2 instance type"
  default     = "t3.micro"
}

variable "ami_id" {
  description = "AMI ID for EC2 instance"
  default     = "ami-0a6793a25df710b06"
}

variable "key_name" {
  description = "SSH keypair name"
  default     = "k2-keypair"
}

# Default SG rules
variable "ssh_cidr" {
  description = "Allowed SSH range"
  default     = "0.0.0.0/0"
}

variable "http_cidr" {
  description = "Allowed HTTP range"
  default     = "0.0.0.0/0"
}
