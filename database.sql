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
    tamanio ENUM('pequeño','mediano','grande','gigante') DEFAULT 'mediano',
    sexo ENUM('macho','hembra') NOT NULL,
    peso DECIMAL(5,2),
    color VARCHAR(80),
    estado_salud ENUM('excelente','bueno','regular','critico') NOT NULL DEFAULT 'bueno',
    vacunacion_estado ENUM('completa','parcial','pendiente','desconocida') DEFAULT 'pendiente',
    esterilizacion_estado ENUM('esterilizado','no_esterilizado','pendiente','desconocido') DEFAULT 'pendiente',
    fecha_rescate DATE NOT NULL,
    fecha_ingreso DATE DEFAULT NULL,
    lugar_rescate VARCHAR(200),
    estado ENUM('disponible','adoptado','en_tratamiento','reservado') DEFAULT 'disponible',
    foto VARCHAR(255) DEFAULT NULL,
    personalidad TEXT,
    historia_rescate TEXT,
    observaciones TEXT,
    observaciones_adicionales TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE animal_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    tipo ENUM('principal','galeria') DEFAULT 'galeria',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animales(id)
);

-- ---------------------------------------------
-- TABLA: adoptantes
-- ---------------------------------------------
CREATE TABLE adoptantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    fecha_nacimiento DATE DEFAULT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion VARCHAR(200),
    ciudad VARCHAR(100),
    ocupacion VARCHAR(100),
    profesion VARCHAR(120),
    referencia_personal VARCHAR(200),
    motivo_adopcion TEXT,
    tipo_vivienda ENUM('casa','departamento','habitacion','quinta','otro') DEFAULT 'casa',
    tiene_patio TINYINT(1) DEFAULT 0,
    posee_otras_mascotas TINYINT(1) DEFAULT 0,
    cantidad_personas_hogar TINYINT UNSIGNED DEFAULT 1,
    observaciones TEXT,
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
    fecha_adopcion DATE DEFAULT NULL,
    fecha_entrega DATE DEFAULT NULL,
    estado ENUM('pendiente','aprobada','rechazada','entregada') DEFAULT 'pendiente',
    seguimiento_estado ENUM('pendiente','programado','en_seguimiento','completado','alerta') DEFAULT 'pendiente',
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
    tipo ENUM('dinero','alimento','medicina','accesorios','monetaria','especie') NOT NULL DEFAULT 'dinero',
    monto DECIMAL(10,2) DEFAULT 0,
    cantidad DECIMAL(10,2) DEFAULT 0,
    unidad VARCHAR(40),
    descripcion VARCHAR(255),
    observaciones TEXT,
    metodo_pago ENUM('efectivo','transferencia','tarjeta','otro') DEFAULT 'efectivo',
    responsable_id INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donante_id) REFERENCES donantes(id),
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id)
);

CREATE TABLE gastos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria ENUM('alimento','medicamentos','veterinario','limpieza','transporte','servicios','otros') NOT NULL,
    fecha DATE NOT NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 0,
    descripcion VARCHAR(255) NOT NULL,
    proveedor VARCHAR(150),
    comprobante VARCHAR(100),
    responsable_id INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id)
);

CREATE TABLE inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    categoria ENUM('alimentos','medicamentos','accesorios','limpieza') NOT NULL,
    unidad VARCHAR(40) NOT NULL DEFAULT 'unidad',
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_vencimiento DATE DEFAULT NULL,
    ubicacion VARCHAR(120),
    observaciones TEXT,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE seguimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adopcion_id INT DEFAULT NULL,
    animal_id INT NOT NULL,
    fecha DATE NOT NULL,
    estado_mascota VARCHAR(120) NOT NULL,
    comentarios TEXT,
    fotos TEXT,
    responsable VARCHAR(150),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animales(id)
);

CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    accion VARCHAR(80) NOT NULL,
    tabla_afectada VARCHAR(80) NOT NULL,
    registro_id INT DEFAULT NULL,
    detalle TEXT,
    ip VARCHAR(45),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
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

