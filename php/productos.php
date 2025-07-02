<?php
/**
 * API de Productos - Tienda Ecológica EcoVerde
 * 
 * Este archivo maneja todas las operaciones relacionadas con productos
 * incluyendo listado, búsqueda, filtrado y gestión de productos.
 */

require_once 'config.php';

// Obtener método de la petición
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        case 'PUT':
            handlePutRequest($action);
            break;
        case 'DELETE':
            handleDeleteRequest($action);
            break;
        default:
            apiError('Método no permitido', 405);
    }
    
} catch (Exception $e) {
    error_log("Error en productos.php: " . $e->getMessage());
    apiError('Error interno del servidor', 500);
}

/**
 * Manejar peticiones GET
 */
function handleGetRequest($action) {
    global $db;
    
    switch ($action) {
        case 'listar':
            getProductos();
            break;
        case 'destacados':
            getProductosDestacados();
            break;
        case 'categoria':
            getProductosPorCategoria();
            break;
        case 'buscar':
            buscarProductos();
            break;
        case 'detalle':
            getProductoDetalle();
            break;
        case 'categorias':
            getCategorias();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Obtener lista de productos con paginación
 */
function getProductos() {
    global $db;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? ITEMS_PER_PAGE);
    $categoria_id = (int)($_GET['categoria_id'] ?? 0);
    $orden = $_GET['orden'] ?? 'nombre';
    $direccion = $_GET['direccion'] ?? 'ASC';
    
    $offset = ($page - 1) * $limit;
    
    // Construir consulta base
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.estado = 'activo'";
    
    $params = [];
    
    // Filtrar por categoría si se especifica
    if ($categoria_id > 0) {
        $sql .= " AND p.categoria_id = ?";
        $params[] = $categoria_id;
    }
    
    // Ordenar
    $ordenes_validos = ['nombre', 'precio', 'created_at', 'destacado'];
    $direcciones_validas = ['ASC', 'DESC'];
    
    if (in_array($orden, $ordenes_validos) && in_array(strtoupper($direccion), $direcciones_validas)) {
        $sql .= " ORDER BY p.$orden $direccion";
    } else {
        $sql .= " ORDER BY p.destacado DESC, p.nombre ASC";
    }
    
    // Paginación
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Obtener productos
    $productos = $db->fetchAll($sql, $params);
    
    // Contar total de productos para paginación
    $countSql = "SELECT COUNT(*) as total FROM productos p WHERE p.estado = 'activo'";
    if ($categoria_id > 0) {
        $countSql .= " AND p.categoria_id = ?";
        $countResult = $db->fetchOne($countSql, [$categoria_id]);
    } else {
        $countResult = $db->fetchOne($countSql);
    }
    
    $total = $countResult['total'];
    $totalPages = ceil($total / $limit);
    
    // Procesar productos
    foreach ($productos as &$producto) {
        $producto['precio_formateado'] = formatPrice($producto['precio']);
        if ($producto['precio_oferta']) {
            $producto['precio_oferta_formateado'] = formatPrice($producto['precio_oferta']);
            $producto['descuento_porcentaje'] = calculateDiscount($producto['precio'], $producto['precio_oferta']);
        }
        
        // Decodificar características JSON
        if ($producto['caracteristicas']) {
            $producto['caracteristicas'] = json_decode($producto['caracteristicas'], true);
        }
        
        // Obtener calificación promedio
        $ratingSql = "SELECT AVG(calificacion) as promedio, COUNT(*) as total 
                      FROM reseñas 
                      WHERE producto_id = ? AND estado = 'aprobado'";
        $rating = $db->fetchOne($ratingSql, [$producto['id']]);
        $producto['rating'] = [
            'promedio' => round($rating['promedio'], 1) ?: 0,
            'total' => $rating['total']
        ];
    }
    
    apiSuccess([
        'productos' => $productos,
        'paginacion' => [
            'pagina_actual' => $page,
            'total_paginas' => $totalPages,
            'total_productos' => $total,
            'productos_por_pagina' => $limit
        ]
    ]);
}

/**
 * Obtener productos destacados
 */
function getProductosDestacados() {
    global $db;
    
    $limit = (int)($_GET['limit'] ?? 6);
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.destacado = TRUE AND p.estado = 'activo'
            ORDER BY p.created_at DESC 
            LIMIT ?";
    
    $productos = $db->fetchAll($sql, [$limit]);
    
    // Procesar productos
    foreach ($productos as &$producto) {
        $producto['precio_formateado'] = formatPrice($producto['precio']);
        if ($producto['precio_oferta']) {
            $producto['precio_oferta_formateado'] = formatPrice($producto['precio_oferta']);
            $producto['descuento_porcentaje'] = calculateDiscount($producto['precio'], $producto['precio_oferta']);
        }
    }
    
    apiSuccess($productos);
}

/**
 * Obtener productos por categoría
 */
function getProductosPorCategoria() {
    global $db;
    
    $categoria_id = (int)($_GET['categoria_id'] ?? 0);
    $limit = (int)($_GET['limit'] ?? 12);
    
    if ($categoria_id <= 0) {
        apiError('ID de categoría requerido', 400);
    }
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.categoria_id = ? AND p.estado = 'activo'
            ORDER BY p.destacado DESC, p.nombre ASC 
            LIMIT ?";
    
    $productos = $db->fetchAll($sql, [$categoria_id, $limit]);
    
    // Procesar productos
    foreach ($productos as &$producto) {
        $producto['precio_formateado'] = formatPrice($producto['precio']);
        if ($producto['precio_oferta']) {
            $producto['precio_oferta_formateado'] = formatPrice($producto['precio_oferta']);
            $producto['descuento_porcentaje'] = calculateDiscount($producto['precio'], $producto['precio_oferta']);
        }
    }
    
    apiSuccess($productos);
}

/**
 * Buscar productos
 */
function buscarProductos() {
    global $db;
    
    $busqueda = trim($_GET['q'] ?? '');
    $limit = (int)($_GET['limit'] ?? 20);
    
    if (empty($busqueda)) {
        apiError('Término de búsqueda requerido', 400);
    }
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.estado = 'activo' 
            AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR c.nombre LIKE ?)
            ORDER BY p.destacado DESC, p.nombre ASC 
            LIMIT ?";
    
    $searchTerm = "%$busqueda%";
    $productos = $db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $limit]);
    
    // Procesar productos
    foreach ($productos as &$producto) {
        $producto['precio_formateado'] = formatPrice($producto['precio']);
        if ($producto['precio_oferta']) {
            $producto['precio_oferta_formateado'] = formatPrice($producto['precio_oferta']);
            $producto['descuento_porcentaje'] = calculateDiscount($producto['precio'], $producto['precio_oferta']);
        }
    }
    
    apiSuccess([
        'productos' => $productos,
        'termino_busqueda' => $busqueda,
        'total_resultados' => count($productos)
    ]);
}

