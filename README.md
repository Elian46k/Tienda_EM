# 🌿 EcoVerde - Tienda Ecológica

Una tienda en línea moderna y profesional para productos ecológicos y sostenibles, desarrollada con HTML5, CSS3, JavaScript, PHP y MySQL.

## 📋 Características

- **Diseño Responsive**: Adaptable a todos los dispositivos
- **Base de Datos Completa**: Sistema de gestión de productos, usuarios, pedidos y carrito
- **Autenticación de Usuarios**: Registro, login y gestión de perfiles
- **Carrito de Compras**: Funcionalidad completa de compras
- **Panel de Administración**: Gestión de productos y pedidos
- **Sistema de Reseñas**: Calificaciones y comentarios de productos
- **Newsletter**: Suscripción a ofertas y novedades
- **Optimizado para SEO**: Meta tags y estructura semántica

## 🚀 Instalación

### Requisitos Previos

- **XAMPP** (Apache + MySQL + PHP)
- **PHP 7.4+**
- **MySQL 5.7+**
- **Navegador web moderno**

### Paso 1: Configurar XAMPP

1. Descarga e instala XAMPP desde [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Inicia Apache y MySQL desde el panel de control de XAMPP
3. Verifica que ambos servicios estén funcionando correctamente

### Paso 2: Clonar/Descargar el Proyecto

1. Coloca todos los archivos del proyecto en la carpeta:
   ```
   C:\xampp\htdocs\tienda_EM\
   ```

2. La estructura de carpetas debe quedar así:
   ```
   tienda_EM/
   ├── index.html
   ├── tienda.html
   ├── carrito.html
   ├── checkout.html
   ├── css/
   │   └── style.css
   ├── js/
   │   └── main.js
   ├── img/
   │   └── (imágenes del proyecto)
   ├── bootstrap-5.3.6/
   │   └── (archivos de Bootstrap)
   ├── db/
   │   └── tienda_ecologica.sql
   ├── php/
   │   ├── config.php
   │   ├── productos.php
   │   ├── carrito.php
   │   └── auth.php
   └── README.md
   ```

### Paso 3: Configurar la Base de Datos

1. Abre tu navegador y ve a: `http://localhost/phpmyadmin`
2. Crea una nueva base de datos llamada `tienda_ecologica`
3. Selecciona la base de datos creada
4. Ve a la pestaña "Importar"
5. Selecciona el archivo `db/tienda_ecologica.sql`
6. Haz clic en "Continuar" para importar la base de datos

**O alternativamente:**

1. Abre phpMyAdmin
2. Ve a la pestaña "SQL"
3. Copia y pega todo el contenido del archivo `db/tienda_ecologica.sql`
4. Haz clic en "Continuar"

### Paso 4: Configurar la Conexión PHP

1. Abre el archivo `php/config.php`
2. Verifica que las credenciales de la base de datos sean correctas:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tienda_ecologica');
define('DB_USER', 'root');  // Usuario por defecto de XAMPP
define('DB_PASS', '');      // Contraseña vacía por defecto
```

3. Si has cambiado la contraseña de MySQL, actualiza `DB_PASS`

### Paso 5: Verificar la Instalación

1. Abre tu navegador y ve a: `http://localhost/tienda_EM/`
2. Deberías ver la página principal de EcoVerde
3. Verifica que todas las imágenes y estilos se carguen correctamente

## 📊 Estructura de la Base de Datos

### Tablas Principales

- **`categorias`**: Categorías de productos
- **`productos`**: Información de productos
- **`usuarios`**: Datos de usuarios y administradores
- **`carrito`**: Items en el carrito de compras
- **`pedidos`**: Información de pedidos
- **`detalles_pedido`**: Detalles de cada pedido
- **`reseñas`**: Calificaciones y comentarios
- **`newsletter`**: Suscriptores al newsletter
- **`configuracion`**: Configuración del sitio

### Datos de Prueba Incluidos

- **Categorías**: Hogar, Cuidado Personal, Oficina, Alimentos
- **Productos**: 8 productos ecológicos con precios y descripciones
- **Usuario Admin**: admin@ecoverde.com (password: password)
- **Configuración**: Datos básicos de la tienda

## 🔧 Configuración Adicional

### Configurar Correo Electrónico (Opcional)

Para habilitar el envío de correos electrónicos:

1. Edita `php/config.php`
2. Actualiza las configuraciones SMTP:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu_email@gmail.com');
define('SMTP_PASS', 'tu_password_de_aplicacion');
```

### Personalizar la Tienda

1. **Cambiar Información de Contacto**:
   - Edita la tabla `configuracion` en la base de datos
   - O modifica directamente en `php/config.php`

2. **Cambiar Moneda**:
   - Modifica `CURRENCY` en `php/config.php`

3. **Cambiar Impuestos**:
   - Modifica `TAX_RATE` en `php/config.php`

4. **Cambiar Configuración de Envío**:
   - Modifica `FREE_SHIPPING_THRESHOLD` y `SHIPPING_COST` en `php/config.php`

## 🛠️ API Endpoints

### Productos
- `GET php/productos.php?action=listar` - Listar productos
- `GET php/productos.php?action=destacados` - Productos destacados
- `GET php/productos.php?action=detalle&id=X` - Detalle de producto
- `GET php/productos.php?action=buscar&q=termino` - Buscar productos

### Carrito
- `GET php/carrito.php?action=obtener` - Obtener carrito
- `POST php/carrito.php?action=añadir` - Añadir al carrito
- `PUT php/carrito.php?action=actualizar` - Actualizar cantidad
- `DELETE php/carrito.php?action=eliminar&id=X` - Eliminar del carrito

### Autenticación
- `POST php/auth.php?action=registro` - Registro de usuario
- `POST php/auth.php?action=login` - Inicio de sesión
- `GET php/auth.php?action=perfil` - Obtener perfil
- `GET php/auth.php?action=logout` - Cerrar sesión

## 🔒 Seguridad

### Características de Seguridad Implementadas

- **Contraseñas Hasheadas**: Uso de `password_hash()` con BCRYPT
- **Protección CSRF**: Tokens CSRF en formularios
- **Sanitización de Datos**: Limpieza de entradas de usuario
- **Prepared Statements**: Prevención de SQL Injection
- **Validación de Sesiones**: Control de acceso y autenticación
- **Headers de Seguridad**: Protección contra ataques comunes

### Recomendaciones de Seguridad

1. **Cambiar Contraseñas por Defecto**:
   - Cambia la contraseña del usuario admin
   - Cambia la contraseña de MySQL

2. **Configurar HTTPS** (en producción):
   - Obtén un certificado SSL
   - Configura redirecciones HTTPS

3. **Backup Regular**:
   - Configura backups automáticos de la base de datos
   - Guarda copias de seguridad de los archivos

## 🐛 Solución de Problemas

### Problemas Comunes

1. **Página no carga estilos**:
   - Verifica que Apache esté funcionando
   - Revisa las rutas de los archivos CSS
   - Verifica permisos de archivos

2. **Error de conexión a base de datos**:
   - Verifica que MySQL esté funcionando
   - Revisa las credenciales en `php/config.php`
   - Verifica que la base de datos existe

3. **Imágenes no se cargan**:
   - Verifica que las imágenes estén en la carpeta `img/`
   - Revisa las rutas en el HTML
   - Verifica permisos de archivos

4. **Errores de PHP**:
   - Revisa el archivo de log: `logs/php_errors.log`
   - Verifica la configuración de PHP en XAMPP
   - Asegúrate de que las extensiones necesarias estén habilitadas

### Logs y Debugging

- **Logs de PHP**: `logs/php_errors.log`
- **Logs de Apache**: `C:\xampp\apache\logs\`
- **Logs de MySQL**: `C:\xampp\mysql\data\`

## 📱 Características Responsive

El sitio está optimizado para:
- **Desktop**: 1200px+
- **Tablet**: 768px - 1199px
- **Mobile**: 320px - 767px

## 🎨 Personalización

### Cambiar Colores
Edita las variables CSS en `css/style.css`:
```css
:root {
    --primary-color: #2e7d32;
    --secondary-color: #4caf50;
    --accent-color: #ff6b6b;
}
```

### Cambiar Fuentes
Modifica las importaciones de Google Fonts en el HTML:
```html
<link href="https://fonts.googleapis.com/css2?family=TuFuente:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
```

## 📞 Soporte

Para soporte técnico o preguntas:
- **Email**: info@ecoverde.com
- **Teléfono**: +51 936 623 658

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Puedes usarlo libremente para proyectos personales y comerciales.

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:
1. Fork el proyecto
2. Crea una rama para tu feature
3. Commit tus cambios
4. Push a la rama
5. Abre un Pull Request

---

**Desarrollado con ❤️ para un futuro más ecológico y sostenible** 