-- Usuario administrador (password: password)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@refugio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador'),
('Dr. García', 'vet@refugio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'veterinario');

-- Animales de prueba
INSERT INTO animales (nombre, especie, raza, edad_años, edad_meses, tamanio, sexo, peso, color, estado_salud, vacunacion_estado, esterilizacion_estado, fecha_rescate, fecha_ingreso, lugar_rescate, estado, personalidad, historia_rescate, observaciones) VALUES
('Max', 'perro', 'Labrador', 2, 3, 'grande', 'macho', 18.5, 'Amarillo', 'bueno', 'parcial', 'pendiente', '2025-01-10', '2025-01-10', 'Av. Principal 123', 'disponible', 'Muy amigable con niños', 'Rescatado de la calle principal.', 'Muy amigable con niños'),
('Luna', 'gato', 'Siamés', 1, 0, 'pequeño', 'hembra', 3.2, 'Blanco/Marrón', 'excelente', 'completa', 'esterilizado', '2025-02-14', '2025-02-14', 'Parque Central', 'disponible', 'Tranquila e independiente', 'Abandonada cerca del parque.', 'Esterilizada y vacunada'),
('Rocky', 'perro', 'Mestizo', 4, 6, 'grande', 'macho', 22.0, 'Negro', 'regular', 'parcial', 'pendiente', '2024-11-20', '2024-11-20', 'Zona Industrial', 'en_tratamiento', 'Protector y dócil', 'Atendido tras accidente.', 'En recuperación por fractura'),
('Mia', 'gato', 'Persa', 3, 2, 'pequeño', 'hembra', 4.5, 'Gris', 'bueno', 'completa', 'esterilizado', '2025-03-05', '2025-03-05', 'Barrio Norte', 'disponible', 'Tranquila, ideal para apartamento', 'Rescatada por vecinos.', 'Tranquila, ideal para apartamento'),
('Toby', 'perro', 'Beagle', 0, 8, 'mediano', 'macho', 6.0, 'Tricolor', 'excelente', 'pendiente', 'pendiente', '2025-04-01', '2025-04-01', 'Mercado Central', 'disponible', 'Cachorro juguetón', 'Encontrado cerca del mercado.', 'Cachorro juguetón');

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

INSERT INTO gastos (categoria, fecha, monto, descripcion, proveedor, comprobante, responsable_id) VALUES
('alimento', '2025-05-05', 320.00, 'Compra de croquetas para perros', 'Proveedor Patitas', 'FAC-001', 1),
('veterinario', '2025-05-18', 180.00, 'Consulta y medicación para Rocky', 'Clínica Animal', 'REC-022', 2);

INSERT INTO inventario (nombre, categoria, unidad, cantidad, stock_minimo, fecha_vencimiento, ubicacion, observaciones) VALUES
('Croquetas perro adulto', 'alimentos', 'kg', 45, 20, NULL, 'Depósito A', 'Saco balanceado'),
('Vacuna múltiple', 'medicamentos', 'unidad', 6, 8, '2025-12-31', 'Refrigerador', 'Requiere frío'),
('Shampoo antipulgas', 'limpieza', 'unidad', 12, 5, NULL, 'Estante limpieza', 'Uso semanal');

-- Voluntarios de prueba
INSERT INTO voluntarios (nombre_completo, dni, telefono, email, direccion, fecha_ingreso, disponibilidad, habilidades, estado) VALUES
('Pedro González', '11111111', '555-3333', 'pedro@email.com', 'Calle 3 #30', '2024-06-01', 'Fines de semana', 'Cuidado animal, limpieza', 'activo'),
('Sofía Torres', '22222222', '555-4444', 'sofia@email.com', 'Av. 4 #40', '2024-08-15', 'Lunes y miércoles', 'Veterinaria estudiante, primeros auxilios', 'activo'),
('Luis Herrera', '33333333', '555-5555', 'luis@email.com', 'Barrio Sur', '2025-01-10', 'Diario', 'Transporte, construcción', 'activo');

-- Adopción de prueba
INSERT INTO adopciones (animal_id, adoptante_id, fecha_solicitud, estado, observaciones, responsable_id) VALUES
(2, 1, '2025-05-20', 'aprobada', 'Familia evaluada positivamente', 1);