/**
 * Obtener detalle de un producto
 */
function getProductoDetalle() {
    global $db;
    
    $producto_id = (int)($_GET['id'] ?? 0);
    
    if ($producto_id <= 0) {
        apiError('ID de producto requerido', 400);
    }
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre, c.descripcion as categoria_descripcion
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.id = ? AND p.estado = 'activo'";
    
    $producto = $db->fetchOne($sql, [$producto_id]);
    
    if (!$producto) {
        apiError('Producto no encontrado', 404);
    }
    
    // Procesar producto
    $producto['precio_formateado'] = formatPrice($producto['precio']);
    if ($producto['precio_oferta']) {
        $producto['precio_oferta_formateado'] = formatPrice($producto['precio_oferta']);
        $producto['descuento_porcentaje'] = calculateDiscount($producto['precio'], $producto['precio_oferta']);
    }
    
    // Decodificar características JSON
    if ($producto['caracteristicas']) {
        $producto['caracteristicas'] = json_decode($producto['caracteristicas'], true);
    }
    
    // Obtener calificación promedio y reseñas
    $ratingSql = "SELECT AVG(calificacion) as promedio, COUNT(*) as total 
                  FROM reseñas 
                  WHERE producto_id = ? AND estado = 'aprobado'";
    $rating = $db->fetchOne($ratingSql, [$producto_id]);
    $producto['rating'] = [
        'promedio' => round($rating['promedio'], 1) ?: 0,
        'total' => $rating['total']
    ];
    
    // Obtener reseñas recientes
    $reseñasSql = "SELECT r.*, u.nombre, u.apellidos 
                   FROM reseñas r 
                   LEFT JOIN usuarios u ON r.usuario_id = u.id 
                   WHERE r.producto_id = ? AND r.estado = 'aprobado' 
                   ORDER BY r.created_at DESC 
                   LIMIT 5";
    $reseñas = $db->fetchAll($reseñasSql, [$producto_id]);
    
    // Ocultar información sensible de usuarios
    foreach ($reseñas as &$reseña) {
        $reseña['usuario_nombre'] = $reseña['nombre'] . ' ' . substr($reseña['apellidos'], 0, 1) . '.';
        unset($reseña['nombre'], $reseña['apellidos'], $reseña['usuario_id']);
    }
    
    $producto['reseñas'] = $reseñas;
    
    // Obtener productos relacionados
    $relacionadosSql = "SELECT p.*, c.nombre as categoria_nombre 
                       FROM productos p 
                       LEFT JOIN categorias c ON p.categoria_id = c.id 
                       WHERE p.categoria_id = ? AND p.id != ? AND p.estado = 'activo' 
                       ORDER BY p.destacado DESC, RAND() 
                       LIMIT 4";
    $relacionados = $db->fetchAll($relacionadosSql, [$producto['categoria_id'], $producto_id]);
    
    // Procesar productos relacionados
    foreach ($relacionados as &$relacionado) {
        $relacionado['precio_formateado'] = formatPrice($relacionado['precio']);
        if ($relacionado['precio_oferta']) {
            $relacionado['precio_oferta_formateado'] = formatPrice($relacionado['precio_oferta']);
        }
    }
    
    $producto['productos_relacionados'] = $relacionados;
    
    apiSuccess($producto);
}

