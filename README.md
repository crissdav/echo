# 3CH0 (Echo)

**3CH0** (se pronuncia "Echo") es una red social de fotos (al estilo Instagram) desplegada en **Amazon Web Services**.
Con ella puedes publicar fotos, seguir a otras cuentas, dar "me gusta" con doble toque, comentar,
usar #hashtags y @menciones, y recibir notificaciones.

> Proyecto del curso **Computación en la Nube**, FIIS, Universidad Nacional Agraria de la Selva (UNAS).
> Autor: Cristopher Leandro Dávila Isidro ([@crissdav](https://github.com/crissdav)).

## Arquitectura

```
 Usuario ── HTTPS ──▶ CloudFront (dxxxx.cloudfront.net, certificado de AWS)
 (navegador)               │ HTTP :80, solo desde la lista de prefijos de CloudFront
                           ▼
                    ┌──────────────────────── VPC echo-vpc (us-east-2) ───────────────────────┐
                    │  Subred pública                     Subredes privadas (2 AZ)            │
                    │  ┌───────────────────┐   :3306/TLS  ┌──────────────────────────┐        │
                    │  │ EC2 t3.micro      │─────────────▶│ RDS MySQL db.t3.micro    │        │
                    │  │ Ubuntu 24.04      │  echo-db-sg   │ sin acceso público       │        │
                    │  │ Apache + PHP 8.3  │  (solo desde  └──────────────────────────┘        │
                    │  │ IP elástica       │   echo-web-sg)                                    │
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

## Despliegue en AWS desde cero

Guía completa, en orden, para levantar 3CH0 en una cuenta de AWS vacía usando la consola web.
Tiempo aproximado: 1 h a 1 h 30 min (RDS y CloudFront tardan en crearse).

**Antes de empezar**

- Una cuenta de AWS con acceso a la consola y, de preferencia, una **alarma de presupuesto**
  (*Billing and Cost Management → Budgets → Create budget*):
  - Cuenta con la capa gratuita clásica: plantilla **Zero spend budget**.
  - Cuenta del **plan gratuito con créditos**: **Cost budget** mensual (por ejemplo 15 USD) y, en
    *Advanced options*, **desmarca "Credits"** para que avise por el uso real aunque los créditos lo cubran.
- El repositorio publicado en GitHub (público), porque la EC2 lo descarga con `git clone`.
- Trabaja siempre en la misma región: **US East (Ohio) `us-east-2`**, selector arriba a la derecha.
  CloudFront e IAM son globales y no dependen de la región.

**Nombres usados en la guía** (si cambias alguno, cámbialo en todos los pasos)

| Recurso | Nombre |
|---|---|
| VPC | `echo-vpc` |
| Grupo de seguridad de la EC2 | `echo-web-sg` |
| Grupo de seguridad de la BD | `echo-db-sg` |
| Bucket S3 | `echo-media-crissdav` (debe ser único en todo AWS: usa el tuyo) |
| Política / rol IAM | `EchoS3Policy` / `EchoEC2Role` |
| Base de datos RDS | `echo-db` |
| Instancia EC2 | `echo-web` |
| Par de claves SSH | `echo-key` |

### Paso 1 · Red (VPC)

**VPC → Your VPCs → Create VPC → "VPC and more"**

| Campo | Valor |
|---|---|
| Name tag auto-generation | `echo` (genera `echo-vpc`, `echo-subnet-public1-…`, etc.) |
| IPv4 CIDR | `10.0.0.0/16` |
| Number of Availability Zones | **2** (RDS exige subredes en 2 zonas) |
| Number of public subnets | **2** |
| Number of private subnets | **2** |
| NAT gateways | **None** (cuesta dinero por hora y no hace falta) |
| VPC endpoints | **S3 Gateway** (gratis) |
| DNS options | ✅ Enable DNS hostnames · ✅ Enable DNS resolution |

Pulsa **Create VPC**. Resultado: la EC2 irá en una subred **pública** y la RDS en las **privadas**, sin salida a internet.

> "Enable DNS hostnames" es necesario: CloudFront se conecta a la EC2 por su nombre DNS público
> (`ec2-X-X-X-X.us-east-2.compute.amazonaws.com`), no por IP.

### Paso 2 · Grupos de seguridad

**EC2 → Security Groups → Create security group** (los dos en la VPC `echo-vpc`).

**`echo-web-sg`** (para la EC2)

| Tipo | Puerto | Origen | Para qué |
|---|---|---|---|
| SSH | 22 | **My IP** | Administrar la instancia solo desde tu PC |
| HTTP | 80 | `0.0.0.0/0` | **Temporal**: para probar antes de tener CloudFront (se cambia en el paso 11) |

**`echo-db-sg`** (para la RDS)

| Tipo | Puerto | Origen | Para qué |
|---|---|---|---|
| MySQL/Aurora | 3306 | **`echo-web-sg`** (elige el grupo, no una IP) | Solo la EC2 puede hablar con la BD |

Deja las reglas de salida (outbound) por defecto. Si tu IP de casa cambia, actualiza la regla SSH con "My IP".

### Paso 3 · Bucket S3 para las fotos

**S3 → Create bucket**

| Campo | Valor |
|---|---|
| Region | `us-east-2` |
| Bucket name | `echo-media-crissdav` (o el tuyo) |
| Object Ownership | ACLs disabled |
| Block Public Access | ✅ **Block all public access** (las fotos se sirven con URLs prefirmadas) |
| Bucket Versioning | Disable |
| Default encryption | SSE-S3 |

### Paso 4 · Permisos IAM (sin access keys)

La EC2 accede a S3 mediante un **rol**; no se guarda ninguna clave de AWS en el servidor.

1. **IAM → Policies → Create policy → JSON**: pega el contenido de [`deploy/iam-policy-s3.json`](deploy/iam-policy-s3.json).
   Si tu bucket se llama distinto, cambia `echo-media-crissdav` en las dos líneas `Resource`.
   Nombre: `EchoS3Policy`.
2. **IAM → Roles → Create role**
   - Trusted entity: **AWS service → EC2**.
   - Permisos: marca `EchoS3Policy`.
   - Nombre: `EchoEC2Role`.

La política solo permite listar, leer, subir y borrar objetos **de ese bucket** (mínimo privilegio).

### Paso 5 · Base de datos (Amazon RDS for MySQL)

1. **RDS → Subnet groups → Create DB subnet group**
   - Nombre `echo-db-subnets`, VPC `echo-vpc`.
   - Zonas: las 2 que usó la VPC; subredes: las **dos privadas** (`echo-subnet-private1-…` y `…private2-…`).
2. **RDS → Databases → Create database**

| Campo | Valor |
|---|---|
| Creation method | Standard create |
| Engine | **MySQL** 8.0 o 8.4 |
| Templates | **Free tier** (o Sandbox) |
| DB instance identifier | `echo-db` |
| Master username | `admin` |
| Credentials | Self managed; escribe una contraseña y **guárdala** |
| Instance class | `db.t3.micro` (o `db.t4g.micro`) |
| Storage | gp3, 20 GiB; desmarca "Enable storage autoscaling" |
| Compute resource | Don't connect to an EC2 compute resource |
| VPC / DB subnet group | `echo-vpc` / `echo-db-subnets` |
| Public access | **No** |
| VPC security group | Choose existing → **solo `echo-db-sg`** (quita `default`) |
| Availability Zone | No preference |
| Database authentication | Password authentication |
| Monitoring | Desmarca Performance Insights / Enhanced monitoring (evita costos) |
| Additional configuration → Initial database name | déjalo vacío (`schema.sql` crea la BD `echo`) |
| Backups | 1 día de retención está bien |

Tarda 5 a 10 minutos. Cuando el estado sea **Available**, copia el **Endpoint**
(pestaña *Connectivity & security*), por ejemplo `echo-db.abc123xyz.us-east-2.rds.amazonaws.com`.

### Paso 6 · Servidor (Amazon EC2)

**EC2 → Instances → Launch instances**

| Campo | Valor |
|---|---|
| Name | `echo-web` |
| AMI | **Ubuntu Server 24.04 LTS** (64-bit x86) |
| Instance type | **t3.micro** |
| Key pair | Create new key pair → `echo-key`, RSA, formato **.pem** → se descarga; guárdalo bien |
| Network settings → Edit | VPC `echo-vpc` · Subnet **una pública** (`echo-subnet-public1-…`) · Auto-assign public IP **Enable** |
| Firewall | Select existing security group → **`echo-web-sg`** |
| Storage | 20 GiB gp3 |
| Advanced details → IAM instance profile | **`EchoEC2Role`** |
| Advanced details → Metadata version | V2 only (token required) — la página `/status` ya usa IMDSv2 |

Pulsa **Launch instance**.

**IP elástica** (para que la dirección no cambie al detener/iniciar la instancia):
**EC2 → Elastic IPs → Allocate Elastic IP address → Allocate**, luego
**Actions → Associate Elastic IP address** → instancia `echo-web` → Associate.

Anota dos datos de la instancia (pestaña *Details*):
- **Public IPv4 address**: la IP elástica.
- **Public IPv4 DNS**: `ec2-X-X-X-X.us-east-2.compute.amazonaws.com` (lo usa CloudFront).

### Paso 7 · Conectarse por SSH (desde Windows)

En PowerShell, en la carpeta donde está `echo-key.pem`. Primero, deja la llave legible solo por tu usuario
(si no, SSH la rechaza por "permisos demasiado abiertos"):

```powershell
icacls echo-key.pem /inheritance:r
icacls echo-key.pem /grant:r "$($env:USERNAME):(R)"
ssh -i echo-key.pem ubuntu@<IP-ELASTICA>
```

Acepta la huella con `yes`. Si no conecta: revisa que la regla SSH de `echo-web-sg` tenga tu IP actual.

### Paso 8 · Instalar la aplicación

Ya dentro de la EC2:

```bash
curl -fsSL https://raw.githubusercontent.com/crissdav/echo/main/deploy/setup.sh -o setup.sh
bash setup.sh https://github.com/crissdav/echo.git
```

El script ([`deploy/setup.sh`](deploy/setup.sh)) crea 1 GB de swap, instala Apache + PHP 8.3 + extensiones + Composer
+ cliente MySQL, clona el repo en `/var/www/echo`, instala el AWS SDK, crea `.env` desde `.env.example`,
descarga el certificado TLS de RDS, ajusta los límites de subida de PHP y activa el sitio de Apache.

### Paso 9 · Configurar `.env`

```bash
cd /var/www/echo
nano .env
```

| Variable | Valor |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `http://<IP-ELASTICA>` por ahora; en el paso 11 pasa a `https://dxxxx.cloudfront.net` |
| `TRUST_CLOUDFRONT` | **`false` por ahora**; `true` recién en el paso 11 |
| `DB_HOST` | el endpoint de RDS del paso 5 |
| `DB_PORT` / `DB_NAME` | `3306` / `echo` |
| `DB_USER` / `DB_PASS` | `echo_app` / una contraseña nueva, **distinta** a la de `admin` (solo letras, números y `_`) |
| `DB_SSL_CA` | `/etc/ssl/certs/rds-global-bundle.pem` |
| `STORAGE_DRIVER` | `s3` |
| `AWS_REGION` / `S3_BUCKET` | `us-east-2` / el nombre de tu bucket |
| `SHOW_DEMO_LOGIN` | **`false`** |

No se pone ninguna access key de AWS: el SDK usa el rol `EchoEC2Role` automáticamente.
Guarda con `Ctrl+O`, `Enter`, `Ctrl+X`.

### Paso 10 · Crear la base de datos y probar

```bash
cd /var/www/echo
CA=/etc/ssl/certs/rds-global-bundle.pem
RDS=echo-db.abc123xyz.us-east-2.rds.amazonaws.com   # tu endpoint

# 1) Tablas y vistas (pide la contraseña de admin)
mysql -h $RDS -u admin -p --ssl-ca=$CA < database/schema.sql

# 2) Usuario de la app con privilegios mínimos, con la MISMA clave que pusiste en DB_PASS.
#    Se reemplaza al vuelo (no se modifica el archivo del repo).
sed "s/CambiaEstaClave_2026/TU_CLAVE_DE_DB_PASS/" database/usuario_app.sql | mysql -h $RDS -u admin -p --ssl-ca=$CA

# 3) Datos de demostración (opcional, UNA sola vez, con una clave propia para las cuentas demo)
php database/seed.php TuClaveDemo2026

# 4) Verificación
curl -s http://localhost/health
```

`/health` debe responder `"status":"ok"` con `db.ok: true`, `db.tls` con la versión de TLS y `storage` en `s3`.
Abre `http://<IP-ELASTICA>` en el navegador: debe cargar el login. Regístrate, sube una foto y comprueba
que se ve (eso confirma que S3 y el rol IAM funcionan).

> El seed **borra todos los datos** antes de insertar. Por eso se niega a ejecutarse si ya hay usuarios
> reales (correo distinto de `@echo.demo`) y, en producción, si no le das una contraseña propia.

### Paso 11 · HTTPS con CloudFront (sin dominio propio)

**CloudFront → Distributions → Create distribution**

| Campo | Valor |
|---|---|
| Origin domain | el **Public IPv4 DNS** de la EC2 (`ec2-X-X-X-X.us-east-2.compute.amazonaws.com`); no acepta una IP |
| Protocol | **HTTP only**, puerto 80 |
| Name | `echo-ec2` |
| Viewer protocol policy | **Redirect HTTP to HTTPS** |
| Allowed HTTP methods | **GET, HEAD, OPTIONS, PUT, POST, PATCH, DELETE** (sin esto no funcionan el login ni las publicaciones) |
| Cache policy | **CachingDisabled** (las páginas son dinámicas y personales) |
| Origin request policy | **AllViewerAndCloudFrontHeaders-2022-06** (reenvía cookies, formularios y las cabeceras `CloudFront-Viewer-Address` y `CloudFront-Forwarded-Proto`) |
| Web Application Firewall (WAF) | **Do not enable security protections** (evita costos) |
| Price class | Use all edge locations (o la opción por defecto) |

Si el asistente nuevo de la consola muestra menos opciones, créala con los valores que permita y luego
edítalos en **Behaviors → Default (*) → Edit**.

*(Opcional)* **Behaviors → Create behavior**: path `/assets/*`, mismo origen, Redirect HTTP to HTTPS,
cache policy **CachingOptimized**. Así el CSS, JS e íconos se sirven desde la caché de CloudFront.

Cuando el estado pase de *Deploying* a una fecha (unos 5 minutos), copia el **Distribution domain name**
(`dxxxxxxxxxxxx.cloudfront.net`) y abre `https://dxxxxxxxxxxxx.cloudfront.net`: debe cargar con candado.

**Cerrar la EC2 para que solo CloudFront pueda entrar** (obligatorio):
**EC2 → Security Groups → `echo-web-sg` → Edit inbound rules**

1. **Elimina** la regla HTTP 80 desde `0.0.0.0/0` (y la de 443 si existe).
2. **Agrega** HTTP 80 con origen **Prefix list → `com.amazonaws.global.cloudfront.origin-facing`**.
3. Deja SSH 22 solo desde tu IP.

> Esa lista de prefijos cuenta como ~55 reglas del límite del grupo. Si AWS se queja del límite,
> deja en `echo-web-sg` solo esa regla y la de SSH.

Por último, en la EC2:

```bash
cd /var/www/echo && nano .env
#   APP_URL=https://dxxxxxxxxxxxx.cloudfront.net
#   TRUST_CLOUDFRONT=true
```

`TRUST_CLOUDFRONT=true` hace que la app tome la IP real del usuario y detecte el HTTPS (cookie de sesión
con `Secure`). **Solo es seguro con el paso anterior hecho**: si la EC2 aceptara tráfico directo,
cualquiera podría falsificar esas cabeceras.

### Paso 12 · Verificación final

| Prueba | Resultado esperado |
|---|---|
| `https://dxxxx.cloudfront.net` | Carga el login con candado, **sin** la tarjeta de cuenta demo |
| `http://dxxxx.cloudfront.net` | Redirige a `https://` |
| `http://<IP-ELASTICA>` | **No responde** (se queda cargando): la EC2 solo acepta a CloudFront |
| `https://dxxxx.cloudfront.net/health` | JSON con `"status":"ok"` |
| Registro, publicar foto, like, comentario | Funcionan; la foto se ve |
| 5 contraseñas incorrectas seguidas en una cuenta | "Demasiados intentos. Espera 5 minutos…" |
| `/status` (con sesión iniciada) | Muestra ID de instancia, zona, rol IAM, versión de RDS y TLS |

Comparte la URL `https://dxxxx.cloudfront.net`.

### Actualizar la aplicación

```bash
cd /var/www/echo
sudo git pull
composer install --no-dev --optimize-autoloader --no-interaction   # si cambió composer.json
```

Si el cambio trae un archivo en `database/migrations/`, ejecútalo una vez como `admin`
(no borra datos):

```bash
mysql -h $RDS -u admin -p --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem echo < database/migrations/001_login_attempts.sql
```

No vuelvas a ejecutar `schema.sql` en producción: hace `DROP TABLE` y borra todo.

### Problemas frecuentes

| Síntoma | Causa probable y solución |
|---|---|
| CloudFront responde **502 / 504** | El *Origin domain* no es el DNS público de la EC2, el protocolo no es *HTTP only*, o `echo-web-sg` no tiene la regla de la lista de prefijos de CloudFront |
| El login "rebota" a `/login` sin error, o falla el CSRF | Falta la *Origin request policy* `AllViewerAndCloudFrontHeaders-2022-06` (no se reenvían las cookies) o no se permitió POST |
| "Demasiados intentos" le sale a **todos** | La app está detrás de CloudFront con `TRUST_CLOUDFRONT=false`: cuenta los fallos por la IP de CloudFront, compartida por muchos usuarios. Pon `true` (con el grupo de seguridad cerrado) |
| Error de conexión a la BD | Endpoint mal copiado, `echo-db-sg` no permite 3306 desde `echo-web-sg`, `DB_PASS` distinta a la de `usuario_app.sql`, o falta `DB_SSL_CA` (el usuario exige TLS) |
| Las fotos no se ven o no se suben | El rol `EchoEC2Role` no está asociado a la instancia, el bucket del `.env` no coincide con el de la política IAM, o la región es otra |
| SSH: "Permission denied (publickey)" o "bad permissions" | Usuario debe ser `ubuntu`; aplica los `icacls` del paso 7 |
| SSH se queda esperando | Tu IP cambió: actualiza la regla SSH de `echo-web-sg` con "My IP" |
| "Base de datos no disponible" (503) y `/health` muestra `"env":null` | Apache no puede leer `.env`. Pasa si se editó con `sed -i` u otra herramienta que recrea el archivo: `sudo chown ubuntu:www-data .env && sudo chmod 640 .env`. Edítalo con `nano`, que conserva los permisos |
| Errores 500 | Revisa `sudo tail -n 50 /var/log/apache2/error.log` |

### Costos y limpieza

Con la capa gratuita, la t3.micro, la db.t3.micro, 20 GB de almacenamiento, S3 y CloudFront entran en los
límites gratuitos para un proyecto de curso; lo que más se suele cobrar es la **IPv4 pública**
(~0.005 USD/hora) según el tipo de cuenta. Revisa *Billing → Free tier* de vez en cuando.

Para **apagar todo** al terminar el curso (en este orden):

1. CloudFront: *Disable* la distribución, esperar y luego *Delete*.
2. EC2: *Terminate* la instancia `echo-web`.
3. Elastic IP: *Release* (una IP elástica sin asociar también se cobra).
4. RDS: *Delete* `echo-db` (puedes desmarcar el snapshot final).
5. S3: *Empty* y luego *Delete* el bucket.
6. VPC: *Delete VPC* `echo-vpc` (borra subredes, endpoint y grupos de seguridad).
7. IAM: borra el rol `EchoEC2Role` y la política `EchoS3Policy`.

## Ejecutar en local (opcional)

Necesitas PHP 8.1 o superior con las extensiones `pdo_mysql`, `gd` y `mbstring`, y MySQL o MariaDB.

```bash
cp .env.example .env    # APP_ENV=local, STORAGE_DRIVER=local, TRUST_CLOUDFRONT=false,
                        # SHOW_DEMO_LOGIN=true, DB_SSL_CA vacío, DB_* de tu MySQL local
mysql -u root -p < database/schema.sql
php database/seed.php
php -S localhost:8000 -t public public/index.php
```
