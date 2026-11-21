-- Ejecutar UNA sola vez en phpMyAdmin (pestaña SQL).
USE aterriza;

-- Las publicaciones ahora pueden ser texto (sin foto) y tener un tipo
ALTER TABLE publicaciones
  MODIFY ruta_imagen VARCHAR(255) NULL,
  MODIFY descripcion VARCHAR(500) NULL,
  ADD COLUMN tipo ENUM('foto','ayuda','plan','recomendacion','otro') NOT NULL DEFAULT 'foto' AFTER usuario_id;

-- Seguidores / seguidos
CREATE TABLE IF NOT EXISTS seguidores (
  seguidor_id INT UNSIGNED NOT NULL,
  seguido_id  INT UNSIGNED NOT NULL,
  fecha       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (seguidor_id, seguido_id),
  CONSTRAINT fk_seg_seguidor FOREIGN KEY (seguidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_seg_seguido  FOREIGN KEY (seguido_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT ck_seg_distinto CHECK (seguidor_id <> seguido_id)
) ENGINE=InnoDB;

-- Lugares que ya visitaste y te gustaron
CREATE TABLE IF NOT EXISTS lugares_favoritos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NOT NULL,
  nombre      VARCHAR(120) NOT NULL,
  zona        VARCHAR(120) NULL,
  nota        VARCHAR(255) NULL,
  fecha       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fav_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
