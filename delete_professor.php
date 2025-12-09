<?php
// Inicia una sesión segura si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Define roles permitidos para acceder a la eliminación de profesores
$allowed_roles = ['administrador', 'jefe', 'coordinador', 'admin'];
$current_role = strtolower($_SESSION['rol'] ?? '');

// Verifica si el usuario está autenticado y tiene un rol permitido
if (empty($_SESSION['usuario_id']) || !in_array($current_role, $allowed_roles)) {
    $_SESSION['mensaje'] = "Acceso denegado: No tienes permisos para eliminar profesores.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: add_user.php");
    exit;
}

require_once 'db.php'; 
// ---------------------------------------------------

// Obtiene el ID del profesor desde la URL
$id_profesor = $_GET['id'] ?? null;

// Verifica si se proporcionó un ID de profesor
if (!$id_profesor) {
    $_SESSION['mensaje'] = "ID de profesor no proporcionado.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: add_user.php");
    exit;
}

try {
    // Inicia una transacción
    $conn->begin_transaction();

    // 1. Obtener imágenes para borrarlas después (antes de borrar los registros)
    $stmt = $conn->prepare("SELECT imagen FROM documentos WHERE id_profesor = ?");
    $stmt->bind_param("i", $id_profesor);
    $stmt->execute();
    $result = $stmt->get_result();

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/CRUD/uploads/'; // Asegúrate que esta ruta sea correcta para tu servidor
    $files_to_delete = [];

    while ($row = $result->fetch_assoc()) {
        if (!empty($row['imagen'])) {
            $files_to_delete[] = $uploadDir . $row['imagen'];
        }
    }

    // 2. Eliminar documentos asociados
    $stmt = $conn->prepare("DELETE FROM documentos WHERE id_profesor = ?");
    $stmt->bind_param("i", $id_profesor);
    if (!$stmt->execute()) throw new Exception("Error al eliminar documentos asociados.");

    // 3. Eliminar formación asociada
    $stmt = $conn->prepare("DELETE FROM formacion WHERE id_profesor = ?");
    $stmt->bind_param("i", $id_profesor);
    if (!$stmt->execute()) throw new Exception("Error al eliminar formación asociada.");

    // 4. Desvincular usuarios (ESTA ES LA CORRECCIÓN CLAVE)
    // Pone en NULL el campo id_profesor en la tabla usuarios para evitar el error de restricción
    $stmt = $conn->prepare("UPDATE usuarios SET id_profesor = NULL WHERE id_profesor = ?");
    $stmt->bind_param("i", $id_profesor);
    if (!$stmt->execute()) throw new Exception("Error al desvincular usuarios asociados.");

    // 5. Eliminar al profesor
    $stmt = $conn->prepare("DELETE FROM profesores WHERE id_profesor = ?");
    $stmt->bind_param("i", $id_profesor);
    if (!$stmt->execute()) throw new Exception("Error al eliminar el profesor.");

    // 6. Eliminar archivos físicos del servidor
    foreach ($files_to_delete as $file_path) {
        if (file_exists($file_path) && !unlink($file_path)) {
            error_log("Error eliminando archivo: " . $file_path);
        }
    }

    // Si todo salió bien, confirma los cambios
    $conn->commit();

    $_SESSION['mensaje'] = "Profesor eliminado correctamente.";
    $_SESSION['mensaje_tipo'] = "success";
    header("Location: add_user.php");
    exit;

} catch (Exception $e) {
    // Si algo falla, revierte todos los cambios de la base de datos
    $conn->rollback();
    $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: add_user.php");
    exit;
}
?>