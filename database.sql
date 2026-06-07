-- =============================================
-- SISTEMA DE REFUGIO DE ANIMALES
-- Base de Datos MySQL
-- =============================================

CREATE DATABASE IF NOT EXISTS refugio_animales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE refugio_animales;

-- ---------------------------------------------
-- TABLA: usuarios
-- ---------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador','veterinario','adopciones','donaciones','voluntarios','consulta') NOT NULL DEFAULT 'consulta',
    estado TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- TABLA: animales
-- ---------------------------------------------
CREATE TABLE animales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    especie ENUM('perro','gato','ave','conejo','otro') NOT NULL,
    raza VARCHAR(100),
    edad_años TINYINT UNSIGNED DEFAULT 0,
    edad_meses TINYINT UNSIGNED DEFAULT 0,
    sexo ENUM('macho','hembra') NOT NULL,
    peso DECIMAL(5,2),
    color VARCHAR(80),
    estado_salud ENUM('excelente','bueno','regular','critico') NOT NULL DEFAULT 'bueno',
    fecha_rescate DATE NOT NULL,
    lugar_rescate VARCHAR(200),
    estado ENUM('disponible','adoptado','en_tratamiento','reservado') DEFAULT 'disponible',
    foto VARCHAR(255) DEFAULT NULL,
    observaciones TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- TABLA: adoptantes
-- ---------------------------------------------
CREATE TABLE adoptantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion VARCHAR(200),
    ocupacion VARCHAR(100),
    estado_civil ENUM('soltero','casado','divorciado','viudo','union_libre') DEFAULT 'soltero',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- TABLA: adopciones
-- ---------------------------------------------
CREATE TABLE adopciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    adoptante_id INT NOT NULL,
    fecha_solicitud DATE NOT NULL,
    fecha_entrega DATE DEFAULT NULL,
    estado ENUM('pendiente','aprobada','rechazada','entregada') DEFAULT 'pendiente',
    observaciones TEXT,
    responsable_id INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animales(id),
    FOREIGN KEY (adoptante_id) REFERENCES adoptantes(id),
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id)
);

-- ---------------------------------------------
-- TABLA: donantes
-- ---------------------------------------------
CREATE TABLE donantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion VARCHAR(200),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- TABLA: donaciones
-- ---------------------------------------------
CREATE TABLE donaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donante_id INT NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('monetaria','especie') NOT NULL DEFAULT 'monetaria',
    monto DECIMAL(10,2) DEFAULT 0,
    descripcion VARCHAR(255),
    metodo_pago ENUM('efectivo','transferencia','tarjeta','otro') DEFAULT 'efectivo',
    responsable_id INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donante_id) REFERENCES donantes(id),
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id)
);

-- ---------------------------------------------
-- TABLA: voluntarios
-- ---------------------------------------------
CREATE TABLE voluntarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion VARCHAR(200),
    fecha_ingreso DATE NOT NULL,
    disponibilidad VARCHAR(200),
    habilidades TEXT,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- DATOS DE PRUEBA
-- ---------------------------------------------

-- Usuario administrador (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@refugio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador'),
('Dr. García', 'vet@refugio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'veterinario');

-- Animales de prueba
INSERT INTO animales (nombre, especie, raza, edad_años, edad_meses, sexo, peso, color, estado_salud, fecha_rescate, lugar_rescate, estado, observaciones) VALUES
('Max', 'perro', 'Labrador', 2, 3, 'macho', 18.5, 'Amarillo', 'bueno', '2025-01-10', 'Av. Principal 123', 'disponible', 'Muy amigable con niños'),
('Luna', 'gato', 'Siamés', 1, 0, 'hembra', 3.2, 'Blanco/Marrón', 'excelente', '2025-02-14', 'Parque Central', 'disponible', 'Esterilizada y vacunada'),
('Rocky', 'perro', 'Mestizo', 4, 6, 'macho', 22.0, 'Negro', 'regular', '2024-11-20', 'Zona Industrial', 'en_tratamiento', 'En recuperación por fractura'),
('Mia', 'gato', 'Persa', 3, 2, 'hembra', 4.5, 'Gris', 'bueno', '2025-03-05', 'Barrio Norte', 'disponible', 'Tranquila, ideal para apartamento'),
('Toby', 'perro', 'Beagle', 0, 8, 'macho', 6.0, 'Tricolor', 'excelente', '2025-04-01', 'Mercado Central', 'disponible', 'Cachorro juguetón');

-- Adoptantes de prueba
INSERT INTO adoptantes (nombre_completo, dni, telefono, email, direccion, ocupacion, estado_civil) VALUES
('María López', '12345678', '555-1234', 'maria@email.com', 'Calle 1 #100', 'Profesora', 'casado'),
('Carlos Ruiz', '87654321', '555-5678', 'carlos@email.com', 'Av. 2 #200', 'Ingeniero', 'soltero');

-- Donantes de prueba
INSERT INTO donantes (nombre, telefono, email, direccion) VALUES
('Empresa ABC', '555-9999', 'empresa@abc.com', 'Zona Empresarial'),
('Ana Martínez', '555-1111', 'ana@email.com', 'Calle 5 #50'),
('Roberto Sánchez', '555-2222', 'roberto@email.com', 'Av. 8 #80');

-- Donaciones de prueba
INSERT INTO donaciones (donante_id, fecha, tipo, monto, descripcion, metodo_pago, responsable_id) VALUES
(1, '2025-05-01', 'monetaria', 500.00, 'Donación mensual', 'transferencia', 1),
(2, '2025-05-10', 'especie', 0, 'Bolsas de alimento para perros (20kg)', 'efectivo', 1),
(3, '2025-05-15', 'monetaria', 150.00, 'Donación voluntaria', 'efectivo', 1);

-- Voluntarios de prueba
INSERT INTO voluntarios (nombre_completo, dni, telefono, email, direccion, fecha_ingreso, disponibilidad, habilidades, estado) VALUES
('Pedro González', '11111111', '555-3333', 'pedro@email.com', 'Calle 3 #30', '2024-06-01', 'Fines de semana', 'Cuidado animal, limpieza', 'activo'),
('Sofía Torres', '22222222', '555-4444', 'sofia@email.com', 'Av. 4 #40', '2024-08-15', 'Lunes y miércoles', 'Veterinaria estudiante, primeros auxilios', 'activo'),
('Luis Herrera', '33333333', '555-5555', 'luis@email.com', 'Barrio Sur', '2025-01-10', 'Diario', 'Transporte, construcción', 'activo');

-- Adopción de prueba
INSERT INTO adopciones (animal_id, adoptante_id, fecha_solicitud, estado, observaciones, responsable_id) VALUES
(2, 1, '2025-05-20', 'aprobada', 'Familia evaluada positivamente', 1);
