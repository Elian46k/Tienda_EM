<?php
/**
 * API del Carrito de Compras - Tienda Ecológica EcoVerde
 * 
 * Este archivo maneja todas las operaciones relacionadas con el carrito
 * de compras incluyendo añadir, eliminar, actualizar y obtener productos.
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
    error_log("Error en carrito.php: " . $e->getMessage());
    apiError('Error interno del servidor', 500);
}

/**
 * Manejar peticiones GET
 */
function handleGetRequest($action) {
    switch ($action) {
        case 'obtener':
            obtenerCarrito();
            break;
        case 'total':
            obtenerTotalCarrito();
            break;
        case 'cantidad':
            obtenerCantidadCarrito();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Obtener productos del carrito
 */
function obtenerCarrito() {
    global $db;
    
    if (!isAuthenticated()) {
        apiError('Debes iniciar sesión para ver tu carrito', 401);
    }
    
    $usuario_id = $_SESSION['user_id'];
    
    $sql = "SELECT c.*, p.nombre, p.descripcion, p.imagen, p.stock, p.estado as producto_estado,
                   p.precio as precio_original, p.precio_oferta,
                   cat.nombre as categoria_nombre
            FROM carrito c
            LEFT JOIN productos p ON c.producto_id = p.id
            LEFT JOIN categorias cat ON p.categoria_id = cat.id
            WHERE c.usuario_id = ?
            ORDER BY c.created_at DESC";
    
    $items = $db->fetchAll($sql, [$usuario_id]);
    
    $total = 0;
    $cantidad_total = 0;
    $items_validos = [];
    
    foreach ($items as $item) {
        // Verificar si el producto sigue disponible
        if ($item['producto_estado'] !== 'activo') {
            // Eliminar del carrito si el producto no está disponible
            $db->delete("DELETE FROM carrito WHERE id = ?", [$item['id']]);
            continue;
        }
        
        // Verificar stock
        if ($item['cantidad'] > $item['stock']) {
            $item['cantidad'] = $item['stock'];
            $db->update("UPDATE carrito SET cantidad = ? WHERE id = ?", [$item['stock'], $item['id']]);
        }
        
        // Calcular precios
        $precio_actual = $item['precio_oferta'] ?: $item['precio_original'];
        $subtotal = $precio_actual * $item['cantidad'];
        
        $item['precio_actual'] = $precio_actual;
        $item['precio_actual_formateado'] = formatPrice($precio_actual);
        $item['subtotal'] = $subtotal;
        $item['subtotal_formateado'] = formatPrice($subtotal);
        
        if ($item['precio_oferta']) {
            $item['descuento_porcentaje'] = calculateDiscount($item['precio_original'], $item['precio_oferta']);
        }
        
        $total += $subtotal;
        $cantidad_total += $item['cantidad'];
        $items_validos[] = $item;
    }
    
    apiSuccess([
        'items' => $items_validos,
        'total' => $total,
        'total_formateado' => formatPrice($total),
        'cantidad_total' => $cantidad_total,
        'envio_gratis' => $total >= FREE_SHIPPING_THRESHOLD,
        'costo_envio' => $total >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST,
        'costo_envio_formateado' => $total >= FREE_SHIPPING_THRESHOLD ? 'Gratis' : formatPrice(SHIPPING_COST),
        'total_con_envio' => $total + ($total >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST),
        'total_con_envio_formateado' => formatPrice($total + ($total >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST))
    ]);
}

/**
 * Obtener total del carrito
 */
function obtenerTotalCarrito() {
    global $db;
    
    if (!isAuthenticated()) {
        apiSuccess(['total' => 0, 'cantidad' => 0]);
    }
    
    $usuario_id = $_SESSION['user_id'];
    
    $sql = "SELECT SUM(c.cantidad * c.precio_unitario) as total, COUNT(c.id) as cantidad
            FROM carrito c
            LEFT JOIN productos p ON c.producto_id = p.id
            WHERE c.usuario_id = ? AND p.estado = 'activo'";
    
    $resultado = $db->fetchOne($sql, [$usuario_id]);
    
    apiSuccess([
        'total' => (float)$resultado['total'] ?: 0,
        'total_formateado' => formatPrice((float)$resultado['total'] ?: 0),
        'cantidad' => (int)$resultado['cantidad'] ?: 0
    ]);
}

/**
 * Obtener cantidad de items en el carrito
 */
function obtenerCantidadCarrito() {
    global $db;
    
    if (!isAuthenticated()) {
        apiSuccess(['cantidad' => 0]);
    }
    
    $usuario_id = $_SESSION['user_id'];
    
    $sql = "SELECT COUNT(c.id) as cantidad
            FROM carrito c
            LEFT JOIN productos p ON c.producto_id = p.id
            WHERE c.usuario_id = ? AND p.estado = 'activo'";
    
    $resultado = $db->fetchOne($sql, [$usuario_id]);
    
    apiSuccess(['cantidad' => (int)$resultado['cantidad'] ?: 0]);
}

/**
 * Manejar peticiones POST
 */
function handlePostRequest($action) {
    switch ($action) {
        case 'añadir':
            añadirAlCarrito();
            break;
        case 'limpiar':
            limpiarCarrito();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Añadir producto al carrito
 */
function añadirAlCarrito() {
    global $db;
    
    if (!isAuthenticated()) {
        apiError('Debes iniciar sesión para añadir productos al carrito', 401);
    }
    
    $usuario_id = $_SESSION['user_id'];
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    
    if ($producto_id <= 0) {
        apiError('ID de producto requerido', 400);
    }
    
    if ($cantidad <= 0) {
        apiError('Cantidad debe ser mayor a 0', 400);
    }
    
    // Verificar que el producto existe y está activo
    $producto = $db->fetchOne("SELECT * FROM productos WHERE id = ? AND estado = 'activo'", [$producto_id]);
    if (!$producto) {
        apiError('Producto no encontrado o no disponible', 404);
    }
    
    // Verificar stock
    if ($cantidad > $producto['stock']) {
        apiError("Solo hay {$producto['stock']} unidades disponibles", 400);
    }
    
    // Verificar si el producto ya está en el carrito
    $item_existente = $db->fetchOne("SELECT * FROM carrito WHERE usuario_id = ? AND producto_id = ?", [$usuario_id, $producto_id]);
    
    if ($item_existente) {
        // Actualizar cantidad
        $nueva_cantidad = $item_existente['cantidad'] + $cantidad;
        
        if ($nueva_cantidad > $producto['stock']) {
            apiError("No puedes añadir más unidades. Stock disponible: {$producto['stock']}", 400);
        }
        
        $precio_actual = $producto['precio_oferta'] ?: $producto['precio'];
        $db->update("UPDATE carrito SET cantidad = ?, precio_unitario = ? WHERE id = ?", 
                   [$nueva_cantidad, $precio_actual, $item_existente['id']]);
        
        $mensaje = "Cantidad actualizada en el carrito";
    } else {
        // Añadir nuevo item
        $precio_actual = $producto['precio_oferta'] ?: $producto['precio'];
        $db->insert("INSERT INTO carrito (usuario_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)",
                   [$usuario_id, $producto_id, $cantidad, $precio_actual]);
        
        $mensaje = "Producto añadido al carrito";
    }
    
    // Log de actividad
    logActivity('añadir_carrito', "Producto añadido al carrito: {$producto['nombre']} (ID: $producto_id)");
    
    // Obtener cantidad actualizada del carrito
    $cantidad_carrito = $db->fetchOne("SELECT COUNT(*) as cantidad FROM carrito WHERE usuario_id = ?", [$usuario_id]);
    
    apiSuccess([
        'mensaje' => $mensaje,
        'cantidad_carrito' => (int)$cantidad_carrito['cantidad']
    ], $mensaje);
}

/**
 * Limpiar carrito
 */
function limpiarCarrito() {
    global $db;
    
    if (!isAuthenticated()) {
        apiError('Debes iniciar sesión para limpiar el carrito', 401);
    }
    
    $usuario_id = $_SESSION['user_id'];
    
    $db->delete("DELETE FROM carrito WHERE usuario_id = ?", [$usuario_id]);
    
    // Log de actividad
    logActivity('limpiar_carrito', "Carrito limpiado");
    
    apiSuccess(null, 'Carrito limpiado exitosamente');
}

/**
 * Manejar peticiones PUT
 */
function handlePutRequest($action) {
    switch ($action) {
        case 'actualizar':
            actualizarCantidad();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Actualizar cantidad de un producto en el carrito
 */
function actualizarCantidad() {
    global $db;
    
    if (!isAuthenticated()) {
        apiError('Debes iniciar sesión para actualizar el carrito', 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $usuario_id = $_SESSION['user_id'];
    $carrito_id = (int)($input['carrito_id'] ?? 0);
    $cantidad = (int)($input['cantidad'] ?? 0);
    
    if ($carrito_id <= 0) {
        apiError('ID de carrito requerido', 400);
    }
    
    if ($cantidad <= 0) {
        apiError('Cantidad debe ser mayor a 0', 400);
    }
    
    // Verificar que el item pertenece al usuario
    $item = $db->fetchOne("SELECT c.*, p.stock, p.nombre 
                          FROM carrito c 
                          LEFT JOIN productos p ON c.producto_id = p.id 
                          WHERE c.id = ? AND c.usuario_id = ?", [$carrito_id, $usuario_id]);
    
    if (!$item) {
        apiError('Item no encontrado', 404);
    }
    
    // Verificar stock
    if ($cantidad > $item['stock']) {
        apiError("Solo hay {$item['stock']} unidades disponibles", 400);
    }
    
    // Actualizar cantidad
    $db->update("UPDATE carrito SET cantidad = ? WHERE id = ?", [$cantidad, $carrito_id]);
    
    // Log de actividad
    logActivity('actualizar_carrito', "Cantidad actualizada: {$item['nombre']} - $cantidad unidades");
    
    apiSuccess(null, 'Cantidad actualizada exitosamente');
}

/**
 * Manejar peticiones DELETE
 */
function handleDeleteRequest($action) {
    switch ($action) {
        case 'eliminar':
            eliminarDelCarrito();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Eliminar producto del carrito
 */
function eliminarDelCarrito() {
    global $db;
    
    if (!isAuthenticated()) {
        apiError('Debes iniciar sesión para eliminar productos del carrito', 401);
    }
    
    $usuario_id = $_SESSION['user_id'];
    $carrito_id = (int)($_GET['id'] ?? 0);
    
    if ($carrito_id <= 0) {
        apiError('ID de carrito requerido', 400);
    }
    
    // Verificar que el item pertenece al usuario
    $item = $db->fetchOne("SELECT c.*, p.nombre 
                          FROM carrito c 
                          LEFT JOIN productos p ON c.producto_id = p.id 
                          WHERE c.id = ? AND c.usuario_id = ?", [$carrito_id, $usuario_id]);
    
    if (!$item) {
        apiError('Item no encontrado', 404);
    }
    
    // Eliminar item
    $db->delete("DELETE FROM carrito WHERE id = ?", [$carrito_id]);
    
    // Log de actividad
    logActivity('eliminar_carrito', "Producto eliminado del carrito: {$item['nombre']}");
    
    apiSuccess(null, 'Producto eliminado del carrito exitosamente');
}

/**
 * Función auxiliar para verificar stock en tiempo real
 */
function verificarStock($producto_id, $cantidad_solicitada) {
    global $db;
    
    $producto = $db->fetchOne("SELECT stock, nombre FROM productos WHERE id = ? AND estado = 'activo'", [$producto_id]);
    
    if (!$producto) {
        return ['disponible' => false, 'mensaje' => 'Producto no disponible'];
    }
    
    if ($cantidad_solicitada > $producto['stock']) {
        return ['disponible' => false, 'mensaje' => "Solo hay {$producto['stock']} unidades disponibles de {$producto['nombre']}"];
    }
    
    return ['disponible' => true, 'stock_actual' => $producto['stock']];
}

/**
 * Función auxiliar para calcular totales del carrito
 */
function calcularTotalesCarrito($usuario_id) {
    global $db;
    
    $sql = "SELECT SUM(c.cantidad * c.precio_unitario) as subtotal, COUNT(c.id) as items
            FROM carrito c
            LEFT JOIN productos p ON c.producto_id = p.id
            WHERE c.usuario_id = ? AND p.estado = 'activo'";
    
    $resultado = $db->fetchOne($sql, [$usuario_id]);
    
    $subtotal = (float)$resultado['subtotal'] ?: 0;
    $items = (int)$resultado['items'] ?: 0;
    
    $envio_gratis = $subtotal >= FREE_SHIPPING_THRESHOLD;
    $costo_envio = $envio_gratis ? 0 : SHIPPING_COST;
    $total = $subtotal + $costo_envio;
    
    return [
        'subtotal' => $subtotal,
        'subtotal_formateado' => formatPrice($subtotal),
        'costo_envio' => $costo_envio,
        'costo_envio_formateado' => $envio_gratis ? 'Gratis' : formatPrice($costo_envio),
        'total' => $total,
        'total_formateado' => formatPrice($total),
        'items' => $items,
        'envio_gratis' => $envio_gratis
    ];
}
?> 