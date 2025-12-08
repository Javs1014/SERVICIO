<?php
// Incluye el archivo de conexión a la base de datos, que establece la conexión con MySQL
include 'db.php';

// ==============================================
// CONFIGURACIÓN DE LA SUBIDA DE ARCHIVOS
// ==============================================

// Define la ruta del directorio donde se almacenarán los archivos subidos
// $_SERVER['DOCUMENT_ROOT'] proporciona la ruta raíz del servidor web
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/CrudXamp/uploads/';

// Verifica si el directorio de uploads existe; si no, lo crea
// 0777 otorga permisos completos (lectura, escritura, ejecución) para todos los usuarios
// El parámetro true permite crear directorios anidados si es necesario
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true); // Crea el directorio con los permisos especificados
}

// Inicializa un arreglo de respuesta con valores por defecto para manejar el resultado
$response = [
    'status' => 'error', // Estado inicial de la operación (error por defecto)
    'message' => 'Error desconocido' // Mensaje inicial en caso de fallo no identificado
];

// ==============================================
// VALIDACIÓN DE CAMPOS REQUERIDOS
// ==============================================

// Define los campos obligatorios que deben estar presentes en la solicitud POST
$requiredFields = ['id_profesor', 'tipo_documento'];

// Itera sobre cada campo requerido para verificar su presencia
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        // Si un campo está vacío, actualiza el mensaje de error
        $response['message'] = "El campo $field es requerido";
        // Envía la respuesta como JSON y termina la ejecución del script
        echo json_encode($response);
        exit;
    }
}

// ==============================================
// SANITIZACIÓN DE DATOS
// ==============================================

// Convierte id_profesor a entero para prevenir inyecciones y asegurar tipo correcto
$id_profesor = intval($_POST['id_profesor']);

// Sanitiza el tipo de documento para prevenir ataques XSS (Cross-Site Scripting)
$tipo_documento = htmlspecialchars($_POST['tipo_documento'], ENT_QUOTES, 'UTF-8');

// Maneja el caso especial cuando el tipo de documento es "otros"
if ($tipo_documento === 'otros' && !empty($_POST['otros_tipo'])) {
    // Combina el prefijo "otros_" con el subtipo seleccionado, también sanitizado
    $tipo_documento = 'otros_' . htmlspecialchars($_POST['otros_tipo'], ENT_QUOTES, 'UTF-8');
}

// Sanitiza los campos académicos, asignando null si no están presentes (para documentos tipo "otros")
$periodo = isset($_POST['periodo']) ? htmlspecialchars($_POST['periodo'], ENT_QUOTES, 'UTF-8') : null;
$anio = isset($_POST['anio']) ? intval($_POST['anio']) : null; // Convierte a entero si existe
$clave_materia = isset($_POST['clave_materia']) ? htmlspecialchars($_POST['clave_materia'], ENT_QUOTES, 'UTF-8') : null;
$grupo = isset($_POST['grupo']) ? htmlspecialchars($_POST['grupo'], ENT_QUOTES, 'UTF-8') : null;

// ==============================================
// PROCESAMIENTO DEL ARCHIVO SUBIDO
// ==============================================