/**
 * Obtener categorías
 */
function getCategorias() {
    global $db;
    
    $sql = "SELECT c.*, COUNT(p.id) as total_productos 
            FROM categorias c 
            LEFT JOIN productos p ON c.id = p.categoria_id AND p.estado = 'activo' 
            WHERE c.estado = 'activo' 
            GROUP BY c.id 
            ORDER BY c.nombre ASC";
    
    $categorias = $db->fetchAll($sql);
    
    apiSuccess($categorias);
}

/**
 * Manejar peticiones POST
 */
function handlePostRequest($action) {
    requireAdmin(); // Solo administradores pueden crear productos
    
    switch ($action) {
        case 'crear':
            crearProducto();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Crear nuevo producto
 */
function crearProducto() {
    global $db;
    
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        apiError('Token CSRF inválido', 403);
    }
    
    // Validar datos requeridos
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    
    if (empty($nombre) || $precio <= 0 || $categoria_id <= 0) {
        apiError('Datos requeridos incompletos', 400);
    }
    
    // Validar que la categoría existe
    $categoria = $db->fetchOne("SELECT id FROM categorias WHERE id = ? AND estado = 'activo'", [$categoria_id]);
    if (!$categoria) {
        apiError('Categoría no válida', 400);
    }
    
    // Procesar precio de oferta
    $precio_oferta = null;
    if (!empty($_POST['precio_oferta'])) {
        $precio_oferta = (float)$_POST['precio_oferta'];
        if ($precio_oferta >= $precio) {
            apiError('El precio de oferta debe ser menor al precio original', 400);
        }
    }
    
    // Procesar características
    $caracteristicas = null;
    if (!empty($_POST['caracteristicas'])) {
        $caracteristicas = json_encode($_POST['caracteristicas']);
    }
    
    // Procesar imagen
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = uploadFile($_FILES['imagen'], 'productos');
        if (!$imagen) {
            apiError('Error al subir la imagen', 400);
        }
    }
    
    // Insertar producto
    $sql = "INSERT INTO productos (categoria_id, nombre, descripcion, precio, precio_oferta, stock, imagen, caracteristicas, destacado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    
    $producto_id = $db->insert($sql, [
        $categoria_id, $nombre, $descripcion, $precio, $precio_oferta, 
        $stock, $imagen, $caracteristicas, $destacado
    ]);
    
    // Log de actividad
    logActivity('crear_producto', "Producto creado: $nombre (ID: $producto_id)");
    
    apiSuccess(['producto_id' => $producto_id], 'Producto creado exitosamente');
}

