<?php
// Inicia una sesión segura si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // Establece la duración de la cookie de sesión a 24 horas
        'cookie_secure' => true, // Cambiar a true en producción para HTTPS, asegura que la cookie solo se envíe por conexiones seguras
        'cookie_httponly' => true, // Previene acceso a la cookie desde JavaScript, aumentando la seguridad
        'use_strict_mode' => true // Activa el modo estricto para prevenir ataques de fijación de sesión
    ]);

    // Verifica si la sesión es nueva o ha expirado (1800 segundos = 30 minutos)
    if (!isset($_SESSION['CREATED'])) {
        $_SESSION['CREATED'] = time(); // Registra el momento de creación de la sesión
    } elseif (time() - $_SESSION['CREATED'] > 1800) {
        session_regenerate_id(true); // Regenera el ID de la sesión para mitigar ataques de fijación
        $_SESSION['CREATED'] = time(); // Actualiza el tiempo de creación
    }
}

// Registra el contenido de la sesión en el log de errores para depuración
error_log("Datos de sesión: " . print_r($_SESSION, true));

// Define roles permitidos para acceder a la eliminación de profesores
$allowed_roles = ['administrador', 'jefe', 'coordinador', 'admin'];
// Obtiene el rol del usuario actual en minúsculas, o cadena vacía si no está definido
$current_role = strtolower($_SESSION['rol'] ?? '');

// Verifica si el usuario está autenticado y tiene un rol permitido
if (empty($_SESSION['usuario_id']) || !in_array($current_role, $allowed_roles)) {
    $_SESSION['mensaje'] = "Acceso denegado: No tienes permisos para eliminar profesores."; // Mensaje de error
    $_SESSION['mensaje_tipo'] = "error"; // Tipo de mensaje
    header("Location: add_user.php"); // Redirige a la página de gestión de usuarios
    exit; // Termina la ejecución del script
}

// Incluye el archivo de conexión a la base de datos
require_once 'db.php';

// Obtiene el ID del profesor desde la URL, o null si no está presente
$id_profesor = $_GET['id'] ?? null;

// Verifica si se proporcionó un ID de profesor
if (!$id_profesor) {
    $_SESSION['mensaje'] = "ID de profesor no proporcionado."; // Mensaje de error
    $_SESSION['mensaje_tipo'] = "error"; // Tipo de mensaje
    header("Location: add_user.php"); // Redirige a la página de gestión de usuarios
    exit; // Termina la ejecución del script
}

try {
    // Inicia una transacción para asegurar la integridad de las operaciones en la base de datos
    $conn->begin_transaction();

    // Prepara una consulta para obtener las imágenes de los documentos asociados al profesor
    $stmt = $conn->prepare("SELECT imagen FROM documentos WHERE id_profesor = ?");
    $stmt->bind_param("i", $id_profesor); // Vincula el ID del profesor como entero
    $stmt->execute(); // Ejecuta la consulta
    $result = $stmt->get_result(); // Obtiene el resultado

    // Define el directorio donde están los archivos subidos
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/CRUD/uploads/';
    $files_to_delete = []; // Arreglo para almacenar las rutas de los archivos a eliminar

    // Recorre los documentos para recolectar las rutas de las imágenes
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['imagen'])) {
            $files_to_delete[] = $uploadDir . $row['imagen']; // Agrega la ruta del archivo al arreglo
        }
    }

    // Prepara y ejecuta una consulta para eliminar los documentos asociados al profesor
    $stmt = $conn->prepare("DELETE FROM documentos WHERE id_profesor = ?");
    $stmt->bind_param("i", $id_profesor); // Vincula el ID del profesor
    if (!$stmt->execute()) { // Ejecuta la consulta
        throw new Exception("Error al eliminar documentos asociados."); // Lanza una excepción si falla
    }

    // Prepara y ejecuta una consulta para eliminar la formación asociada al profesor
    $stmt = $conn->prepare("DELETE FROM formacion WHERE id_profesor = ?");
    $stmt->bind_param("i", $id_profesor); // Vincula el ID del profesor
    if (!$stmt->execute()) { // Ejecuta la consulta
        throw new Exception("Error al eliminar formación asociada."); // Lanza una excepción si falla
    }

    // Prepara y ejecuta una consulta para eliminar al profesor de la base de datos
    $stmt = $conn->prepare("DELETE FROM profesores WHERE id_profesor = ?");
    $stmt->bind_param("i", $id_profesor); // Vincula el ID del profesor
    if (!$stmt->execute()) { // Ejecuta la consulta
        throw new Exception("Error al eliminar el profesor."); // Lanza una excepción si falla
    }

    // Intenta eliminar los archivos físicos del servidor
    foreach ($files_to_delete as $file_path) {
        if (file_exists($file_path) && !unlink($file_path)) {
            error_log("Error eliminando archivo: " . $file_path); // Registra un error si no se puede eliminar el archivo
        }
    }

    // Confirma la transacción si todas las operaciones fueron exitosas
    $conn->commit();

    // Establece un mensaje de éxito para mostrar al usuario
    $_SESSION['mensaje'] = "Profesor eliminado correctamente.";
    $_SESSION['mensaje_tipo'] = "success";
    header("Location: add_user.php"); // Redirige a la página de gestión de usuarios
    exit; // Termina la ejecución del script

} catch (Exception $e) {
    // Revierte la transacción en caso de error para mantener la integridad de la base de datos
    $conn->rollback();
    // Establece un mensaje de error basado en la excepción
    $_SESSION['mensaje'] = $e->getMessage();
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: add_user.php"); // Redirige a la página de gestión de usuarios
    exit; // Termina la ejecución del script
}
?>