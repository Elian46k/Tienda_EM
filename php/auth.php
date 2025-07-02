<?php
/**
 * API de Autenticación - Tienda Ecológica EcoVerde
 * 
 * Este archivo maneja todas las operaciones relacionadas con la autenticación
 * de usuarios incluyendo registro, login, logout y gestión de sesiones.
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
        default:
            apiError('Método no permitido', 405);
    }
    
} catch (Exception $e) {
    error_log("Error en auth.php: " . $e->getMessage());
    apiError('Error interno del servidor', 500);
}

/**
 * Manejar peticiones GET
 */
function handleGetRequest($action) {
    switch ($action) {
        case 'perfil':
            obtenerPerfil();
            break;
        case 'verificar':
            verificarSesion();
            break;
        case 'logout':
            logout();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Obtener perfil del usuario
 */
function obtenerPerfil() {
    if (!isAuthenticated()) {
        apiError('Debes iniciar sesión para ver tu perfil', 401);
    }
    
    global $db;
    $usuario_id = $_SESSION['user_id'];
    
    $sql = "SELECT id, nombre, apellidos, email, telefono, direccion, ciudad, codigo_postal, pais, 
                   tipo_usuario, fecha_registro, ultimo_acceso
            FROM usuarios 
            WHERE id = ? AND estado = 'activo'";
    
    $usuario = $db->fetchOne($sql, [$usuario_id]);
    
    if (!$usuario) {
        // Limpiar sesión si el usuario no existe
        session_destroy();
        apiError('Usuario no encontrado', 404);
    }
    
    // Obtener estadísticas del usuario
    $stats = obtenerEstadisticasUsuario($usuario_id);
    
    $usuario['estadisticas'] = $stats;
    
    apiSuccess($usuario);
}

/**
 * Verificar si la sesión está activa
 */
function verificarSesion() {
    if (isAuthenticated()) {
        $usuario = [
            'id' => $_SESSION['user_id'],
            'nombre' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'tipo_usuario' => $_SESSION['user_type']
        ];
        apiSuccess($usuario);
    } else {
        apiSuccess(['autenticado' => false]);
    }
}

/**
 * Logout del usuario
 */
function logout() {
    if (isAuthenticated()) {
        logActivity('logout', 'Usuario cerró sesión');
    }
    
    // Destruir sesión
    session_destroy();
    
    apiSuccess(null, 'Sesión cerrada exitosamente');
}

/**
 * Manejar peticiones POST
 */
function handlePostRequest($action) {
    switch ($action) {
        case 'registro':
            registro();
            break;
        case 'login':
            login();
            break;
        case 'recuperar':
            recuperarPassword();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Registro de nuevo usuario
 */
function registro() {
    global $db;
    
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        apiError('Token CSRF inválido', 403);
    }
    
    // Validar datos requeridos
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    
    // Validaciones
    if (empty($nombre) || empty($apellidos) || empty($email) || empty($password)) {
        apiError('Todos los campos son requeridos', 400);
    }
    
    if (!validateEmail($email)) {
        apiError('Email no válido', 400);
    }
    
    if (strlen($password) < 6) {
        apiError('La contraseña debe tener al menos 6 caracteres', 400);
    }
    
    if ($password !== $confirmar_password) {
        apiError('Las contraseñas no coinciden', 400);
    }
    
    // Verificar si el email ya existe
    $usuario_existente = $db->fetchOne("SELECT id FROM usuarios WHERE email = ?", [$email]);
    if ($usuario_existente) {
        apiError('El email ya está registrado', 400);
    }
    
    // Crear usuario
    $password_hash = hashPassword($password);
    
    $sql = "INSERT INTO usuarios (nombre, apellidos, email, password, telefono, tipo_usuario) 
            VALUES (?, ?, ?, ?, ?, 'cliente')";
    
    $usuario_id = $db->insert($sql, [$nombre, $apellidos, $email, $password_hash, $telefono]);
    
    // Iniciar sesión automáticamente
    iniciarSesion($usuario_id, $nombre, $email, 'cliente');
    
    // Log de actividad
    logActivity('registro', "Nuevo usuario registrado: $email");
    
    apiSuccess([
        'usuario_id' => $usuario_id,
        'mensaje' => 'Usuario registrado exitosamente'
    ], 'Registro exitoso');
}

/**
 * Login de usuario
 */
function login() {
    global $db;
    
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        apiError('Token CSRF inválido', 403);
    }
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        apiError('Email y contraseña son requeridos', 400);
    }
    
    if (!validateEmail($email)) {
        apiError('Email no válido', 400);
    }
    
    // Buscar usuario
    $sql = "SELECT id, nombre, apellidos, email, password, tipo_usuario, estado 
            FROM usuarios 
            WHERE email = ?";
    
    $usuario = $db->fetchOne($sql, [$email]);
    
    if (!$usuario) {
        apiError('Credenciales inválidas', 401);
    }
    
    if ($usuario['estado'] !== 'activo') {
        apiError('Tu cuenta está desactivada. Contacta al administrador.', 403);
    }
    
    // Verificar contraseña
    if (!verifyPassword($password, $usuario['password'])) {
        apiError('Credenciales inválidas', 401);
    }
    
    // Actualizar último acceso
    $db->update("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?", [$usuario['id']]);
    
    // Iniciar sesión
    iniciarSesion($usuario['id'], $usuario['nombre'], $usuario['email'], $usuario['tipo_usuario']);
    
    // Log de actividad
    logActivity('login', "Usuario inició sesión: {$usuario['email']}");
    
    apiSuccess([
        'usuario_id' => $usuario['id'],
        'nombre' => $usuario['nombre'],
        'tipo_usuario' => $usuario['tipo_usuario']
    ], 'Inicio de sesión exitoso');
}

/**
 * Recuperar contraseña
 */
function recuperarPassword() {
    global $db;
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !validateEmail($email)) {
        apiError('Email válido requerido', 400);
    }
    
    // Verificar si el usuario existe
    $usuario = $db->fetchOne("SELECT id, nombre FROM usuarios WHERE email = ? AND estado = 'activo'", [$email]);
    
    if (!$usuario) {
        // Por seguridad, no revelar si el email existe o no
        apiSuccess(null, 'Si el email existe, recibirás instrucciones para recuperar tu contraseña');
    }
    
    // Generar token de recuperación
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Guardar token en la base de datos (necesitarías crear una tabla para esto)
    // Por ahora, solo simulamos el proceso
    
    // En un entorno real, aquí enviarías un email con el enlace de recuperación
    // $resetLink = APP_URL . "/reset-password.php?token=" . $token;
    
    // Log de actividad
    logActivity('recuperar_password', "Solicitud de recuperación de contraseña: $email");
    
    apiSuccess(null, 'Si el email existe, recibirás instrucciones para recuperar tu contraseña');
}