// Verifica si se subió un archivo y si no hubo errores en la subida
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    
    // Define los tipos MIME permitidos para las imágenes
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Obtiene el tipo MIME real del archivo usando mime_content_type para mayor seguridad
    $fileType = mime_content_type($_FILES['imagen']['tmp_name']);
    
    // Valida que el tipo de archivo esté en la lista de tipos permitidos
    if (!in_array($fileType, $allowedTypes)) {
        $response['message'] = 'Tipo de archivo no permitido';
        // Envía la respuesta JSON y termina la ejecución
        echo json_encode($response);
        exit;
    }

    // Valida el tamaño del archivo (máximo 5MB, convertido a bytes)
    if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
        $response['message'] = 'El archivo es demasiado grande (máximo 5MB)';
        // Envía la respuesta JSON y termina la ejecución
        echo json_encode($response);
        exit;
    }

    // ==============================================
    // OBTENER NOMBRE DEL PROFESOR PARA EL NOMBRE DEL ARCHIVO
    // ==============================================
    
    // Prepara una consulta para obtener el nombre del profesor asociado al id_profesor
    $stmt = $conn->prepare("SELECT nombre FROM profesores WHERE id_profesor = ?");
    // Vincula el parámetro id_profesor como entero
    $stmt->bind_param("i", $id_profesor);
    $stmt->execute(); // Ejecuta la consulta
    $result = $stmt->get_result(); // Obtiene el resultado
    
    // Procesa el resultado de la consulta
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Reemplaza espacios en el nombre del profesor con guiones bajos para un nombre de archivo válido
        $nombreProfesor = preg_replace('/\s+/', '_', $row['nombre']);
    } else {
        // Si no se encuentra el profesor, usa un valor por defecto
        $nombreProfesor = 'Desconocido';
    }
    $stmt->close(); // Cierra el statement para liberar recursos

    // ==============================================
    // GENERAR NOMBRE ÚNICO PARA EL ARCHIVO
    // ==============================================
    
    // Obtiene la extensión del archivo original (en minúsculas)
    $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    
    // Crea un nombre descriptivo para el archivo usando los datos proporcionados
    $nombreImagen = sprintf("%s_%s_%d_%s_%s_%s.%s",
        $tipo_documento,               // Tipo de documento (ej. acta, horario, otros_licenciatura)
        $periodo ?? 'NA',              // Periodo académico o 'NA' si no aplica
        $anio ?? '0000',               // Año o '0000' si no aplica
        $nombreProfesor,               // Nombre del profesor (sin espacios)
        $clave_materia ?? 'NA',        // Clave de materia o 'NA' si no aplica
        $grupo ?? 'NA',                // Grupo o 'NA' si no aplica
        $extension                     // Extensión del archivo (jpg, png, etc.)
    );
    
    // Define la ruta completa donde se guardará el archivo
    $rutaImagen = $uploadDir . $nombreImagen;

    // ==============================================
    // MOVER ARCHIVO Y GUARDAR EN BASE DE DATOS
    // ==============================================
    
    // Intenta mover el archivo temporal a su ubicación final en el servidor
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaImagen)) {
        
        // Prepara una consulta para insertar los datos del documento en la base de datos
        $stmt = $conn->prepare("INSERT INTO documentos 
            (id_profesor, tipo_documento, periodo, anio, clave_materia, grupo, imagen) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // Vincula los parámetros a la consulta preparada
        // Tipos: i=entero, s=string
        $stmt->bind_param("ississs", 
            $id_profesor,     // ID del profesor (entero)
            $tipo_documento,  // Tipo de documento (string)
            $periodo,         // Periodo académico (string, puede ser null)
            $anio,            // Año (entero, puede ser null)
            $clave_materia,   // Clave de materia (string, puede ser null)
            $grupo,           // Grupo (string, puede ser null)
            $nombreImagen     // Nombre del archivo guardado (string)
        );
        
        // Ejecuta la consulta para insertar el registro
        if ($stmt->execute()) {
            // Si la inserción es exitosa, actualiza la respuesta
            $response['status'] = 'success';
            $response['message'] = '✅ Documento cargado correctamente.';
        } else {
            // Si falla la inserción, incluye el error de MySQL en el mensaje
            $response['message'] = '❌ Error al guardar en la base de datos: ' . $stmt->error;
        }
        $stmt->close(); // Cierra el statement
    } else {
        // Si falla el movimiento del archivo, actualiza el mensaje de error
        $response['message'] = '❌ Error al mover el archivo subido.';
    }
} else {
    // Si no se recibió un archivo o hubo un error en la subida
    $response['message'] = '⚠️ No se recibió ningún archivo o hubo un error en la subida.';
    // Incluye el código de error de subida si está disponible
    $response['upload_error'] = $_FILES['imagen']['error'] ?? 'No hay archivo';
}

// Cierra la conexión a la base de datos para liberar recursos
$conn->close();

// Establece la cabecera HTTP para indicar que la respuesta es JSON
header('Content-Type: application/json');

// Codifica la respuesta como JSON y la envía al cliente
echo json_encode($response);

// Termina la ejecución del script
exit;
?>