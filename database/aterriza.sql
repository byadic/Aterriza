DROP DATABASE IF EXISTS aterriza;
CREATE DATABASE aterriza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aterriza;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    rol ENUM('foraneo', 'guia') NOT NULL,
    carrera VARCHAR(100) NOT NULL,
    ciudad_origen VARCHAR(100) NOT NULL,
    ciudad_destino VARCHAR(100) NOT NULL,
    intereses TEXT NOT NULL,
    whatsapp VARCHAR(20) NOT NULL
);

CREATE TABLE conexiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitante_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptada') NOT NULL DEFAULT 'pendiente',
    FOREIGN KEY (solicitante_id) REFERENCES usuarios(id),
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id)
);

CREATE TABLE cuartos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    zona VARCHAR(100) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    descripcion TEXT NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE avisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guia_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    contenido TEXT NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guia_id) REFERENCES usuarios(id)
);

INSERT INTO usuarios (nombre, correo, `password`, rol, carrera, ciudad_origen, ciudad_destino, intereses, whatsapp) VALUES
('Ana López', 'ana@example.com', '$2y$10$G.xpNf0F6p.8t2nih44VIet4aWyLA8PRFcfpkguqCAaFG5mX5/wCy', 'foraneo', 'Ingeniería', 'Puebla', 'Ciudad de México', 'Música, lectura', '5551234567'),
('Luis Pérez', 'luis@example.com', '$2y$10$G.xpNf0F6p.8t2nih44VIet4aWyLA8PRFcfpkguqCAaFG5mX5/wCy', 'foraneo', 'Diseño', 'Oaxaca', 'Ciudad de México', 'Cine, fotografía', '5552345678'),
('María García', 'maria@example.com', '$2y$10$G.xpNf0F6p.8t2nih44VIet4aWyLA8PRFcfpkguqCAaFG5mX5/wCy', 'guia', 'Psicología', 'Ciudad de México', 'Ciudad de México', 'Caminatas, café', '5553456789'),
('Carlos Ruiz', 'carlos@example.com', '$2y$10$G.xpNf0F6p.8t2nih44VIet4aWyLA8PRFcfpkguqCAaFG5mX5/wCy', 'guia', 'Arquitectura', 'Ciudad de México', 'Ciudad de México', 'Arquitectura, deportes', '5554567890');

INSERT INTO cuartos (usuario_id, titulo, zona, precio, descripcion, whatsapp) VALUES
(1, 'Cuarto cerca de la universidad', 'Centro', 4500.00, 'Amueblado y con servicios incluidos.', '5551234567'),
(3, 'Habitación compartida', 'Sur', 3200.00, 'Habitación iluminada con acceso a cocina.', '5553456789');

INSERT INTO avisos (guia_id, titulo, categoria, contenido, whatsapp) VALUES
(3, 'Ruta segura al campus', 'Movilidad', 'Se recomienda usar transporte público establecido y evitar calles poco transitadas por la noche.', '5553456789'),
(4, 'Convocatoria de orientación', 'Universidad', 'Los nuevos estudiantes pueden consultar horarios y trámites en la próxima sesión grupal.', '5554567890');
