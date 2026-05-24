# Guía de Despliegue y Operación: Plataforma Web ZAME SCENT

Esta guía técnica describe el proceso detallado para validar localmente, aprovisionar la infraestructura en la nube de AWS con Terraform y operar la aplicación web redundante.

---

## 1. Requisitos Previos

Antes de comenzar, asegúrate de contar con las siguientes herramientas en tu estación de trabajo local:

* **AWS CLI** instalado y configurado con credenciales activas del entorno Sandbox (`aws configure`).
* **Terraform CLI** (versión v1.3.0 o superior) instalado y agregado al PATH de tu sistema.
* **Node.js** (v18 o v20 LTS) para validaciones locales fuera de línea.
* Un cliente Git para la gestión del repositorio de control de cambios.

---

## 2. Pruebas y Validación Local (Offline)

La aplicación web ZAME SCENT incluye un mecanismo de contingencia para autodetectar la ausencia del servidor RDS de AWS y levantar una base de datos relacional local basada en **SQLite**. Esto permite depurar el código de manera 100% offline.

### 2.1 Pasos para Inicializar Localmente:
1. Abre tu terminal y navega al directorio del aplicativo:
   ```bash
   cd c:\Users\daniel\Desktop\Zame\app
   ```
2. Instala las dependencias del proyecto:
   ```bash
   npm install
   ```
3. Si deseas pre-cargar el catálogo de fragancias de lujo en tu base de datos SQLite de pruebas, ejecuta el seeder:
   ```bash
   npm run seed
   ```
4. Levanta el servidor local de Express:
   ```bash
   npm start
   ```
