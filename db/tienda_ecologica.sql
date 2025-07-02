-- =====================================================
-- BASE DE DATOS: TIENDA ECOLÓGICA ECOVERDE
-- =====================================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS tienda_ecologica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tienda_ecologica;

-- =====================================================
-- TABLA: CATEGORÍAS
-- =====================================================
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: PRODUCTOS
-- =====================================================
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    precio_oferta DECIMAL(10,2),
    stock INT DEFAULT 0,
    imagen VARCHAR(255),
    imagenes_adicionales TEXT, -- JSON para múltiples imágenes
    caracteristicas TEXT, -- JSON para características del producto
    estado ENUM('activo', 'inactivo', 'agotado') DEFAULT 'activo',
    destacado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA: USUARIOS
-- =====================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    ciudad VARCHAR(100),
    codigo_postal VARCHAR(10),
    pais VARCHAR(100) DEFAULT 'Perú',
    tipo_usuario ENUM('cliente', 'admin') DEFAULT 'cliente',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: CARRITO DE COMPRAS
-- =====================================================
CREATE TABLE carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    producto_id INT,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: PEDIDOS
-- =====================================================
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_pedido VARCHAR(20) UNIQUE NOT NULL,
    usuario_id INT,
    total DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) DEFAULT 0,
    descuento DECIMAL(10,2) DEFAULT 0,
    estado ENUM('pendiente', 'confirmado', 'en_proceso', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'paypal') DEFAULT 'efectivo',
    direccion_envio TEXT,
    telefono_envio VARCHAR(20),
    notas TEXT,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA: DETALLES DE PEDIDO
-- =====================================================
CREATE TABLE detalles_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    producto_id INT,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA: RESEÑAS DE PRODUCTOS
-- =====================================================
CREATE TABLE reseñas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT,
    usuario_id INT,
    calificacion INT CHECK (calificacion >= 1 AND calificacion <= 5),
    comentario TEXT,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: NEWSLETTER
-- =====================================================
CREATE TABLE newsletter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) UNIQUE NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_suscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: CONFIGURACIÓN DEL SITIO
-- =====================================================
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- INSERTAR DATOS DE PRUEBA
-- =====================================================

-- Insertar categorías
INSERT INTO categorias (nombre, descripcion, imagen) VALUES
('Hogar', 'Productos ecológicos para mantener tu hogar limpio y sostenible', 'para el Hogar.png'),
('Cuidado Personal', 'Productos naturales para tu bienestar diario', 'Cuidado personal.png'),
('Oficina', 'Soluciones ecológicas para tu espacio de trabajo', 'oficina-ecologica.jpg'),
('Alimentos', 'Productos orgánicos y naturales para una alimentación saludable', 'Frutos secos.jpg');

-- Insertar productos
INSERT INTO productos (categoria_id, nombre, descripcion, precio, precio_oferta, stock, imagen, caracteristicas, destacado) VALUES
(1, 'Cepillo de Dientes de Bambú', 'Cepillo de dientes 100% biodegradable hecho de bambú sostenible. Ideal para reducir el plástico en tu rutina diaria.', 17.50, 14.00, 50, 'cepillo_bambu.png', '{"material": "Bambú", "biodegradable": true, "duracion": "3 meses"}', TRUE),
(1, 'Bolso de Algodón Orgánico', 'Bolso reutilizable hecho de algodón 100% orgánico. Perfecto para hacer compras de manera sostenible.', 53.00, 45.00, 30, 'bolso de algodón.png', '{"material": "Algodón orgánico", "capacidad": "20L", "lavable": true}', TRUE),
(1, 'Lámpara de Madera Natural', 'Lámpara decorativa hecha de madera natural certificada. Iluminación cálida y acogedora.', 46.70, 35.00, 15, 'lampara-natural.jpg', '{"material": "Madera natural", "tipo_bombilla": "LED", "potencia": "9W"}', TRUE),
(2, 'Shampoo Orgánico', 'Shampoo natural sin químicos nocivos. Cuida tu cabello y el medio ambiente.', 25.00, NULL, 40, 'Shampoo organico.png', '{"tipo": "Orgánico", "sin_sulfatos": true, "volumen": "250ml"}', FALSE),
(2, 'Jabón de Avena', 'Jabón artesanal de avena para piel sensible. Hidratante y suave.', 18.00, NULL, 35, 'Jabon-avena.png', '{"tipo": "Artesanal", "ingredientes": "Avena, aceites naturales", "peso": "100g"}', FALSE),
(1, 'Detergente Ecológico', 'Detergente biodegradable para ropa. Efectivo y respetuoso con el medio ambiente.', 32.00, NULL, 25, 'Detergente-Ecologico.png', '{"tipo": "Biodegradable", "concentrado": true, "lavados": "30"}', FALSE),
(3, 'Papel Higiénico Reciclado', 'Papel higiénico 100% reciclado. Suave y resistente.', 12.50, NULL, 60, 'papel_higienico.jpg', '{"tipo": "Reciclado", "hojas": "200", "capas": "2"}', FALSE),
(1, 'Esponja Natural', 'Esponja de luffa natural para la limpieza del hogar. Biodegradable y duradera.', 8.00, NULL, 45, 'esponja_natural.png', '{"material": "Luffa natural", "biodegradable": true, "duracion": "2-3 meses"}', FALSE);

