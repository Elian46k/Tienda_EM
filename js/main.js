// Función para añadir producto al carrito
function agregarAlCarrito(producto) {
    try {
        let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
        const existente = carrito.find(item => item.nombre === producto.nombre);
        
        if (existente) {
            existente.cantidad += 1;
        } else {
            carrito.push({
                ...producto,
                cantidad: 1
            });
        }
        
        localStorage.setItem("carrito", JSON.stringify(carrito));
        mostrarNotificacion("✅ Producto añadido al carrito");
        actualizarContadorCarrito();
        
        // Efecto visual en el botón
        const boton = event.target;
        boton.style.backgroundColor = "#4CAF50";
        boton.textContent = "¡Añadido!";
        
        setTimeout(() => {
            boton.style.backgroundColor = "#2e7d32";
            boton.textContent = "Añadir al carrito";
        }, 1000);
        
    } catch (error) {
        console.error("Error al añadir al carrito:", error);
        mostrarNotificacion("❌ Error al añadir al carrito", "error");
    }
}

// Función para vaciar el carrito
function vaciarCarrito() {
    if (confirm("¿Estás seguro de que quieres vaciar el carrito?")) {
        localStorage.removeItem("carrito");
        mostrarCarrito();
        actualizarContadorCarrito();
        mostrarNotificacion("🗑️ Carrito vaciado");
    }
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = "success") {
    // Remover notificación existente
    const notificacionExistente = document.querySelector(".notificacion");
    if (notificacionExistente) {
        notificacionExistente.remove();
    }
    
    const notificacion = document.createElement("div");
    notificacion.className = `notificacion ${tipo}`;
    notificacion.textContent = mensaje;
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: ${tipo === "success" ? "#4CAF50" : "#f44336"};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        z-index: 1000;
        font-weight: bold;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notificacion);
    
    // Remover después de 1 segundo (reducido de 3 segundos)
    setTimeout(() => {
        notificacion.style.animation = "slideOut 0.3s ease";
        setTimeout(() => notificacion.remove(), 300);
    }, 1000);
}

// Función para actualizar contador del carrito en el header
function actualizarContadorCarrito() {
    const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    const totalItems = carrito.reduce((total, item) => total + item.cantidad, 0);
    
    // Buscar o crear el contador en el header
    let contador = document.querySelector(".contador-carrito");
    if (!contador) {
        const nav = document.querySelector("nav");
        contador = document.createElement("span");
        contador.className = "contador-carrito";
        contador.style.cssText = `
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
            position: relative;
            top: -8px;
        `;
        
        // Añadir el contador al enlace del carrito
        const enlaceCarrito = document.querySelector('a[href="carrito.html"]');
        if (enlaceCarrito) {
            enlaceCarrito.appendChild(contador);
        }
    }
    
    if (totalItems > 0) {
        contador.textContent = totalItems;
        contador.style.display = "inline";
    } else {
        contador.style.display = "none";
    }
}

// Mostrar carrito en carrito.html
function mostrarCarrito() {
    const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    const tbody = document.querySelector("tbody");
    const totalElement = document.querySelector(".carrito-total p");
    
    if (!tbody) return;
    
    tbody.innerHTML = "";
    let total = 0;

    if (carrito.length === 0) {
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td colspan="4" style="text-align: center; padding: 2rem; color: #666;">
                No hay productos en el carrito
            </td>
        `;
        tbody.appendChild(fila);
    } else {
        carrito.forEach(producto => {
            const subtotal = producto.precio * producto.cantidad;
            total += subtotal;

            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="${producto.imagen}" alt="${producto.nombre}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                        <span>${producto.nombre}</span>
                    </div>
                </td>
                <td>
                    <input type="number" min="1" value="${producto.cantidad}" data-nombre="${producto.nombre}" style="width: 60px; padding: 5px;">
                    <button onclick="eliminarProducto('${producto.nombre}')" style="background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 3px; margin-left: 5px; cursor: pointer;">Eliminar</button>
                </td>
                <td>S/. ${producto.precio.toFixed(2)}</td>
                <td>S/. ${subtotal.toFixed(2)}</td>
            `;
            tbody.appendChild(fila);
        });
    }

    if (totalElement) {
        totalElement.innerHTML = `<strong>Total:</strong> S/. ${total.toFixed(2)}`;
    }

    actualizarCantidad();
}

// Función para eliminar producto del carrito
function eliminarProducto(nombre) {
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    carrito = carrito.filter(item => item.nombre !== nombre);
    localStorage.setItem("carrito", JSON.stringify(carrito));
    mostrarCarrito();
    actualizarContadorCarrito();
    mostrarNotificacion("🗑️ Producto eliminado del carrito");
}

