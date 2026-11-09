-- ============================================================
--  Echo — usuario de la aplicación con PRIVILEGIOS MÍNIMOS
--  La app NO usa el usuario maestro "admin" de RDS.
--  Ejecutar como admin DESPUÉS de schema.sql.
--  >>> Cambia la contraseña y usa la misma en DB_PASS del .env <<<
-- ============================================================

CREATE USER IF NOT EXISTS 'echo_app'@'%'
  IDENTIFIED BY 'CambiaEstaClave_2026'
  REQUIRE SSL;                                   -- solo conexiones cifradas (TLS)

-- Solo lectura/escritura de datos: no puede borrar tablas ni crear usuarios.
GRANT SELECT, INSERT, UPDATE, DELETE ON echo.* TO 'echo_app'@'%';

FLUSH PRIVILEGES;

-- Verificación:
SHOW GRANTS FOR 'echo_app'@'%';