/**
 * Manejar peticiones PUT
 */
function handlePutRequest($action) {
    requireAdmin();
    
    switch ($action) {
        case 'actualizar':
            actualizarProducto();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Actualizar producto
 */
function actualizarProducto() {
    global $db;
    
    // Obtener datos del body
    $input = json_decode(file_get_contents('php://input'), true);
    
    $producto_id = (int)($input['id'] ?? 0);
    if ($producto_id <= 0) {
        apiError('ID de producto requerido', 400);
    }
    
    // Verificar que el producto existe
    $producto = $db->fetchOne("SELECT * FROM productos WHERE id = ?", [$producto_id]);
    if (!$producto) {
        apiError('Producto no encontrado', 404);
    }
    
    // Actualizar campos
    $updates = [];
    $params = [];
    
    if (isset($input['nombre'])) {
        $updates[] = "nombre = ?";
        $params[] = trim($input['nombre']);
    }
    
    if (isset($input['descripcion'])) {
        $updates[] = "descripcion = ?";
        $params[] = trim($input['descripcion']);
    }
    
    if (isset($input['precio'])) {
        $updates[] = "precio = ?";
        $params[] = (float)$input['precio'];
    }
    
    if (isset($input['precio_oferta'])) {
        $updates[] = "precio_oferta = ?";
        $params[] = $input['precio_oferta'] ? (float)$input['precio_oferta'] : null;
    }
    
    if (isset($input['stock'])) {
        $updates[] = "stock = ?";
        $params[] = (int)$input['stock'];
    }
    
    if (isset($input['categoria_id'])) {
        $updates[] = "categoria_id = ?";
        $params[] = (int)$input['categoria_id'];
    }
    
    if (isset($input['estado'])) {
        $updates[] = "estado = ?";
        $params[] = $input['estado'];
    }
    
    if (isset($input['destacado'])) {
        $updates[] = "destacado = ?";
        $params[] = $input['destacado'] ? 1 : 0;
    }
    
    if (isset($input['caracteristicas'])) {
        $updates[] = "caracteristicas = ?";
        $params[] = json_encode($input['caracteristicas']);
    }
    
    if (empty($updates)) {
        apiError('No hay datos para actualizar', 400);
    }
    
    $params[] = $producto_id;
    $sql = "UPDATE productos SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $db->update($sql, $params);
    
    // Log de actividad
    logActivity('actualizar_producto', "Producto actualizado: {$producto['nombre']} (ID: $producto_id)");
    
    apiSuccess(null, 'Producto actualizado exitosamente');
}

/**
 * Manejar peticiones DELETE
 */
function handleDeleteRequest($action) {
    requireAdmin();
    
    switch ($action) {
        case 'eliminar':
            eliminarProducto();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Eliminar producto (cambiar estado a inactivo)
 */
function eliminarProducto() {
    global $db;
    
    $producto_id = (int)($_GET['id'] ?? 0);
    
    if ($producto_id <= 0) {
        apiError('ID de producto requerido', 400);
    }
    
    // Verificar que el producto existe
    $producto = $db->fetchOne("SELECT nombre FROM productos WHERE id = ?", [$producto_id]);
    if (!$producto) {
        apiError('Producto no encontrado', 404);
    }
    
    // Cambiar estado a inactivo
    $db->update("UPDATE productos SET estado = 'inactivo' WHERE id = ?", [$producto_id]);
    
    // Log de actividad
    logActivity('eliminar_producto', "Producto eliminado: {$producto['nombre']} (ID: $producto_id)");
    
    apiSuccess(null, 'Producto eliminado exitosamente');
}
?> 