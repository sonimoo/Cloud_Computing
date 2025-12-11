variable "region" {
  description = "AWS region"
  default     = "us-east-1"
}

variable "public_subnets" {
  description = "Public subnet CIDRs"
  type        = list(string)
  default     = ["10.0.1.0/24", "10.0.2.0/24"]
}

variable "private_subnets" {
  description = "Private subnet CIDRs"
  type        = list(string)
  default     = ["10.0.3.0/24", "10.0.4.0/24"]
}

variable "availability_zones" {
  description = "AWS AZ list"
  type        = list(string)
  default     = ["us-east-1a", "us-east-1b"]
}

variable "ssh_ip" {
  description = "Allowed SSH IP"
  default     = "89.149.84.15/32"
}

variable "instance_type" {
  description = "EC2 instance type"
  default     = "t3.micro"
}

variable "key_name" {
  description = "SSH keypair name"
  default     = "k2-keypair"
}
