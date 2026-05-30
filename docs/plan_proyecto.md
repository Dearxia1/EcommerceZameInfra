# Plan de Proyecto: Despliegue de una Aplicación Web Escalable en AWS con IaC

**Curso:** Infraestructura III  
**Docente:** Ing. Mario German Castillo Ramirez  
**Institución:** Facultad de Ingeniería, Departamento de Tecnologías de Información y Comunicaciones  
**Estudiante / Startup Developer:** Daniel y carlos

---

## 1. Objetivos del Proyecto

### 1.1 Objetivo General
Desplegar una plataforma de comercio electrónico (E-commerce) de alta perfumería denominada **ZAME SCENT** en la nube de Amazon Web Services (AWS), garantizando altos niveles de seguridad, tolerancia a fallos, escalabilidad horizontal automática e integridad de datos mediante el uso de Infraestructura como Código (IaC) con Terraform.

### 1.2 Objetivos Específicos
1. **Diseñar una topología de red segmentada (VPC)** con subredes públicas, privadas y de base de datos distribuidas en múltiples Zonas de Disponibilidad (AZ) para aislar la infraestructura crítica.
2. **Implementar una API REST robusta en Node.js (Express)** que gestione de forma eficiente las operaciones fundamentales: catálogo, carrito de compras, registro de usuarios y simulación transaccional de pagos utilizando Sequelize ORM.
3. **Garantizar la alta disponibilidad y balanceo de carga** mediante el despliegue de un Application Load Balancer (ALB) y un Auto Scaling Group (ASG) configurado para reaccionar ante picos de demanda.
4. **Almacenar la información transaccional** en un motor de base de datos relacional robusto (MySQL en AWS RDS) de forma segura en subredes privadas.
5. **Configurar un esquema proactivo de monitoreo y gobernanza** mediante alarmas en CloudWatch, notificaciones SNS ante eventos críticos y logs de auditoría con CloudTrail.

---

## 2. Alcance del Proyecto

El alcance comprende el desarrollo del software, diseño de la arquitectura de red y aprovisionamiento automático mediante Terraform en AWS, contemplando:

### 2.1 Funcionalidades de la Aplicación
* **Catálogo de Perfumes**: Renderizado asíncrono dinámico de fragancias con precio, descripción y familia olfativa.
* **Autenticación Segura**: Registro de usuarios con contraseñas encriptadas (bcryptjs) y gestión de sesiones mediante JSON Web Tokens (JWT).
* **Gestión de Carrito**: Operaciones de agregar, actualizar cantidad y remover ítems vinculados a la base de datos de forma persistente.
* **Pasarela de Simulación**: Procesamiento simulado de pagos y registro final de la orden de compra.

### 2.2 Infraestructura Soportada
* **Red**: 1 VPC (`10.0.0.0/16`), 2 Subredes Públicas, 2 Subredes Privadas, 2 Subredes de Bases de Datos, 1 Internet Gateway y 1 NAT Gateway.
* **Computación**: Launch Template para instancias EC2 web `t2.micro` y 1 Bastión Host de salto.
* **Escalabilidad**: Application Load Balancer (ALB) de capa 7 y Auto Scaling Group (ASG) con política simple de incremento ante alto consumo de CPU.
* **Seguridad**: 4 Security Groups restrictivos, Roles IAM con perfil de instancia SSM y S3 Read-Only.
* **Persistencia**: Instancia única de base de datos relacional RDS (MySQL Engine version 8.0, db.t3.micro).
* **Observabilidad y Auditoría**: Alarmas en CloudWatch, alertas de correo con SNS y rastreo con CloudTrail.

---

## 3. Topología de la Solución (Arquitectura)

La arquitectura sigue el principio del **Diseño de Múltiples Capas Seguro**:

* **Capa de Entrada (Pública)**: El Application Load Balancer (ALB) recibe el tráfico en el puerto 80. Distribuye las peticiones HTTP de forma balanceada a los servidores internos en el puerto 3000. El Bastión Host se encuentra aquí para operaciones manuales seguras de administración.
* **Capa de Aplicación (Privada)**: Servidores EC2 administrados por el ASG. No poseen IPs públicas y solo se puede acceder a ellos para administración remota por medio de AWS Systems Manager (SSM) Session Manager. Consumen APIs externas y descargan paquetes de manera segura a través del NAT Gateway.
* **Capa de Persistencia (Privada)**: La base de datos RDS MySQL reside en una zona aislada, permitiendo únicamente conexiones entrantes desde la Capa de Aplicación en el puerto 3306.

---

## 4. Estructura de Desglose de Trabajo (EDT)

El proyecto se planifica en 5 fases secuenciales:

| Fase | Tarea | Entregable Asociado |
|---|---|---|
| **Fase 1: Planificación** | Análisis de requisitos académicos y diseño de topología de red en AWS. | `docs/plan_proyecto.md` |
| **Fase 2: Aplicación** | Desarrollo del servidor Express, Sequelize ORM y frontend dinámico. | Directorio `/app` completo |
| **Fase 3: IaC** | Programación del despliegue en Terraform (main, variables, outputs, bootstrap). | Directorio `/terraform` completo |
| **Fase 4: Integración** | Carga de bundle en S3, inicialización remota de RDS y pruebas en AWS. | Pipeline de inicio del Servidor |
| **Fase 5: Documentación** | Elaboración de manual de operaciones, guías y presentación del proyecto. | `guia_despliegue.md`, `presentacion.md` |

---

## 5. Gestión de Riesgos y Mitigación

* **Riesgo 1: Limitaciones de cuota en AWS Sandbox**: El entorno académico limita el uso a un máximo de 9 instancias y prohíbe despliegues Multi-AZ de RDS o registro de dominios en Route53.
  * *Mitigación:* Se ha configurado el ASG con un máximo de 4 instancias web, RDS está configurado como Single-AZ en subredes privadas, y el acceso público es directo a través de la URL de DNS público del ALB.
* **Riesgo 2: Pérdida o fuga de credenciales sensibles**: Escribir contraseñas de base de datos o llaves JWT en texto plano.
  * *Mitigación:* Uso de variables sensibles en Terraform y carga dinámica de configuración con variables de entorno `.env` en los servidores web a través de variables renderizadas en el template de `user_data.sh`.
* **Riesgo 3: Caídas de comunicación interna con la base de datos**: Los servidores web intentan correr antes de que RDS esté en estado "available".
  * *Mitigación:* Sequelize implementa reconexiones automáticas, y el script de `user_data.sh` se ejecuta una vez que los componentes de la VPC y del RDS se han completado e inyectado con precisión.
