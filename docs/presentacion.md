# Presentación del Proyecto: Despliegue de Aplicación Web Escalable ZAME SCENT en AWS

Esta guía detalla la estructura de diapositivas recomendada, puntos clave a exponer y el guion técnico para la sustentación final ante el jurado y el docente **Ing. Mario German Castillo Ramirez**.

---

## Diapositiva 1: Portada del Proyecto
* **Título:** Despliegue de una Aplicación Web Escalable en AWS con IaC (Terraform)
* **Caso de Uso:** Plataforma de Comercio Electrónico "ZAME SCENT"
* **Presentado por:** Daniel
* **Materia:** Infraestructura III
* **Facultad:** Departamento de Tecnologías de Información y Comunicaciones

---

## Diapositiva 2: El Desafío de Negocio y Alcance
* **Problema:** Lanzamiento del primer producto de una Startup de perfumería. Necesidad de desplegar en la nube de forma segura, económica y tolerante a fallos desde el primer día.
* **Funcionalidades de la Aplicación desarrolladas:**
  * Catálogo interactivo de fragancias.
  * Carrito de compras en tiempo real.
  * Simulación de pago seguro.
  * Autenticación con cifrado hash de contraseñas.
* **Decisión Técnica Clave:** Se migró el frontend de lujo de WordPress (`ZameFront`) a una aplicación monolítica ligera con Node.js + Express.js para optimizar recursos y permitir un escalado lineal exacto.

---

## Diapositiva 3: Arquitectura de Red Propuesta (VPC)
* **Estructura de Red Segmentada:**
  * **VPC CIDR:** `10.0.0.0/16`
  * **Subredes Públicas (Capa de Tránsito):** Aloja el ALB (Application Load Balancer) y el Bastión Host.
  * **Subredes Privadas (Capa de Aplicación):** Servidores EC2 administrados por el Auto Scaling Group. No tienen IPs públicas.
  * **Subredes de Persistencia (Capa de Base de Datos):** Aislamiento total de RDS en subredes privadas dedicadas.
* **Conectividad:** Internet Gateway para entrada/salida pública; NAT Gateway para que los servidores privados descarguen paquetes de forma segura sin exponerse a internet.

---

## Diapositiva 4: Alta Disponibilidad y Escalado Automático
* **Application Load Balancer (ALB):** Balancea las peticiones de los clientes HTTP (puerto 80) hacia el clúster de Express (puerto 3000) distribuido en dos Zonas de Disponibilidad.
* **Monitoreo con CloudWatch:**
  * Alarma configurada para evaluar el consumo de CPU.
  * Si el CPU supera el 80% durante dos periodos consecutivos, se dispara la alarma.
* **Acción de Auto Scaling (ASG):** Incrementa dinámicamente el número de instancias para mantener el rendimiento de la aplicación.
* **Integración con SNS:** Envía notificaciones inmediatas por correo electrónico a los administradores de sistemas en tiempo real sobre eventos de escalado o fallos de red.

---

## Diapositiva 5: Seguridad e Integridad de Datos (RDS)
* **Base de Datos (RDS MySQL):**
  * Versión del motor: MySQL 8.0.
  * Almacenada en subredes privadas.
  * Multi-AZ deshabilitado cumpliendo el presupuesto del Sandbox académico.
* **Políticas de Firewall (Security Groups):**
  * El ALB solo acepta tráfico HTTP del exterior.
  * Las instancias de aplicación solo aceptan tráfico en el puerto 3000 originado desde el Security Group del ALB.
  * RDS solo acepta tráfico en el puerto 3306 originado desde el Security Group de los servidores web y del Bastión Host.
* **Acceso Administrativo Seguro:** No hay SSH expuesto. Las EC2 privadas se administran por **AWS Systems Manager (SSM) Session Manager**, garantizando logs de auditoría exhaustivos.

---

## Diapositiva 6: Infraestructura como Código (IaC) con Terraform
* **¿Por qué Terraform en lugar de CloudFormation?**
  * Declarativo, multiplataforma y con una sintaxis limpia (HCL) que facilita la reutilización de módulos y planes en multi-cloud.
* **Automatización del Aprovisionamiento (User Data Bootstrapping):**
  * Instalación desatendida del motor Node.js.
  * Descarga segura del código web desde un bucket privado S3 (configurado con roles IAM restrictivos).
  * Creación del archivo `.env` de producción con inyección dinámica del endpoint RDS.
  * Creación del servicio `systemd` para arrancar y auto-recuperar la aplicación en Express.js.

---

## Diapositiva 7: Demostración y Plan de Verificación
* **Health Check API:** La ruta `/health` comprueba de forma concurrente el estatus del servidor y la conexión a la base de datos MySQL, devolviendo estados `200 OK` para el ALB.
* **Offline Fallback:** Soporte local completo utilizando SQLite para que los desarrolladores realicen iteraciones sin costos en la nube.
* **Auditoría de Cuenta:** **AWS CloudTrail** habilitado para registrar cada llamada a la API de AWS y garantizar la trazabilidad de seguridad.

---

## Diapositiva 8: Lecciones Aprendidas y Conclusiones
* **1. Aislamiento por Capas:** La segregación de subredes y los Security Groups restrictivos anulan más del 90% de los vectores de ataque comunes en la nube.
* **2. Despliegues reproducibles:** Terraform asegura que la startup pueda recrear el entorno de comercio electrónico en cualquier región geográfica de AWS en cuestión de minutos.
* **3. Token Economy:** Mantener las instancias pequeñas (`t2.micro`, `db.t3.micro`) y limitar el Auto Scaling se ajusta perfectamente a los presupuestos operativos de una startup en fase inicial.
