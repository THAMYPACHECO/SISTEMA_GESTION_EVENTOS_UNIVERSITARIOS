<?php
/**
 * 404.php - Página de error "No encontrada"
 * Sistema de Gestión de Eventos Universitarios
 */

http_response_code(404);

$app_name = 'GEU - Gestión de Eventos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada | <?= htmlspecialchars($app_name) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Hoja de estilos -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="error-page">
        <div class="error-box">
            <!-- Número de error grande con gradiente -->
            <div class="error-code">404</div>

            <h2>¡Página no encontrada!</h2>
            <p>
                Lo sentimos, la página que buscas no existe, fue movida
                o no está disponible en este momento.
            </p>

            <!-- Acciones -->
            <div style="display:flex;flex-direction:column;gap:.75rem;align-items:center;">
                <a href="index.php" class="btn btn-primary btn-block" style="max-width:280px;">
                    🏠 Volver al inicio
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary btn-block" style="max-width:280px;">
                    ← Página anterior
                </a>
            </div>

            <!-- Info extra -->
            <p style="margin-top:1.75rem;font-size:.8rem;color:#9e9e9e;">
                Si crees que esto es un error, contacta al administrador del sistema.<br>
                <strong style="color:#1a237e;"><?= htmlspecialchars($app_name) ?></strong>
            </p>
        </div>
    </div>

</body>
</html>
