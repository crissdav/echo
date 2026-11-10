# 3CH0 (Echo)

**3CH0** (se pronuncia "Echo") es una red social de fotos (al estilo Instagram) desplegada en **Amazon Web Services**.
Con ella puedes publicar fotos, seguir a otras cuentas, dar "me gusta" con doble toque, comentar,
usar #hashtags y @menciones, y recibir notificaciones.

> Proyecto del curso **Computación en la Nube**, FIIS, Universidad Nacional Agraria de la Selva (UNAS).
> Autor: Cristopher Leandro Dávila Isidro ([@crissdav](https://github.com/crissdav)).

## Arquitectura

```
                    ┌──────────────────────── VPC echo-vpc (us-east-2) ───────────────────────┐
 Usuario ── HTTP ──▶│  Subred pública                     Subredes privadas (2 AZ)            │
 (navegador)        │  ┌───────────────────┐   :3306/TLS  ┌──────────────────────────┐        │
                    │  │ EC2 t3.micro      │─────────────▶│ RDS MySQL db.t3.micro    │        │
                    │  │ Ubuntu 24.04      │  sg-echo-db   │ sin acceso público       │        │
                    │  │ Apache + PHP 8.3  │  (solo desde  └──────────────────────────┘        │
                    │  │ IP elástica       │   sg-echo-web)                                    │
                    │  └─────────┬─────────┘                                                   │
                    └────────────┼─────────────────────────────────────────────────────────────┘
                                 │ rol IAM (sin access keys)
                                 ▼
                       S3 echo-media-crissdav (privado, URLs prefirmadas)
```

| Capa | Servicio / tecnología |
|---|---|
| Cómputo | Amazon EC2 t3.micro · Ubuntu Server 24.04 LTS · Apache 2.4 · PHP 8.3 |
| Base de datos | Amazon RDS for MySQL 8 · db.t3.micro · Single-AZ · subred privada |
| Archivos | Amazon S3 (bucket privado) + AWS SDK for PHP v3 + rol IAM de la instancia |
| Frontend | HTML5, Bootstrap 5, Bootstrap Icons y JavaScript nativo (fetch, IntersectionObserver) |
| Red | VPC propia, grupos de seguridad encadenados e IP elástica |
| Entrega / HTTPS | Amazon CloudFront delante de la EC2 (certificado de AWS en `*.cloudfront.net`) |

## Funcionalidades

- Registro e inicio de sesión (bcrypt, regeneración de sesión y límite de intentos fallidos guardado en RDS, por cuenta y por IP).
- Feed de las cuentas que sigues, con **scroll infinito** y paginación por cursor.
- Publicar fotos: validación del tipo real, corrección de la orientación EXIF, recorte, versión de 1080 px y miniatura, eliminación de metadatos GPS y subida a S3.
- **Me gusta** con botón o con **doble toque** (animación de corazón) y actualización optimista.
- Comentarios en vivo, #hashtags y @menciones con enlaces, y página por hashtag.
- Seguir y dejar de seguir, contadores, sugerencias ("Le siguen N cuentas que sigues") y listas de seguidores y seguidos.
- Explorar: búsqueda de personas, tendencias y una cuadrícula de lo más popular.
- Notificaciones agrupadas (Hoy / Esta semana / Este mes).
- **Estado del sistema** (`/status`): ID de la instancia EC2, zona de disponibilidad, rol IAM, versión de RDS, cifrado TLS y latencias. También en JSON en `/health`.

## Seguridad aplicada

- Todo el tráfico de los usuarios va por **HTTPS** gracias a CloudFront. La EC2 solo acepta HTTP desde la lista de prefijos de CloudFront, así que no se puede saltar el CDN entrando por la IP.
- Límite de intentos de login guardado en RDS: 5 fallos en 15 min bloquean la cuenta 5 min, y 20 fallos desde una IP la bloquean 15 min. Se usa la IP real del usuario (`CloudFront-Viewer-Address`).
- La BD **no es pública**: RDS vive en subredes privadas y su grupo de seguridad solo acepta el puerto 3306 desde el grupo de seguridad de la EC2.
- La aplicación usa el usuario `echo_app`, con privilegios mínimos (`SELECT, INSERT, UPDATE, DELETE`) y `REQUIRE SSL`.
- La conexión a RDS va **cifrada con TLS** (certificado oficial de AWS).
- Las credenciales están en `.env`, fuera del repositorio (`.gitignore`) y fuera de la carpeta pública.
- S3 se usa **sin access keys**: la EC2 asume un rol IAM con una política de mínimo privilegio sobre un solo bucket.
- El bucket es privado (Block Public Access activado) y las imágenes se entregan con URLs prefirmadas temporales.
- SSH (22) está permitido solo desde la IP del administrador.
- Contra inyección SQL se usan consultas preparadas reales con PDO.
- Contra XSS se escapa todo el HTML. Hay tokens CSRF en formularios y peticiones AJAX, y cookies `HttpOnly` + `SameSite`.

## Estructura

```
echo/
├── app/
│   ├── Controllers/   Auth, Feed, Post, Social (AJAX), Profile, Explore, Notification, System
│   ├── Core/          Env, Database (PDO+TLS), Router, Auth, Csrf, View, Storage (S3), Image (GD)
│   ├── Models/        User, Post, Social, LoginThrottle
│   ├── Views/         layouts, partials y páginas
│   ├── bootstrap.php
│   └── helpers.php
├── database/
│   ├── schema.sql        9 tablas + 2 vistas
│   ├── usuario_app.sql   usuario con privilegios mínimos
│   ├── migrations/       cambios para una base ya creada (no borran datos)
│   └── seed.php          datos de demostración (12 usuarios, 24 fotos)
├── deploy/
│   ├── setup.sh          instalación automática en Ubuntu 24.04
│   ├── apache-echo.conf  VirtualHost
│   └── iam-policy-s3.json
├── public/               ← DocumentRoot (lo único expuesto a Internet)
│   ├── index.php  .htaccess
│   └── assets/ (css, js, img)
├── storage/uploads/      solo para el modo local
├── .env.example
└── composer.json
```

## Despliegue rápido (resumen)

1. Crea la VPC, los grupos de seguridad, el RDS, el bucket S3, el rol IAM y la EC2 (paso a paso en la guía de despliegue).
2. Conéctate por SSH a la EC2 y ejecuta:
   ```bash
   curl -fsSL https://raw.githubusercontent.com/crissdav/echo/main/deploy/setup.sh -o setup.sh
   bash setup.sh https://github.com/crissdav/echo.git
   ```
3. Edita `/var/www/echo/.env` y carga la base de datos:
   ```bash
   cd /var/www/echo
   CA=/etc/ssl/certs/rds-global-bundle.pem
   mysql -h <endpoint-rds> -u admin -p --ssl-ca=$CA < database/schema.sql
   mysql -h <endpoint-rds> -u admin -p --ssl-ca=$CA < database/usuario_app.sql
   php database/seed.php TuClaveSegura   # una sola vez; se niega si ya hay usuarios reales
   ```
4. Crea la distribución de CloudFront delante de la EC2 (HTTPS sin dominio propio) y restringe
   el grupo de seguridad a la lista de prefijos de CloudFront.
5. Abre `https://<id>.cloudfront.net`, regístrate o entra con una cuenta demo y la clave del paso 3.

## Ejecutar en local (opcional)

Necesitas PHP 8.1 o superior con las extensiones `pdo_mysql`, `gd` y `mbstring`, y MySQL o MariaDB.

```bash
cp .env.example .env    # APP_ENV=local, STORAGE_DRIVER=local, TRUST_CLOUDFRONT=false,
                        # SHOW_DEMO_LOGIN=true, DB_SSL_CA vacío, DB_* de tu MySQL local
mysql -u root -p < database/schema.sql
php database/seed.php
php -S localhost:8000 -t public public/index.php
```
