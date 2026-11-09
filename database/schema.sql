-- ============================================================
--  Echo — esquema de base de datos
--  Motor: Amazon RDS for MySQL 8.x  (compatible con MariaDB 10.6+)
--  Ejecutar como usuario maestro:
--    mysql -h <endpoint-rds> -u admin -p --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem < database/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS echo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE echo;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW  IF EXISTS v_estadisticas;
DROP VIEW  IF EXISTS v_ranking_publicaciones;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS post_hashtags;
DROP TABLE IF EXISTS hashtags;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS follows;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Usuarios
-- ------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(30)  NOT NULL,
  email         VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,            -- bcrypt (password_hash de PHP)
  full_name     VARCHAR(60)  NOT NULL,
  bio           VARCHAR(160) NULL,
  website       VARCHAR(120) NULL,
  avatar_key    VARCHAR(200) NULL,                -- clave del objeto en S3
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Publicaciones (la imagen vive en S3; aquí solo su clave)
-- ------------------------------------------------------------
CREATE TABLE posts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  image_key  VARCHAR(200) NOT NULL,
  thumb_key  VARCHAR(200) NOT NULL,
  width      SMALLINT UNSIGNED NOT NULL,
  height     SMALLINT UNSIGNED NOT NULL,
  caption    VARCHAR(2200) NULL,
  location   VARCHAR(80)  NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_posts_user_created (user_id, created_at),
  CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seguidores (relación muchos a muchos de users consigo misma)
-- ------------------------------------------------------------
CREATE TABLE follows (
  follower_id  INT UNSIGNED NOT NULL,              -- quien sigue
  following_id INT UNSIGNED NOT NULL,              -- a quien sigue
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, following_id),
  KEY idx_follows_following (following_id),
  CONSTRAINT fk_follows_follower  FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_follows_following FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Me gusta (un usuario solo puede dar 1 like por publicación)
-- ------------------------------------------------------------
CREATE TABLE likes (
  user_id    INT UNSIGNED NOT NULL,
  post_id    INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, post_id),
  KEY idx_likes_post (post_id),
  CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Comentarios
-- ------------------------------------------------------------
CREATE TABLE comments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  body       VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_comments_post (post_id, created_at),
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Hashtags y su relación con publicaciones
-- ------------------------------------------------------------
CREATE TABLE hashtags (
  id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tag VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_hashtags_tag (tag)
) ENGINE=InnoDB;

CREATE TABLE post_hashtags (
  post_id    INT UNSIGNED NOT NULL,
  hashtag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, hashtag_id),
  KEY idx_ph_hashtag (hashtag_id),
  CONSTRAINT fk_ph_post    FOREIGN KEY (post_id)    REFERENCES posts(id)    ON DELETE CASCADE,
  CONSTRAINT fk_ph_hashtag FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Notificaciones (like, comentario, nuevo seguidor)
-- ------------------------------------------------------------
CREATE TABLE notifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,                -- destinatario
  actor_id   INT UNSIGNED NOT NULL,                -- quien hizo la acción
  type       ENUM('like','comment','follow') NOT NULL,
  post_id    INT UNSIGNED NULL,
  is_read    TINYINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_user (user_id, is_read, created_at),
  CONSTRAINT fk_notif_user  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_post  FOREIGN KEY (post_id)  REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Vistas para el informe / pruebas
-- ------------------------------------------------------------
CREATE VIEW v_estadisticas AS
SELECT
  (SELECT COUNT(*) FROM users)         AS usuarios,
  (SELECT COUNT(*) FROM posts)         AS publicaciones,
  (SELECT COUNT(*) FROM likes)         AS me_gusta,
  (SELECT COUNT(*) FROM comments)      AS comentarios,
  (SELECT COUNT(*) FROM follows)       AS relaciones_seguimiento,
  (SELECT COUNT(*) FROM hashtags)      AS hashtags;

CREATE VIEW v_ranking_publicaciones AS
SELECT p.id, u.username, LEFT(p.caption, 60) AS descripcion,
       COUNT(DISTINCT l.user_id) AS likes,
       COUNT(DISTINCT c.id)      AS comentarios
  FROM posts p
  JOIN users u      ON u.id = p.user_id
  LEFT JOIN likes l ON l.post_id = p.id
  LEFT JOIN comments c ON c.post_id = p.id
 GROUP BY p.id, u.username, p.caption
 ORDER BY likes DESC, comentarios DESC;
