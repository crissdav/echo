-- ============================================================
--  Migración 001 — tabla de intentos fallidos de login
--  Para una base que YA existe (schema.sql la crea en instalaciones nuevas).
--  No borra datos. Ejecutar una vez como usuario maestro:
--    mysql -h <endpoint-rds> -u admin -p --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem echo < database/migrations/001_login_attempts.sql
--  El usuario echo_app ya tiene SELECT/INSERT/DELETE sobre echo.*, no hace falta otro GRANT.
-- ============================================================

CREATE TABLE IF NOT EXISTS login_attempts (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip           VARCHAR(45)  NOT NULL,
  login_key    VARCHAR(120) NOT NULL,
  attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempts_key (login_key, attempted_at),
  KEY idx_attempts_ip  (ip, attempted_at)
) ENGINE=InnoDB;