// Actualizar cantidades en el carrito
function actualizarCantidad() {
    const inputs = document.querySelectorAll("tbody input[type='number']");
    inputs.forEach(input => {
        input.addEventListener("change", () => {
            let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
            const nombre = input.dataset.nombre;
            const item = carrito.find(p => p.nombre === nombre);
            if (item) {
                item.cantidad = parseInt(input.value);
                if (item.cantidad <= 0) {
                    eliminarProducto(nombre);
                } else {
                    localStorage.setItem("carrito", JSON.stringify(carrito));
                    mostrarCarrito();
                    actualizarContadorCarrito();
                }
            }
        });
    });
}

// En checkout, al hacer submit
function manejarCheckout() {
    const form = document.querySelector(".formulario-compra");
    if (!form) {
        console.log("No se encontró el formulario");
        return;
    }
    
    console.log("Formulario encontrado, agregando evento submit");
    
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Formulario enviado");
        
        const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
        
        if (carrito.length === 0) {
            alert("El carrito está vacío");
            return;
        }
        
        const datos = {
            nombre: form.nombre.value,
            direccion: form.direccion.value,
            email: form.email.value,
            pago: form.pago.value,
            carrito: carrito,
            fecha: new Date().toLocaleDateString('es-ES'),
            numeroPedido: generarNumeroPedido()
        };
        
        console.log("Datos del pedido:", datos);
        mostrarModalCompra(datos);
    });
}

// Función para generar número de pedido
function generarNumeroPedido() {
    const fecha = new Date();
    const año = fecha.getFullYear().toString().slice(-2);
    const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
    const dia = fecha.getDate().toString().padStart(2, '0');
    const hora = fecha.getHours().toString().padStart(2, '0');
    const minuto = fecha.getMinutes().toString().padStart(2, '0');
    const segundo = fecha.getSeconds().toString().padStart(2, '0');
    
    return `EV${año}${mes}${dia}${hora}${minuto}${segundo}`;
}

// Función para mostrar el modal de confirmación de compra
function mostrarModalCompra(datos) {
    console.log("Mostrando modal de compra");
    
    const modal = document.getElementById("modalCompra");
    const detallesCliente = document.getElementById("detallesCliente");
    
    if (!modal) {
        console.error("No se encontró el modal");
        return;
    }
    
    if (!detallesCliente) {
        console.error("No se encontró el elemento detallesCliente");
        return;
    }
    
    // Calcular total
    const total = datos.carrito.reduce((sum, producto) => sum + (producto.precio * producto.cantidad), 0);
    
    // Mostrar solo la información solicitada
    detallesCliente.innerHTML = `
        <div class="detalle-seccion">
            <h4>👤 Información del Pedido</h4>
            <p><strong>Cliente:</strong> ${datos.nombre}</p>
            <p><strong>Dirección:</strong> ${datos.direccion}</p>
            <p><strong>Fecha:</strong> ${datos.fecha}</p>
            <p><strong>Total:</strong> S/. ${total.toFixed(2)}</p>
        </div>
    `;
    
    // Mostrar modal
    modal.style.display = "block";
    console.log("Modal mostrado");
    
    // Limpiar carrito después de mostrar el modal
    localStorage.removeItem("carrito");
    actualizarContadorCarrito();
}

// Función para cerrar modal y redirigir
function cerrarModalYRedirigir() {
    const modal = document.getElementById("modalCompra");
    modal.style.display = "none";
    window.location.href = "index.html";
}

// Función para cerrar modal con la X
function cerrarModal() {
    const modal = document.getElementById("modalCompra");
    modal.style.display = "none";
}

// Ejecutar automáticamente según la página
document.addEventListener("DOMContentLoaded", () => {
    // Añadir estilos CSS para las animaciones
    const style = document.createElement("style");
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Actualizar contador del carrito en todas las páginas
    actualizarContadorCarrito();
    
    // Funciones específicas por página
    if (window.location.pathname.includes("carrito.html")) {
        mostrarCarrito();
    }
    if (window.location.pathname.includes("checkout.html")) {
        console.log("Página de checkout detectada, ejecutando manejarCheckout");
        manejarCheckout();
        
        // Agregar evento para cerrar modal con la X
        const cerrarBtn = document.querySelector(".cerrar-modal");
        if (cerrarBtn) {
            cerrarBtn.addEventListener("click", cerrarModal);
        }
        
        // Cerrar modal al hacer clic fuera de él
        const modal = document.getElementById("modalCompra");
        if (modal) {
            modal.addEventListener("click", function(e) {
                if (e.target === modal) {
                    cerrarModal();
                }
            });
        }
    }
});