5. Abre en tu navegador [http://localhost:3000](http://localhost:3000) y valida el funcionamiento del registro de usuarios, catálogo, carrito de compras y simulación de checkout.

---

## 3. Preparación del Bundle de Despliegue en S3

Para que el Auto Scaling Group (ASG) pueda aprovisionar de forma automática múltiples instancias EC2 web idénticas en subredes privadas, inyectaremos el bundle comprimido de nuestra aplicación a un bucket S3 protegido. Las instancias EC2 se descargarán este archivo directamente en su fase de arranque (`user_data.sh`).

### 3.1 Comprimir la Aplicación:
Asegúrate de comprimir todo el contenido de la carpeta `/app` (excluyendo carpetas temporales como `node_modules` o archivos `.sqlite` locales).
1. En Powershell:
   ```powershell
   cd c:\Users\daniel\Desktop\Zame\app
   Compress-Archive -Path .\* -DestinationPath .\app-bundle.zip -Force
   ```

*(Nota: Mantén este archivo `app-bundle.zip` listo para subirlo al bucket S3 una vez creado).*

---

## 4. Despliegue en AWS con Terraform (Estrategia Optimizada para Sandbox)

Para garantizar que los servidores web EC2 privados no se inicialicen en modo de contingencia (*Emergency Fallback UI*), utilizaremos una estrategia de despliegue en dos fases: **creación dirigida del bucket S3**, **carga de archivos** y finalmente **aprovisionamiento completo**.

### 4.1 Inicializar Terraform
Navega al directorio de infraestructura y descarga los proveedores de AWS necesarios:
```bash
cd c:\Users\daniel\Desktop\Zame\terraform
terraform init
```

### 4.2 Validar Configuración
Ejecuta la validación sintáctica de los archivos de configuración (`main.tf`, `variables.tf`, `outputs.tf`):
```bash
terraform validate
```

### 4.3 Fase 1: Crear el Bucket de Almacenamiento S3
Aprovisiona exclusivamente el bucket S3 dirigiendo el comando de Terraform. Esto evitará levantar cómputo antes de tener cargado el software:
```bash
terraform apply -target=aws_s3_bucket.static_assets -auto-approve
```
*Esto creará únicamente el bucket S3 `zame-scent-assets-<random-hex>` y sus dependencias de generación de nombres (random_id).*

---

## 5. Carga de Código Fuente a S3 (Fase de Carga)

1. Obtén el nombre exacto del bucket S3 creado mediante la consola de AWS o consultando el estado de Terraform:
   ```bash
   terraform state show aws_s3_bucket.static_assets
   ```
2. Sube el archivo `app-bundle.zip` (comprimido en el Paso 3) al bucket mediante la AWS CLI:
   ```bash
   aws s3 cp c:\Users\daniel\Desktop\Zame\app\app-bundle.zip s3://zame-scent-assets-<TU-HEX-RANDOM>/app-bundle.zip
   ```
3. Verifica que el archivo está correctamente alojado:
   ```bash
   aws s3 ls s3://zame-scent-assets-<TU-HEX-RANDOM>/
   ```

---

## 5.5 Fase 2: Aprovisionamiento Completo de la Infraestructura

Una vez que el bundle de la aplicación web de alta perfumería está cargado en S3, procede a aplicar el plan completo de Terraform:
```bash
terraform apply -auto-approve
```
*Al completarse la ejecución (aproximadamente de 3 a 5 minutos para inicializar la base de datos relacional RDS MySQL), las instancias web EC2 se descargarán automáticamente la versión real del software durante su arranque sin generar fallas de inicialización. El sistema imprimirá los outputs:*
* `alb_dns_name`: URL de entrada para acceder a la aplicación web.
* `bastion_public_ip`: IP pública del servidor de salto Bastión.
* `rds_endpoint`: Endpoint de comunicación con la base de datos MySQL.

---

## 6. Verificación del Despliegue y Pruebas AWS

### 6.1 Acceso Web de Alta Disponibilidad
Copia el valor de salida `alb_dns_name` y pégalo en tu navegador web:
```text
http://zame-application-load-balancer-XXXXXXXXX.us-east-1.elb.amazonaws.com
```
Registra un usuario, añade fragancias de lujo al carrito, realiza el checkout simulado y comprueba que los datos persisten de forma correcta en el RDS MySQL.

### 6.2 Health Checks de Instancia
El Application Load Balancer valida constantemente la salud de los servidores web enviando peticiones GET a la ruta `/health` en el puerto 3000. Si una instancia falla, el ALB detiene el envío de tráfico a la misma de forma automática.

### 6.3 Acceso de Administración Segura mediante SSM Session Manager
Dado que las instancias web EC2 se encuentran seguras dentro de subredes privadas sin IP pública y sin puertos SSH abiertos, el acceso se realiza por medio del Agente de Systems Manager de AWS:
```bash
aws ssm start-session --target <instance-id>
```
*Esto abrirá una terminal interactiva en el servidor privado de forma 100% segura, permitiendo auditar logs del bootstrap en `/var/log/user_data.log` o revisar el estatus del servicio NodeJS con `systemctl status zame-app`.*

### 6.4 Prueba de Escalado Automático (CPU Stress)
1. Conéctate a una instancia web EC2 por SSM Session Manager.
2. Instala la utilidad `stress-ng` o simula estrés en la CPU:
   ```bash
   sudo yum install -y stress
   stress --cpu 1 --timeout 400
   ```
3. Ve a la consola de AWS CloudWatch. La métrica de CPU superará el 80% y disparará la alarma `zame-asg-high-cpu-alarm`, indicando al ASG que debe añadir una instancia `t2.micro` de forma automática.

---

## 7. Destrucción de Infraestructura (Limpieza)

Una vez finalizado el proyecto académico, destruye todos los recursos creados en AWS para evitar cobros innecesarios o consumos de saldo en tu Sandbox:
```bash
cd c:\Users\daniel\Desktop\Zame\terraform
terraform destroy -auto-approve
```
*Este comando limpiará de forma segura las subredes, VPC, base de datos RDS, alarmas CloudWatch, balanceadores de carga y buckets S3.*
