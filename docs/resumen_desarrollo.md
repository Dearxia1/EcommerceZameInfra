# Resumen de Desarrollo, Decisiones Técnicas y Arquitectura Cloud

Este documento recopila el desglose técnico, las decisiones arquitectónicas, los elementos reutilizados y las implementaciones nuevas realizadas para el proyecto **ZAME SCENT**.

---

## 1. Qué se Hizo (Resumen General)
Se diseñó, programó y desplegó una plataforma de comercio electrónico de alta perfumería que consta de dos componentes principales integrados:
1. **Aplicación Web Completa (Backend + Frontend)**: Un servidor REST en Node.js/Express con persistencia relacional vía Sequelize ORM y un frontend de lujo responsivo (Single Page Application con HTML5, Tailwind CSS y JavaScript).
2. **Infraestructura como Código (AWS Cloud via Terraform)**: Una topología de red de producción altamente segura en AWS, que implementa alta disponibilidad, autoescalado, balanceo de carga y monitoreo.
3. **Resolución de Errores Críticos (Race Condition Fix)**: Corrección de un fallo de sincronización asíncrona en el frontend donde las validaciones de estado del carrito local bloqueaban la navegación en `checkout.html` y desconfiguraban `cart.html` al ejecutarse antes del retorno del API (`fetchCart`).

---

## 2. Decisiones de Diseño y Arquitectura

### 2.1 Persistencia Híbrida (SQLite + MySQL)
* **Decisión**: Configurar Sequelize ORM para alternar automáticamente el dialecto de base de datos según el entorno operativo.
* **Justificación**: Permite un desarrollo local ágil y autónomo sin necesidad de estar conectado a AWS (usando SQLite local almacenado en un archivo físico `.sqlite`), garantizando al mismo tiempo compatibilidad transaccional completa con el motor MySQL gestionado por AWS RDS en producción.

### 2.2 Acceso Remoto Seguro (SSM vs SSH)
* **Decisión**: Deshabilitar el acceso tradicional por puerto 22 (SSH) y omitir la generación de pares de claves públicas/privadas. En su lugar, se asocian Perfiles de Instancia IAM con políticas `AmazonSSMManagedInstanceCore` a los servidores EC2.
* **Justificación**: Aumenta exponencialmente la postura de seguridad. Ninguna instancia web expone puertos administrativos a internet. Las sesiones de administración se realizan exclusivamente mediante AWS Systems Manager Session Manager, que audita y restringe accesos según roles IAM definidos.

### 2.3 Segmentación en Capas en la VPC
* **Decisión**: Dividir la red `10.0.0.0/16` en 3 capas de subredes distribuidas a lo largo de dos zonas de disponibilidad (AZ):
  * **Capa Pública**: Aloja el Application Load Balancer (ALB) y un Bastión Host de salto.
  * **Capa Privada**: Aloja las instancias de cómputo EC2 (Web Servers) del Auto Scaling Group. Carecen de IPs públicas.
  * **Capa de Datos**: Subredes aisladas dedicadas exclusivamente a la base de datos RDS MySQL.
* **Justificación**: Garantiza que si la capa pública (ALB) se ve comprometida, los servidores y las bases de datos transaccionales se mantienen inaccesibles y seguros detrás de tablas de ruteo estrictas y NAT Gateways.

---

## 3. Elementos Reutilizados (Reutilización de ZameFront)
Para honrar la identidad visual de la startup y la plantilla premium preexistente (`ZameFront`), se reutilizó lo siguiente:
* **Paleta de Colores y Tipografías**: Se extrajo el esquema de diseño minimalista de lujo, utilizando el color dorado oscuro primario (`#B8860B`) y el dorado claro de acento (`#C5A059`) sobre un fondo negro profundo/zinc oscuro. Se importaron las fuentes `Playfair Display` (para títulos elegantes y serifas de alta perfumería) e `Inter` (para textos de legibilidad técnica).
* **Concepto de Producto y Clasificación**: Estructura de familias olfativas ("Woody", "Oriental", "Fresh", "Floral") que categoriza el catálogo.
* **Flujo Transaccional**: El flujo estático de adición de productos, visualización de carrito y formulario de facturación segura.

---

## 4. Elementos Nuevos (Desarrollados desde Cero)
Dado que `ZameFront` era un prototipo frontend o plugin de WordPress, toda la lógica de servidor e infraestructura debió crearse por completo:

### 4.1 Backend RESTful (Node.js/Express)
* Creación de endpoints para registro (`/api/auth/register`), inicio de sesión (`/api/auth/login`) con hashing seguro vía `bcryptjs` y firma digital con tokens JWT (`jsonwebtoken`).
* APIs transaccionales para lectura/escritura de ítems en carrito (`/api/cart`) y confirmación de pedidos (`/api/checkout`), enlazando las operaciones al usuario autenticado.

### 4.2 Lógica Cliente Reactiva (`app.js`)
* Programación del estado reactivo del cliente (`state.cart`, `state.products`).
* Implementación del renderizado dinámico del catálogo y filtros asíncronos por familia olfativa sin recarga de página.
* Solución al bug de sincronización: Introducción de la bandera de estado `state.cartLoaded` que previene redireccionamientos inmediatos e incorrectos desde la pasarela de pagos antes de recibir la confirmación del servidor.

### 4.3 Infraestructura Terraform (IaC)
* **VPC Segmentada**: Configuración declarativa de subredes, tablas de ruteo, asociaciones de internet gateway y NAT gateway.
* **Auto Scaling Group (ASG) & ALB**: Reglas para aprovisionar dinámicamente entre 2 y 4 servidores según demanda, asociándolos a un Target Group balanceado por el ALB.
* **Seguridad de Red (Security Groups)**: Configuración de políticas de tráfico cerrado donde el tráfico web solo fluye de `Internet -> ALB (Puerto 80) -> EC2 Web (Puerto 3000) -> RDS MySQL (Puerto 3306)`.
* **Telemetry Stack**: Programación automática de alarmas en CloudWatch, tópicos SNS de notificación de salud por correo y rastros CloudTrail de auditoría.

---

## 5. Requerimientos Necesarios en Código y Cloud

### 5.1 En el Código (Node.js / JS)
* **Control de Asincronismo**: Manejo estricto de promesas (`async/await`) en las llamadas HTTP locales para evitar la actualización desordenada de la interfaz gráfica.
* **Seguridad en la Base de Datos**: Integración de Sequelize con políticas de migración automática (`sequelize.sync()`) para desplegar la estructura relacional inmediatamente al iniciar el servidor en el entorno de AWS.
* **Encriptación de Sesiones**: Implementación de Bearer Tokens (JWT) para la protección de endpoints sensibles (Carts, Orders).

### 5.2 En la Nube (AWS / Terraform)
* **NAT Gateway**: Requerido en las subredes públicas para permitir que los servidores de la subred privada descarguen dependencias `npm` y paquetes del sistema durante el aprovisionamiento, bloqueando cualquier intento de conexión entrante desde el exterior.
* **Launch Template (User Data)**: Script Bash automatizado para configurar la instancia EC2 desde su nacimiento: instala Node.js 20, descarga la versión más reciente del código, inyecta dinámicamente las variables de entorno correspondientes y levanta el daemon del sistema para mantener el servicio activo de forma permanente.
