<?php
/**
 * Configuración de la Base de Datos - Tienda Ecológica EcoVerde
 * 
 * Este archivo contiene la configuración de conexión a la base de datos
 * y funciones de utilidad para el manejo de errores y seguridad.
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'tienda_ecologica');
define('DB_USER', 'root');  // Cambiar en producción
define('DB_PASS', '');      // Cambiar en producción
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'EcoVerde');
define('APP_URL', 'http://localhost/tienda_EM');
define('APP_VERSION', '1.0.0');

// Configuración de sesiones
define('SESSION_NAME', 'ecoverde_session');
define('SESSION_LIFETIME', 3600); // 1 hora

// Configuración de seguridad
define('HASH_COST', 12); // Costo para password_hash()
define('JWT_SECRET', 'tu_clave_secreta_muy_segura_cambiar_en_produccion');

// Configuración de archivos
define('UPLOAD_PATH', '../img/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Configuración de correo (opcional)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu_email@gmail.com');
define('SMTP_PASS', 'tu_password_de_aplicacion');

// Configuración de paginación
define('ITEMS_PER_PAGE', 12);

// Configuración de moneda
define('CURRENCY', 'S/.');
define('TAX_RATE', 0.18); // 18% IGV

// Configuración de envío
define('FREE_SHIPPING_THRESHOLD', 100);
define('SHIPPING_COST', 15);

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Configuración de zona horaria
date_default_timezone_set('America/Lima');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

/**
 * Clase Database para manejo de conexiones
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    /**
     * Obtener instancia única (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener conexión PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Ejecutar consulta preparada
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error en consulta SQL: " . $e->getMessage());
            throw new Exception("Error en la consulta de base de datos");
        }
    }
    
    /**
     * Obtener una fila
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtener múltiples filas
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insertar y obtener ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }
    
    /**
     * Actualizar registros
     */
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Eliminar registros
     */
    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Revertir transacción
     */
    public function rollback() {
        return $this->connection->rollback();
    }
}

/**
 * Funciones de utilidad
 */

/**
 * Sanitizar entrada de datos
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generar hash seguro para contraseñas
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirigir con mensaje
 */
function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $url");
    exit();
}

/**
 * Obtener mensaje de sesión
 */
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message'], $_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Verificar si el usuario es administrador
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Requerir autenticación
 */
function requireAuth() {
    if (!isAuthenticated()) {
        redirect('login.php', 'Debes iniciar sesión para acceder a esta página', 'warning');
    }
}

/**
 * Requerir permisos de administrador
 */
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        redirect('index.php', 'No tienes permisos para acceder a esta página', 'error');
    }
}

/**
 * Formatear precio
 */
function formatPrice($price) {
    return CURRENCY . ' ' . number_format($price, 2);
}

/**
 * Calcular descuento
 */
function calculateDiscount($originalPrice, $discountPrice) {
    if ($originalPrice <= 0) return 0;
    return round((($originalPrice - $discountPrice) / $originalPrice) * 100);
}

/**
 * Generar slug para URLs
 */
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Validar archivo subido
 */
function validateUploadedFile($file) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    return true;
}

/**
 * Subir archivo
 */
function uploadFile($file, $destination) {
    if (!validateUploadedFile($file)) {
        return false;
    }
    
    $filename = uniqid() . '_' . sanitize($file['name']);
    $filepath = UPLOAD_PATH . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

/**
 * Log de actividad
 */
function logActivity($action, $details = '') {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $sql = "INSERT INTO log_actividad (usuario_id, accion, detalles, ip, user_agent) VALUES (?, ?, ?, ?, ?)";
    $db->query($sql, [$userId, $action, $details, $ip, $userAgent]);
}

/**
 * Obtener configuración del sitio
 */
function getConfig($key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT clave, valor FROM configuracion");
        $config = [];
        foreach ($rows as $row) {
            $config[$row['clave']] = $row['valor'];
        }
    }
    
    return $config[$key] ?? $default;
}

/**
 * Enviar respuesta JSON
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Manejar errores de API
 */
function apiError($message, $status = 400) {
    jsonResponse(['error' => $message], $status);
}

/**
 * Manejar éxito de API
 */
function apiSuccess($data, $message = '') {
    jsonResponse(['success' => true, 'data' => $data, 'message' => $message]);
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Configurar headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Verificar si la base de datos está disponible
try {
    $db = Database::getInstance();
    $db->getConnection();
} catch (Exception $e) {
    error_log("Error crítico: No se puede conectar a la base de datos");
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Error de conexión a la base de datos: " . $e->getMessage());
    } else {
        die("Error del servidor. Por favor, inténtalo más tarde.");
    }
}
?> 