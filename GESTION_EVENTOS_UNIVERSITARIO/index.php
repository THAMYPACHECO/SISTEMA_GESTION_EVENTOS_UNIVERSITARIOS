<?php
/**
 * index.php - Página de inicio pública
 * Sistema de Gestión de Eventos Universitarios
 */

session_start();

// Si hay sesión activa, redirigir al dashboard según rol
if (isset($_SESSION['user_id']) && isset($_SESSION['rol_id'])) {
    switch ((int)$_SESSION['rol_id']) {
        case 1:
            header('Location: admin/dashboard.php');
            exit;
        case 2:
            header('Location: organizador/dashboard.php');
            exit;
        case 3:
            header('Location: participante/dashboard.php');
            exit;
        default:
            header('Location: auth/logout.php');
            exit;
    }
}

// Nombre del sistema
$app_name    = 'GEU - Gestión de Eventos Universitarios';
$universidad = 'Universidad Nacional';
$year        = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión de Eventos Universitarios - Administra, inscríbete y certifica tu participación en eventos académicos.">
    <title><?= htmlspecialchars($app_name) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Hoja de estilos principal -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-page">

    <!-- ========== NAVBAR ========== -->
    <nav class="landing-nav">
        <div class="nav-brand">
            <span>🎓</span>
            <div>
                <div><?= htmlspecialchars($app_name) ?></div>
            </div>
        </div>
        <div class="nav-links">
            <a href="auth/login.php"    class="btn btn-outline-primary btn-sm" style="border-color:rgba(255,255,255,.6);color:#fff;">Iniciar Sesión</a>
            <a href="auth/register.php" class="btn btn-warning btn-sm">Registrarse</a>
        </div>
    </nav>

    <!-- ========== HERO ========== -->
    <section class="hero">
        <!-- Formas decorativas -->
        <div class="hero-shape hero-shape-1"></div>
        <div class="hero-shape hero-shape-2"></div>

        <div class="hero-content">
            <span class="hero-eyebrow">🏛️ <?= htmlspecialchars($universidad) ?></span>

            <h1>
                Gestiona tus <span class="accent">Eventos Universitarios</span>
                de forma fácil y eficiente
            </h1>

            <p>
                Plataforma integral para la organización, inscripción y certificación
                de eventos académicos. Conecta organizadores con participantes y
                genera certificados de asistencia automáticamente.
            </p>

            <div class="hero-actions">
                <a href="auth/login.php"    class="btn btn-hero-primary">🔐 Iniciar Sesión</a>
                <a href="auth/register.php" class="btn btn-hero-outline">✏️ Registrarse Gratis</a>
            </div>
        </div>
    </section>

    <!-- ========== FEATURES ========== -->
    <section class="features" id="caracteristicas">
        <div class="section-header">
            <h2>¿Qué puedes hacer?</h2>
            <p>Una plataforma completa para toda la comunidad universitaria, diseñada para simplificar la gestión de eventos académicos.</p>
        </div>

        <div class="features-grid">
            <!-- Card 1: Gestión de Eventos -->
            <div class="feature-card">
                <span class="feature-icon">📅</span>
                <h3>Gestión de Eventos</h3>
                <p>
                    Crea, programa y administra eventos universitarios: conferencias, talleres,
                    seminarios, congresos y más. Control total de cupos, fechas y modalidad
                    (presencial, virtual o híbrida).
                </p>
            </div>

            <!-- Card 2: Inscripciones -->
            <div class="feature-card">
                <span class="feature-icon">📋</span>
                <h3>Inscripciones en Línea</h3>
                <p>
                    Los participantes pueden inscribirse a eventos disponibles en segundos,
                    consultar su historial de inscripciones, cancelar si es necesario
                    y llevar seguimiento de su asistencia.
                </p>
            </div>

            <!-- Card 3: Certificados -->
            <div class="feature-card">
                <span class="feature-icon">🏆</span>
                <h3>Certificados Digitales</h3>
                <p>
                    Genera y descarga certificados de participación automáticamente
                    al finalizar el evento. Cada certificado incluye un código único
                    de verificación y está listo para imprimir.
                </p>
            </div>

            <!-- Card 4: Roles y Perfiles -->
            <div class="feature-card">
                <span class="feature-icon">👥</span>
                <h3>Roles Diferenciados</h3>
                <p>
                    Tres perfiles de usuario: <strong>Administrador</strong> con control total,
                    <strong>Organizador</strong> para gestionar sus propios eventos y
                    <strong>Participante</strong> para inscribirse y obtener certificados.
                </p>
            </div>

            <!-- Card 5: Reportes -->
            <div class="feature-card">
                <span class="feature-icon">📊</span>
                <h3>Reportes y Estadísticas</h3>
                <p>
                    Visualiza estadísticas de asistencia, inscripciones por período,
                    eventos más populares y mucho más mediante gráficas interactivas
                    en el panel de administración.
                </p>
            </div>

            <!-- Card 6: Responsive -->
            <div class="feature-card">
                <span class="feature-icon">📱</span>
                <h3>100% Responsive</h3>
                <p>
                    Accede desde cualquier dispositivo: computadora, tablet o teléfono móvil.
                    La plataforma se adapta perfectamente a cualquier tamaño de pantalla
                    para una experiencia óptima.
                </p>
            </div>
        </div>
    </section>

    <!-- ========== CTA SECTION ========== -->
    <section style="background:linear-gradient(135deg,var(--color-primary),#3949ab);padding:4rem 2rem;text-align:center;">
        <div style="max-width:600px;margin:0 auto;">
            <h2 style="color:#fff;font-size:1.8rem;margin-bottom:.75rem;">¿Listo para comenzar?</h2>
            <p style="color:rgba(255,255,255,.85);margin-bottom:2rem;">
                Únete a la comunidad universitaria y no te pierdas ningún evento académico.
                El registro es gratuito y solo toma un minuto.
            </p>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <a href="auth/register.php" class="btn btn-hero-primary" style="border-radius:30px;padding:.8rem 2.2rem;font-size:1rem;">
                    ✏️ Crear cuenta gratuita
                </a>
                <a href="auth/login.php" class="btn btn-hero-outline" style="border-radius:30px;padding:.8rem 2.2rem;font-size:1rem;">
                    🔐 Ya tengo cuenta
                </a>
            </div>
        </div>
    </section>

    <!-- ========== FOOTER ========== -->
    <footer class="landing-footer">
        <div class="landing-footer-grid">
            <!-- Columna Marca -->
            <div class="footer-brand">
                <h3>🎓 <?= htmlspecialchars($app_name) ?></h3>
                <p>
                    Sistema desarrollado para la gestión integral de eventos académicos
                    de la comunidad universitaria. Facilita la organización, inscripción
                    y certificación de eventos en un solo lugar.
                </p>
            </div>

            <!-- Columna Accesos -->
            <div class="footer-col">
                <h4>Acceso Rápido</h4>
                <a href="auth/login.php">🔐 Iniciar Sesión</a>
                <a href="auth/register.php">✏️ Registrarse</a>
                <a href="#caracteristicas">📋 Características</a>
            </div>

            <!-- Columna Universidad -->
            <div class="footer-col">
                <h4>Universidad</h4>
                <a href="#">🏛️ <?= htmlspecialchars($universidad) ?></a>
                <a href="#">📞 Contacto</a>
                <a href="#">📍 Ubicación</a>
                <a href="#">🌐 Sitio Web</a>
            </div>
        </div>

        <div class="landing-footer-bottom">
            <p>
                &copy; <?= $year ?> <?= htmlspecialchars($universidad) ?> &mdash;
                <?= htmlspecialchars($app_name) ?>. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <!-- JavaScript principal -->
    <script src="assets/js/main.js"></script>
</body>
</html>