-- Insertar usuario administrador
INSERT INTO usuarios (nombre, apellidos, email, password, telefono, tipo_usuario) VALUES
('Admin', 'EcoVerde', 'admin@ecoverde.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+51 936 623 658', 'admin');

-- Insertar configuración del sitio
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('nombre_tienda', 'EcoVerde', 'Nombre de la tienda'),
('descripcion_tienda', 'Tu tienda de productos ecológicos y sostenibles', 'Descripción de la tienda'),
('email_contacto', 'info@ecoverde.com', 'Email de contacto'),
('telefono_contacto', '+51 936 623 658', 'Teléfono de contacto'),
('direccion_tienda', 'Lima, Perú', 'Dirección de la tienda'),
('moneda', 'S/.', 'Símbolo de la moneda'),
('impuestos', '18', 'Porcentaje de impuestos'),
('envio_gratis_desde', '100', 'Monto mínimo para envío gratis'),
('costo_envio', '15', 'Costo de envío estándar');

-- =====================================================
-- CREAR ÍNDICES PARA OPTIMIZAR RENDIMIENTO
-- =====================================================

-- Índices para productos
CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_productos_estado ON productos(estado);
CREATE INDEX idx_productos_destacado ON productos(destacado);
CREATE INDEX idx_productos_precio ON productos(precio);

-- Índices para usuarios
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo_usuario);

-- Índices para pedidos
CREATE INDEX idx_pedidos_usuario ON pedidos(usuario_id);
CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_pedidos_fecha ON pedidos(fecha_pedido);

-- Índices para carrito
CREATE INDEX idx_carrito_usuario ON carrito(usuario_id);
CREATE INDEX idx_carrito_producto ON carrito(producto_id);

-- Índices para reseñas
CREATE INDEX idx_reseñas_producto ON reseñas(producto_id);
CREATE INDEX idx_reseñas_usuario ON reseñas(usuario_id);

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista para productos con información de categoría
CREATE VIEW vista_productos AS
SELECT 
    p.*,
    c.nombre as categoria_nombre,
    c.descripcion as categoria_descripcion
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE p.estado = 'activo';

-- Vista para productos destacados
CREATE VIEW vista_productos_destacados AS
SELECT 
    p.*,
    c.nombre as categoria_nombre
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE p.destacado = TRUE AND p.estado = 'activo'
ORDER BY p.created_at DESC;

-- Vista para estadísticas de productos
CREATE VIEW vista_estadisticas_productos AS
SELECT 
    c.nombre as categoria,
    COUNT(p.id) as total_productos,
    AVG(p.precio) as precio_promedio,
    SUM(p.stock) as stock_total
FROM categorias c
LEFT JOIN productos p ON c.id = p.categoria_id
WHERE c.estado = 'activo'
GROUP BY c.id, c.nombre;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

-- Procedimiento para obtener productos por categoría
DELIMITER //
CREATE PROCEDURE ObtenerProductosPorCategoria(IN categoria_id INT)
BEGIN
    SELECT * FROM productos 
    WHERE categoria_id = categoria_id AND estado = 'activo'
    ORDER BY destacado DESC, nombre ASC;
END //
DELIMITER ;

-- Procedimiento para calcular total del carrito
DELIMITER //
CREATE PROCEDURE CalcularTotalCarrito(IN usuario_id INT)
BEGIN
    SELECT 
        SUM(c.cantidad * c.precio_unitario) as total,
        COUNT(c.id) as items
    FROM carrito c
    WHERE c.usuario_id = usuario_id;
END //
DELIMITER ;

-- Procedimiento para crear un nuevo pedido
DELIMITER //
CREATE PROCEDURE CrearPedido(
    IN p_usuario_id INT,
    IN p_total DECIMAL(10,2),
    IN p_direccion TEXT,
    IN p_telefono VARCHAR(20),
    OUT p_pedido_id INT
)
BEGIN
    DECLARE numero_pedido VARCHAR(20);
    
    -- Generar número de pedido único
    SET numero_pedido = CONCAT('ECO', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Insertar pedido
    INSERT INTO pedidos (numero_pedido, usuario_id, total, subtotal, direccion_envio, telefono_envio)
    VALUES (numero_pedido, p_usuario_id, p_total, p_total, p_direccion, p_telefono);
    
    SET p_pedido_id = LAST_INSERT_ID();
END //
DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger para actualizar stock al crear pedido
DELIMITER //
CREATE TRIGGER actualizar_stock_pedido
AFTER INSERT ON detalles_pedido
FOR EACH ROW
BEGIN
    UPDATE productos 
    SET stock = stock - NEW.cantidad
    WHERE id = NEW.producto_id;
END //
DELIMITER ;

-- Trigger para actualizar fecha de actualización
DELIMITER //
CREATE TRIGGER actualizar_timestamp_productos
BEFORE UPDATE ON productos
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- =====================================================
-- PERMISOS Y SEGURIDAD
-- =====================================================

-- Crear usuario específico para la aplicación (opcional)
-- CREATE USER 'ecoverde_user'@'localhost' IDENTIFIED BY 'tu_password_seguro';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON tienda_ecologica.* TO 'ecoverde_user'@'localhost';
-- FLUSH PRIVILEGES;

-- =====================================================
-- FINALIZACIÓN
-- =====================================================

-- Mostrar mensaje de éxito
SELECT 'Base de datos tienda_ecologica creada exitosamente!' as mensaje;
SELECT COUNT(*) as total_productos FROM productos;
SELECT COUNT(*) as total_categorias FROM categorias; 