#!/bin/bash
# ZAME SCENT - Bastion Host Bootstrap Provisioning Script
exec > >(tee /var/log/user_data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "========================================================="
echo "🛡️ INICIANDO CONFIGURACIÓN DE BASTIÓN HOST"
echo "========================================================="

# Update package cache
yum update -y

# Install MySQL client for database diagnostics and admin jumps
echo "Installing MariaDB/MySQL Client tools..."
yum install -y mariadb105

echo "Bastion configuration completed successfully!"
echo "========================================================="
