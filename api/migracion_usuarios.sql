-- Ejecutar UNA sola vez en phpMyAdmin (pestaña SQL), con la base "aterriza" ya creada.
-- Unifica foraneos y residentes en "usuarios" (así amigos, mensajes y publicaciones
-- pueden apuntar a un único id) y crea las tablas que usa aterriza.html.
USE aterriza;

CREATE TABLE IF NOT EXISTS usuarios (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol             ENUM('foraneo','residente') NOT NULL,
  nombre_completo VARCHAR(120) NOT NULL,
  correo          VARCHAR(120) NOT NULL UNIQUE,
  contrasena      VARCHAR(255) NOT NULL,
  universidad     VARCHAR(150) NULL,   -- foráneo
  carrera         VARCHAR(150) NULL,   -- foráneo
  edad            TINYINT UNSIGNED NULL,
  sexo            VARCHAR(20)  NULL,   -- residente
  procedencia     VARCHAR(150) NULL,   -- "ciudad de origen" del perfil
  bio             TEXT NULL,
  musica          VARCHAR(150) NULL,
  pasatiempos     VARCHAR(255) NULL,
  comida          VARCHAR(150) NULL,
  plan_favorito   VARCHAR(80)  NULL,
  fecha_registro  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Pasa las cuentas que ya creaste (las contraseñas ya están cifradas)
INSERT IGNORE INTO usuarios (rol, nombre_completo, correo, contrasena, universidad, carrera, fecha_registro)
SELECT 'foraneo', nombre_completo, correo, contrasena, universidad, carrera, fecha_registro FROM foraneos;

INSERT IGNORE INTO usuarios (rol, nombre_completo, correo, contrasena, edad, sexo, procedencia, fecha_registro)
SELECT 'residente', nombre_completo, correo, contrasena, edad, sexo, procedencia, fecha_registro FROM residentes;

DROP TABLE IF EXISTS foraneos;
DROP TABLE IF EXISTS residentes;

-- Fotos de la pestaña Publicaciones / perfil
CREATE TABLE IF NOT EXISTS publicaciones (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id   INT UNSIGNED NOT NULL,
  ruta_imagen  VARCHAR(255) NOT NULL,
  descripcion  VARCHAR(255) NULL,
  fecha        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pub_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_pub_usuario (usuario_id)
) ENGINE=InnoDB;

-- Amistades: una fila por pareja, siempre con usuario_a < usuario_b
CREATE TABLE IF NOT EXISTS amistades (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_a  INT UNSIGNED NOT NULL,
  usuario_b  INT UNSIGNED NOT NULL,
  fecha      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_amis_a FOREIGN KEY (usuario_a) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_amis_b FOREIGN KEY (usuario_b) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT uq_amistad UNIQUE (usuario_a, usuario_b),
  CONSTRAINT ck_amistad_orden CHECK (usuario_a < usuario_b)
) ENGINE=InnoDB;

-- Chat entre amigos
CREATE TABLE IF NOT EXISTS mensajes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  remitente_id    INT UNSIGNED NOT NULL,
  destinatario_id INT UNSIGNED NOT NULL,
  texto           VARCHAR(1000) NOT NULL,
  fecha           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_rem  FOREIGN KEY (remitente_id)    REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_dest FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_msg_par (remitente_id, destinatario_id)
) ENGINE=InnoDB;