// Funcionalidades principales para EcoVerde
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar todas las funcionalidades
    initAnimations();
    initScrollEffects();
    initProductCards();
    initNewsletterForm();
    initStatsCounter();
    initSmoothScrolling();
    initHeaderScroll();
    
    // Animaciones de entrada
    function initAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        
        // Observar elementos para animación
        const animateElements = document.querySelectorAll('.product-card, .stat-item, .categoria-card-modern, .feature');
        animateElements.forEach(el => {
            observer.observe(el);
        });
    }
    
    // Efectos de scroll
    function initScrollEffects() {
        let ticking = false;
        
        function updateScrollEffects() {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.hero-background');
            
            parallaxElements.forEach(element => {
                const speed = 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
            
            ticking = false;
        }
        
        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateScrollEffects);
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', requestTick);
    }
    
    // Funcionalidades de las tarjetas de productos
    function initProductCards() {
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            const addButton = card.querySelector('.btn-add-cart');
            const quickViewButton = card.querySelector('.btn-quick-view');
            
            if (addButton) {
                addButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    addToCart(card);
                });
            }
            
            if (quickViewButton) {
                quickViewButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    showQuickView(card);
                });
            }
        });
    }
    
    // Función para añadir al carrito
    function addToCart(productCard) {
        const productTitle = productCard.querySelector('.product-title').textContent;
        const productPrice = productCard.querySelector('.price-new').textContent;
        const productImage = productCard.querySelector('.product-image img').src;
        
        // Crear notificación de éxito
        showNotification(`${productTitle} añadido al carrito`, 'success');
        
        // Animación del botón
        const button = productCard.querySelector('.btn-add-cart');
        button.innerHTML = '<i class="fas fa-check"></i> Añadido';
        button.style.background = 'linear-gradient(135deg, #4caf50, #66bb6a)';
        
        setTimeout(() => {
            button.innerHTML = '<i class="fas fa-shopping-cart"></i> Añadir al carrito';
            button.style.background = 'linear-gradient(135deg, #2e7d32, #4caf50)';
        }, 2000);
        
        // Aquí podrías integrar con tu sistema de carrito
        console.log('Producto añadido:', { title: productTitle, price: productPrice, image: productImage });
    }
    
    // Función para vista rápida
    function showQuickView(productCard) {
        const productTitle = productCard.querySelector('.product-title').textContent;
        const productPrice = productCard.querySelector('.price-new').textContent;
        const productImage = productCard.querySelector('.product-image img').src;
        const productRating = productCard.querySelector('.product-rating').innerHTML;
        
        // Crear modal de vista rápida
        const modal = document.createElement('div');
        modal.className = 'quick-view-modal';
        modal.innerHTML = `
            <div class="modal-overlay">
                <div class="modal-content">
                    <button class="modal-close">&times;</button>
                    <div class="modal-body">
                        <div class="modal-image">
                            <img src="${productImage}" alt="${productTitle}">
                        </div>
                        <div class="modal-info">
                            <h3>${productTitle}</h3>
                            <div class="modal-rating">${productRating}</div>
                            <div class="modal-price">${productPrice}</div>
                            <p>Descripción del producto y características principales...</p>
                            <button class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-cart"></i> Añadir al carrito
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Cerrar modal
        const closeButton = modal.querySelector('.modal-close');
        const overlay = modal.querySelector('.modal-overlay');
        
        closeButton.addEventListener('click', () => {
            modal.remove();
        });
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                modal.remove();
            }
        });
        
        // Añadir estilos del modal
        addModalStyles();
    }
    
    // Estilos para el modal
    function addModalStyles() {
        if (!document.getElementById('modal-styles')) {
            const styles = document.createElement('style');
            styles.id = 'modal-styles';
            styles.textContent = `
                .quick-view-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 10000;
                    animation: fadeIn 0.3s ease;
                }
                
                .modal-overlay {
                    background: rgba(0, 0, 0, 0.8);
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1rem;
                }
                
                .modal-content {
                    background: white;
                    border-radius: 15px;
                    max-width: 800px;
                    width: 100%;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    animation: slideUp 0.3s ease;
                }
                
                .modal-close {
                    position: absolute;
                    top: 1rem;
                    right: 1rem;
                    background: none;
                    border: none;
                    font-size: 2rem;
                    cursor: pointer;
                    color: #666;
                    z-index: 1;
                }
                
                .modal-body {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 2rem;
                    padding: 2rem;
                }
                
                .modal-image img {
                    width: 100%;
                    height: 300px;
                    object-fit: cover;
                    border-radius: 10px;
                }
                
                .modal-info h3 {
                    color: #2e7d32;
                    margin-bottom: 1rem;
                }
                
                .modal-rating {
                    margin-bottom: 1rem;
                }
                
                .modal-price {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2e7d32;
                    margin-bottom: 1rem;
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes slideUp {
                    from { transform: translateY(50px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                
                @media (max-width: 768px) {
                    .modal-body {
                        grid-template-columns: 1fr;
                        gap: 1rem;
                    }
                }
            `;
            document.head.appendChild(styles);
        }
    }
    
    // Formulario de newsletter
    function initNewsletterForm() {
        const newsletterForm = document.querySelector('.newsletter-form');
        
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const email = this.querySelector('input[type="email"]').value;
                
                if (email) {
                    // Simular envío
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton.innerHTML;
                    
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                    submitButton.disabled = true;
                    
                    setTimeout(() => {
                        showNotification('¡Gracias por suscribirte! Te enviaremos las mejores ofertas.', 'success');
                        this.reset();
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;
                    }, 2000);
                }
            });
        }
    }
    
    // Contador de estadísticas
    function initStatsCounter() {
        const statNumbers = document.querySelectorAll('.stat-number');
        
        const observerOptions = {
            threshold: 0.5
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        statNumbers.forEach(stat => {
            observer.observe(stat);
        });
    }
    
    function animateCounter(element) {
        const target = element.textContent;
        const isDecimal = target.includes('.');
        const finalValue = parseFloat(target.replace(/[^\d.]/g, ''));
        const suffix = target.replace(/[\d.]/g, '');
        
        let currentValue = 0;
        const increment = finalValue / 50;
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            
            if (isDecimal) {
                element.textContent = currentValue.toFixed(1) + suffix;
            } else {
                element.textContent = Math.floor(currentValue) + suffix;
            }
        }, 50);
    }
    
    // Scroll suave
    function initSmoothScrolling() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    const headerHeight = document.querySelector('.header-modern').offsetHeight;
                    const targetPosition = targetElement.offsetTop - headerHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }
    
    // Header con efecto de scroll
    function initHeaderScroll() {
        const header = document.querySelector('.header-modern');
        let lastScroll = 0;
        
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                header.style.background = 'rgba(46, 125, 50, 0.95)';
                header.style.backdropFilter = 'blur(10px)';
            } else {
                header.style.background = 'linear-gradient(135deg, #2e7d32 0%, #388e3c 100%)';
                header.style.backdropFilter = 'none';
            }
            
            if (currentScroll > lastScroll && currentScroll > 200) {
                header.style.transform = 'translateY(-100%)';
            } else {
                header.style.transform = 'translateY(0)';
            }
            
            lastScroll = currentScroll;
        });
    }
    
    // Sistema de notificaciones
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        // Añadir estilos de notificación si no existen
        if (!document.getElementById('notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'notification-styles';
            styles.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
                    z-index: 10001;
                    animation: slideInRight 0.3s ease;
                    max-width: 400px;
                }
                
                .notification-success {
                    border-left: 4px solid #4caf50;
                }
                
                .notification-info {
                    border-left: 4px solid #2196f3;
                }
                
                .notification-content {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    padding: 1rem;
                }
                
                .notification-content i {
                    color: #4caf50;
                    font-size: 1.2rem;
                }
                
                .notification-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #666;
                    margin-left: auto;
                }
                
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(styles);
        }
        
        document.body.appendChild(notification);
        
        // Cerrar notificación
        const closeButton = notification.querySelector('.notification-close');
        closeButton.addEventListener('click', () => {
            notification.remove();
        });
        
        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    // Añadir estilos de animación para elementos
    if (!document.getElementById('animation-styles')) {
        const styles = document.createElement('style');
        styles.id = 'animation-styles';
        styles.textContent = `
            .product-card, .stat-item, .categoria-card-modern, .feature {
                opacity: 0;
                transform: translateY(30px);
                transition: all 0.6s ease;
            }
            
            .animate-in {
                opacity: 1;
                transform: translateY(0);
            }
            
            .product-card:nth-child(1) { transition-delay: 0.1s; }
            .product-card:nth-child(2) { transition-delay: 0.2s; }
            .product-card:nth-child(3) { transition-delay: 0.3s; }
            
            .stat-item:nth-child(1) { transition-delay: 0.1s; }
            .stat-item:nth-child(2) { transition-delay: 0.2s; }
            .stat-item:nth-child(3) { transition-delay: 0.3s; }
            .stat-item:nth-child(4) { transition-delay: 0.4s; }
        `;
        document.head.appendChild(styles);
    }
    
    // Lazy loading para imágenes
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Preloader
    window.addEventListener('load', () => {
        const preloader = document.querySelector('.preloader');
        if (preloader) {
            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 300);
        }
    });
    
    console.log('EcoVerde - Funcionalidades cargadas correctamente');
});

// Funciones utilitarias
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}