/**
 * Manejar peticiones PUT
 */
function handlePutRequest($action) {
    switch ($action) {
        case 'actualizar':
            actualizarPerfil();
            break;
        case 'cambiar_password':
            cambiarPassword();
            break;
        default:
            apiError('Acción no válida', 400);
    }
}

/**
 * Actualizar perfil del usuario
 */
function actualizarPerfil() {
    if (!isAuthenticated()) {
        apiError('Debes iniciar sesión para actualizar tu perfil', 401);
    }
    
    global $db;
    $usuario_id = $_SESSION['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos
    $nombre = trim($input['nombre'] ?? '');
    $apellidos = trim($input['apellidos'] ?? '');
    $telefono = trim($input['telefono'] ?? '');
    $direccion = trim($input['direccion'] ?? '');
    $ciudad = trim($input['ciudad'] ?? '');
    $codigo_postal = trim($input['codigo_postal'] ?? '');
    
    if (empty($nombre) || empty($apellidos)) {
        apiError('Nombre y apellidos son requeridos', 400);
    }
    
    // Actualizar perfil
    $sql = "UPDATE usuarios SET 
            nombre = ?, apellidos = ?, telefono = ?, direccion = ?, 
            ciudad = ?, codigo_postal = ?, updated_at = NOW() 
            WHERE id = ?";
    
    $db->update($sql, [$nombre, $apellidos, $telefono, $direccion, $ciudad, $codigo_postal, $usuario_id]);
    
    // Actualizar datos de sesión
    $_SESSION['user_name'] = $nombre;
    
    // Log de actividad
    logActivity('actualizar_perfil', "Perfil actualizado");
    
    apiSuccess(null, 'Perfil actualizado exitosamente');
}

/**
 * Cambiar contraseña
 */
function cambiarPassword() {
    if (!isAuthenticated()) {
        apiError('Debes iniciar sesión para cambiar tu contraseña', 401);
    }
    
    global $db;
    $usuario_id = $_SESSION['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $password_actual = $input['password_actual'] ?? '';
    $password_nuevo = $input['password_nuevo'] ?? '';
    $confirmar_password = $input['confirmar_password'] ?? '';
    
    if (empty($password_actual) || empty($password_nuevo) || empty($confirmar_password)) {
        apiError('Todos los campos son requeridos', 400);
    }
    
    if (strlen($password_nuevo) < 6) {
        apiError('La nueva contraseña debe tener al menos 6 caracteres', 400);
    }
    
    if ($password_nuevo !== $confirmar_password) {
        apiError('Las contraseñas no coinciden', 400);
    }
    
    // Verificar contraseña actual
    $usuario = $db->fetchOne("SELECT password FROM usuarios WHERE id = ?", [$usuario_id]);
    
    if (!verifyPassword($password_actual, $usuario['password'])) {
        apiError('Contraseña actual incorrecta', 400);
    }
    
    // Actualizar contraseña
    $password_hash = hashPassword($password_nuevo);
    $db->update("UPDATE usuarios SET password = ?, updated_at = NOW() WHERE id = ?", [$password_hash, $usuario_id]);
    
    // Log de actividad
    logActivity('cambiar_password', "Contraseña cambiada");
    
    apiSuccess(null, 'Contraseña cambiada exitosamente');
}

/**
 * Función auxiliar para iniciar sesión
 */
function iniciarSesion($usuario_id, $nombre, $email, $tipo_usuario) {
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
    
    // Guardar datos en sesión
    $_SESSION['user_id'] = $usuario_id;
    $_SESSION['user_name'] = $nombre;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_type'] = $tipo_usuario;
    $_SESSION['login_time'] = time();
    
    // Generar nuevo token CSRF
    generateCSRFToken();
}

/**
 * Función auxiliar para obtener estadísticas del usuario
 */
function obtenerEstadisticasUsuario($usuario_id) {
    global $db;
    
    // Total de pedidos
    $total_pedidos = $db->fetchOne("SELECT COUNT(*) as total FROM pedidos WHERE usuario_id = ?", [$usuario_id]);
    
    // Pedidos pendientes
    $pedidos_pendientes = $db->fetchOne("SELECT COUNT(*) as total FROM pedidos WHERE usuario_id = ? AND estado IN ('pendiente', 'confirmado', 'en_proceso')", [$usuario_id]);
    
    // Total gastado
    $total_gastado = $db->fetchOne("SELECT SUM(total) as total FROM pedidos WHERE usuario_id = ? AND estado = 'entregado'", [$usuario_id]);
    
    // Items en carrito
    $items_carrito = $db->fetchOne("SELECT COUNT(*) as total FROM carrito WHERE usuario_id = ?", [$usuario_id]);
    
    return [
        'total_pedidos' => (int)$total_pedidos['total'],
        'pedidos_pendientes' => (int)$pedidos_pendientes['total'],
        'total_gastado' => (float)$total_gastado['total'] ?: 0,
        'total_gastado_formateado' => formatPrice((float)$total_gastado['total'] ?: 0),
        'items_carrito' => (int)$items_carrito['total']
    ];
}

/**
 * Función auxiliar para validar sesión y obtener usuario
 */
function obtenerUsuarioActual() {
    if (!isAuthenticated()) {
        return null;
    }
    
    global $db;
    $usuario_id = $_SESSION['user_id'];
    
    return $db->fetchOne("SELECT id, nombre, apellidos, email, tipo_usuario FROM usuarios WHERE id = ? AND estado = 'activo'", [$usuario_id]);
}

/**
 * Función auxiliar para verificar permisos
 */
function verificarPermisos($tipo_requerido = 'cliente') {
    if (!isAuthenticated()) {
        return false;
    }
    
    $usuario = obtenerUsuarioActual();
    if (!$usuario) {
        return false;
    }
    
    if ($tipo_requerido === 'admin' && $usuario['tipo_usuario'] !== 'admin') {
        return false;
    }
    
    return true;
}
?> 