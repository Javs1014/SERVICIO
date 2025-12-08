<?php
// Inicia una sesión segura si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // Establece la duración de la cookie de sesión a 24 horas
        'cookie_secure' => false, // Cambiar a true en producción para HTTPS, asegura que la cookie solo se envíe por conexiones seguras
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

// Define roles permitidos para acceder a la gestión de usuarios y verifica el rol del usuario actual
$allowed_roles = ['administrador', 'jefe', 'coordinador', 'admin'];
$current_role = strtolower($_SESSION['rol'] ?? '');
$is_logged_in = isset($_SESSION['usuario_id']);
$is_authorized = $is_logged_in && in_array($current_role, $allowed_roles);

// Verifica si el usuario está autenticado y tiene un rol permitido
if (empty($_SESSION['usuario_id']) || !in_array($current_role, $allowed_roles)) {
    $_SESSION['mensaje'] = "Acceso denegado: No tienes permisos para estar aquí.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: index.php"); // Redirige al inicio si no tiene permisos
    exit; // Termina la ejecución del script
}

// Incluye el archivo de conexión a la base de datos
require_once 'db.php';

// Obtiene la lista de departamentos para el formulario de profesores
$departamentos = [];
$query_dept = "SELECT id_departamento, nombre FROM departamento";
$result_dept = $conn->query($query_dept); // Ejecuta la consulta
if ($result_dept) {
    while ($row = $result_dept->fetch_assoc()) { // Recorre los resultados
        $departamentos[] = $row; // Almacena cada departamento en el arreglo
    }
}

// Obtiene la lista de todos los usuarios
$usuarios = [];
$query_users = "SELECT id_usuario, usuario, rol FROM usuarios";
$result_users = $conn->query($query_users); // Ejecuta la consulta
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) { // Recorre los resultados
        $usuarios[] = $row; // Almacena cada usuario en el arreglo
    }
}

// Obtiene la lista de todos los profesores con su departamento
$profesores = [];
$query_prof = "SELECT p.id_profesor, p.nombre, p.anio_ingreso, d.nombre AS departamento 
               FROM profesores p 
               LEFT JOIN departamento d ON p.id_departamento = d.id_departamento 
               ORDER BY p.nombre";
$result_prof = $conn->query($query_prof); // Ejecuta la consulta
if ($result_prof) {
    while ($row = $result_prof->fetch_assoc()) { // Recorre los resultados
        $profesores[] = $row; // Almacena cada profesor en el arreglo
    }
}

// Procesa el formulario para agregar un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $usuario = trim($_POST['usuario'] ?? ''); // Obtiene y limpia el nombre de usuario
    $contrasena = trim($_POST['contrasena'] ?? ''); // Obtiene y limpia la contraseña
    $rol = trim($_POST['rol'] ?? ''); // Obtiene y limpia el rol

    // Validación de los campos del formulario
    if (empty($usuario) || empty($contrasena) || empty($rol)) {
        $_SESSION['mensaje'] = "Todos los campos son obligatorios.";
        $_SESSION['mensaje_tipo'] = "error";
    } elseif (!in_array($rol, ['Administrador', 'Jefe', 'Coordinador', 'Visitante'])) {
        $_SESSION['mensaje'] = "Rol inválido.";
        $_SESSION['mensaje_tipo'] = "error";
    } else {
        try {
            // Verifica si el usuario ya existe en la base de datos
            $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("El usuario ya existe.");
            }

            // Hashea la contraseña para almacenarla de forma segura
            $contrasena_hash = password_hash($contrasena, PASSWORD_BCRYPT);

            // Inserta el nuevo usuario en la base de datos
            $stmt = $conn->prepare("INSERT INTO usuarios (usuario, contraseña, rol) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $usuario, $contrasena_hash, $rol);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Usuario agregado correctamente.";
                $_SESSION['mensaje_tipo'] = "success";
            } else {
                throw new Exception("Error al agregar el usuario.");
            }
        } catch (Exception $e) {
            $_SESSION['mensaje'] = $e->getMessage();
            $_SESSION['mensaje_tipo'] = "error";
        }
    }
    header("Location: add_user.php"); // Redirige a la misma página
    exit;
}

// Procesa el formulario para agregar un nuevo profesor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_professor'])) {
    $nombre = trim($_POST['nombre'] ?? ''); // Obtiene y limpia el nombre
    $id_departamento = !empty($_POST['id_departamento']) ? (int)$_POST['id_departamento'] : null; // Obtiene el departamento (opcional)
    $anio_ingreso = !empty($_POST['anio_ingreso']) ? (int)$_POST['anio_ingreso'] : null; // Obtiene el año de ingreso (opcional)

    // Validación de los campos del formulario
    if (empty($nombre)) {
        $_SESSION['mensaje'] = "El nombre del profesor es obligatorio.";
        $_SESSION['mensaje_tipo'] = "error";
    } elseif ($anio_ingreso !== null && ($anio_ingreso < 1900 || $anio_ingreso > date('Y'))) {
        $_SESSION['mensaje'] = "El año de ingreso debe estar entre 1900 y " . date('Y') . ".";
        $_SESSION['mensaje_tipo'] = "error";
    } else {
        try {
            // Inserta el nuevo profesor en la base de datos
            $stmt = $conn->prepare("INSERT INTO profesores (nombre, id_departamento, anio_ingreso) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $nombre, $id_departamento, $anio_ingreso);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Profesor agregado correctamente.";
                $_SESSION['mensaje_tipo'] = "success";
            } else {
                throw new Exception("Error al agregar el profesor.");
            }
        } catch (Exception $e) {
            $_SESSION['mensaje'] = $e->getMessage();
            $_SESSION['mensaje_tipo'] = "error";
        }
    }
    header("Location: add_user.php"); // Redirige a la misma página
    exit;
}

// Obtiene la lista de todas las materias
$materias = [];
$query_materias = "SELECT clave_materia, nombre FROM materias ORDER BY clave_materia";
$result_materias = $conn->query($query_materias); // Ejecuta la consulta
if ($result_materias) {
    while ($row = $result_materias->fetch_assoc()) { // Recorre los resultados
        $materias[] = $row; // Almacena cada materia en el arreglo
    }
}

// Procesa el formulario para agregar una nueva materia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $clave_materia = trim($_POST['clave_materia'] ?? ''); // Obtiene y limpia la clave de la materia
    $nombre_materia = trim($_POST['nombre_materia'] ?? ''); // Obtiene y limpia el nombre de la materia

    // Validación de los campos del formulario
    if (empty($clave_materia) || empty($nombre_materia)) {
        $_SESSION['mensaje'] = "Todos los campos son obligatorios.";
        $_SESSION['mensaje_tipo'] = "error";
    } else {
        try {
            // Verifica si la clave de materia ya existe
            $stmt = $conn->prepare("SELECT clave_materia FROM materias WHERE clave_materia = ?");
            $stmt->bind_param("s", $clave_materia);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("La clave de materia ya existe.");
            }

            // Inserta la nueva materia en la base de datos
            $stmt = $conn->prepare("INSERT INTO materias (clave_materia, nombre) VALUES (?, ?)");
            $stmt->bind_param("ss", $clave_materia, $nombre_materia);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Materia agregada correctamente.";
                $_SESSION['mensaje_tipo'] = "success";
            } else {
                throw new Exception("Error al agregar la materia.");
            }
        } catch (Exception $e) {
            $_SESSION['mensaje'] = $e->getMessage();
            $_SESSION['mensaje_tipo'] = "error";
        }
    }
    header("Location: add_user.php"); // Redirige a la misma página
    exit;
}

// --- Gestión de Grupos ---
// Obtiene la lista de todos los grupos
$grupos = [];
$query_groups = "SELECT id_grupo, nombre FROM grupos ORDER BY nombre";
$result_groups = $conn->query($query_groups);
if ($result_groups) {
    while ($row = $result_groups->fetch_assoc()) {
        $grupos[] = $row;
    }
}

// Procesa el formulario para agregar un nuevo grupo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_group'])) {
    $nombre_g = trim($_POST['nombre_grupo'] ?? '');
    if (empty($nombre_g)) {
        $_SESSION['mensaje'] = "El nombre del grupo es obligatorio.";
        $_SESSION['mensaje_tipo'] = "error";
    } else {
        try {
            // Verifica duplicados
            $stmt = $conn->prepare("SELECT id_grupo FROM grupos WHERE nombre = ?");
            $stmt->bind_param("s", $nombre_g);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("El grupo ya existe.");
            }
            // Inserta
            $stmt = $conn->prepare("INSERT INTO grupos (nombre) VALUES (?)");
            $stmt->bind_param("s", $nombre_g);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Grupo agregado correctamente.";
                $_SESSION['mensaje_tipo'] = "success";
            } else {
                throw new Exception("Error al agregar el grupo.");
            }
        } catch (Exception $e) {
            $_SESSION['mensaje'] = $e->getMessage();
            $_SESSION['mensaje_tipo'] = "error";
        }
    }
    header("Location: add_user.php"); exit;
}


$conn->close(); // Cierra la conexión a la base de datos
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> <!-- Define la codificación de caracteres -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Configura el diseño responsivo -->
    <title>Gestionar Usuarios y Profesores</title> <!-- Título de la página -->
    
    <!-- Enlace al archivo CSS -->
    <link rel="stylesheet" href="../CRUD/styles/styles_addUser.css"> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script> <!-- Carga Font Awesome para iconos -->
    <link rel="stylesheet" href="../CRUD/styles/styles_addUser.css?v=2">

</head>
<body>
    <!-- Botón de hamburguesa para mostrar/ocultar la barra lateral -->
    <button id="sidebar-toggle" class="sidebar-toggle">
        <i class="fas fa-bars"></i> <!-- Icono de hamburguesa -->
    </button>

    <!-- Barra lateral de navegación -->
    <div class="sidebar">
        <div>
            <!-- Título del sistema -->
            <h2>DRWSC-B</h2>
            <!-- Descripción breve del sistema -->
            <p>Desarrollo de repositorio web de <br>sistemas y computación beta</p>
        </div>
        <div class="menu">
            <!-- Enlaces de navegación con iconos -->
            <a href="index.php"><i class="fas fa-home"></i> INICIO</a> <!-- Página principal -->
            <a href="search_Document.php"><i class="fas fa-search"></i> BUSCAR</a> <!-- Página de búsqueda -->
            <a href="upload_form.php"><i class="fas fa-upload"></i> SUBIR</a> <!-- Página para subir documentos -->
            <a href="add_user.php"><i class="fas fa-folder-open"></i>GESTION</a> <!-- Gestión de usuarios y profesores -->
            <a href="all_Profiles.php"><i class="fas fa-user"></i> PERFILES</a> <!-- Visualización de perfiles -->
        </div>
        <!-- Enlace para cerrar sesión -->
        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> CERRAR</a>
    </div>

    <main class="content">
        <h1>Gestionar Usuarios y Profesores</h1> <!-- Título principal de la página -->

        <!-- Muestra mensajes de éxito o error almacenados en la sesión -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje <?= $_SESSION['mensaje_tipo'] === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($_SESSION['mensaje']) ?> <!-- Escapa el mensaje para evitar XSS -->
            </div>
            <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?> <!-- Elimina los mensajes de la sesión -->
        <?php endif; ?>

        <!-- Pestañas para navegar entre secciones -->
        <div class="tabs">
            <button class="tab-button active" onclick="openTab('add-user')">Agregar Usuario</button>
            <button class="tab-button" onclick="openTab('list-users')">Listar Usuarios</button>
            <button class="tab-button" onclick="openTab('add-professor')">Agregar Profesor</button>
            <button class="tab-button" onclick="openTab('list-professors')">Listar Profesores</button>
            <button class="tab-button" onclick="openTab('add-subject')">Agregar Materia</button>
            <button class="tab-button" onclick="openTab('list-subjects')">Listar Materias</button>
            <button class="tab-button" onclick="openTab('add-group')">Agregar Grupo</button>
            <button class="tab-button" onclick="openTab('list-groups')">Listar Grupos</button>
        </div>

        <!-- Pestaña: Agregar Usuario -->
        <div id="add-user" class="tab-content active">
            <h2>Agregar Nuevo Usuario</h2>
            <form method="POST" action=""> <!-- Formulario para agregar un usuario -->
                <input type="hidden" name="add_user" value="1"> <!-- Campo oculto para identificar la acción -->
                <div class="form-group">
                    <label for="usuario">Usuario:</label>
                    <input type="text" id="usuario" name="usuario" required> <!-- Campo para el nombre de usuario -->
                </div>
                <div class="form-group">
                    <label for="contrasena">Contraseña:</label>
                    <input type="password" id="contrasena" name="contrasena" required> <!-- Campo para la contraseña -->
                </div>
                <div class="form-group">
                    <label for="rol">Rol:</label>
                    <select id="rol" name="rol" required> <!-- Selector para el rol -->
                        <option value="">Seleccionar rol</option>
                        <option value="Administrador">Administrador</option>
                        <option value="Jefe">Jefe</option>
                        <option value="Coordinador">Coordinador</option>
                        <option value="Visitante">Visitante</option>
                    </select>
                </div>
                <button type="submit" class="btn">Agregar Usuario</button> <!-- Botón para enviar el formulario -->
            </form>
        </div>

        <!-- Pestaña: Listar Usuarios -->
        <div id="list-users" class="tab-content">
            <h2>Lista de Usuarios</h2>
            <?php if (empty($usuarios)): ?> <!-- Verifica si no hay usuarios -->
                <p>No hay usuarios registrados.</p>
            <?php else: ?>
                <table class="data-table"> <!-- Tabla para mostrar usuarios -->
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?> <!-- Itera sobre los usuarios -->
                            <tr>
                                <td><?= htmlspecialchars($usuario['usuario']) ?></td> <!-- Muestra el nombre de usuario -->
                                <td><?= htmlspecialchars($usuario['rol']) ?></td> <!-- Muestra el rol -->
                                <td>
                                    <?php if (in_array($current_role, $allowed_roles)): ?> <!-- Verifica permisos -->
                                        <a href="edit_user.php?id=<?= $usuario['id_usuario'] ?>" 
                                           class="btn edit-btn">
                                           ✏️ Editar Contraseña
                                        </a> <!-- Enlace para editar contraseña -->
                                        <a href="../CRUD/delete_user.php?id=<?= $usuario['id_usuario'] ?>" 
                                           class="btn delete-btn" 
                                           onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                                           🗑 Eliminar
                                        </a> <!-- Enlace para eliminar usuario con confirmación -->
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pestaña: Agregar Profesor -->
        <div id="add-professor" class="tab-content">
            <h2>Agregar Nuevo Profesor</h2>
            <form method="POST" action=""> <!-- Formulario para agregar un profesor -->
                <input type="hidden" name="add_professor" value="1"> <!-- Campo oculto para identificar la acción -->
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required> <!-- Campo para el nombre -->
                </div>
                <div class="form-group">
                    <label for="id_departamento">Departamento (Opcional):</label>
                    <select id="id_departamento" name="id_departamento"> <!-- Selector para el departamento -->
                        <option value="">Ninguno</option>
                        <?php foreach ($departamentos as $dept): ?> <!-- Itera sobre los departamentos -->
                            <option value="<?= $dept['id_departamento'] ?>">
                                <?= htmlspecialchars($dept['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="anio_ingreso">Año de Ingreso (Opcional):</label>
                    <input type="number" id="anio_ingreso" name="anio_ingreso" min="1900" max="<?= date('Y') ?>"> <!-- Campo para el año de ingreso -->
                </div>
                <button type="submit" class="btn">Agregar Profesor</button> <!-- Botón para enviar el formulario -->
            </form>
        </div>

        <!-- Pestaña: Listar Profesores -->
        <div id="list-professors" class="tab-content">
            <h2>Lista de Profesores</h2>
            <?php if (empty($profesores)): ?> <!-- Verifica si no hay profesores -->
                <p>No hay profesores registrados.</p>
            <?php else: ?>
                <table class="data-table"> <!-- Tabla para mostrar profesores -->
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Año de Ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profesores as $profesor): ?> <!-- Itera sobre los profesores -->
                            <tr>
                                <td><?= htmlspecialchars($profesor['nombre']) ?></td> <!-- Muestra el nombre -->
                                <td><?= htmlspecialchars($profesor['departamento'] ?? 'No asignado') ?></td> <!-- Muestra el departamento -->
                                <td><?= htmlspecialchars($profesor['anio_ingreso'] ?? 'No especificado') ?></td> <!-- Muestra el año de ingreso -->
                                <td>
                                    <?php if (in_array($current_role, $allowed_roles)): ?> <!-- Verifica permisos -->
                                        <a href="edit_professor.php?id=<?= $profesor['id_profesor'] ?>" 
                                           class="btn edit-btn">
                                           ✏️ Editar
                                        </a> <!-- Enlace para editar profesor -->
                                        <a href="../CRUD/delete_professor.php?id=<?= $profesor['id_profesor'] ?>" 
                                           class="btn delete-btn" 
                                           onclick="return confirm('¿Estás seguro de que deseas eliminar este profesor?');">
                                           🗑 Eliminar
                                        </a> <!-- Enlace para eliminar profesor con confirmación -->
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pestaña: Listar Materias -->
        <div id="list-subjects" class="tab-content">
            <h2>Lista de Materias</h2>
            <?php if (empty($materias)): ?> <!-- Verifica si no hay materias -->
                <p>No hay materias registradas.</p>
            <?php else: ?>
                <table class="data-table"> <!-- Tabla para mostrar materias -->
                    <thead>
                        <tr>
                            <th>Clave Materia</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materias as $materia): ?> <!-- Itera sobre las materias -->
                            <tr>
                                <td><?= htmlspecialchars($materia['clave_materia']) ?></td> <!-- Muestra la clave de la materia -->
                                <td><?= htmlspecialchars($materia['nombre']) ?></td> <!-- Muestra el nombre de la materia -->
                                <td>
                                    <?php if (in_array($current_role, $allowed_roles)): ?> <!-- Verifica permisos -->
                                        <a href="edit_subject.php?clave_materia=<?= urlencode($materia['clave_materia']) ?>" 
                                           class="btn edit-btn">
                                           ✏️ Editar
                                        </a> <!-- Enlace para editar materia -->
                                        <?php if (stripos($materia['nombre'], 'servicio') === false): ?> <!-- Evita eliminar materias con "servicio" en el nombre -->
                                            <a href="delete_subject.php?clave_materia=<?= urlencode($materia['clave_materia']) ?>" 
                                               class="btn delete-btn" 
                                               onclick="return confirm('¿Estás seguro de que deseas eliminar esta materia?');">
                                               🗑 Eliminar
                                            </a> <!-- Enlace para eliminar materia con confirmación -->
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pestaña: Agregar Materia -->
        <div id="add-subject" class="tab-content">
            <h2>Agregar Nueva Materia</h2>
            <form method="POST" action=""> <!-- Formulario para agregar una materia -->
                <input type="hidden" name="add_subject" value="1"> <!-- Campo oculto para identificar la acción -->
                <div class="form-group">
                    <label for="clave_materia">Clave Materia:</label>
                    <input type="text" id="clave_materia" name="clave_materia" required> <!-- Campo para la clave de la materia -->
                </div>
                <div class="form-group">
                    <label for="nombre_materia">Nombre:</label>
                    <input type="text" id="nombre_materia" name="nombre_materia" required> <!-- Campo para el nombre de la materia -->
                </div>
                <button type="submit" class="btn">Agregar Materia</button> <!-- Botón para enviar el formulario -->
            </form>
        </div>

        <!-- Pestaña: Agregar Grupo -->
<div id="add-group" class="tab-content">
    <h2>Agregar Nuevo Grupo</h2>
    <form method="POST" action="">
        <input type="hidden" name="add_group" value="1">
        <div class="form-group">
            <label for="nombre_grupo">Nombre del Grupo:</label>
            <input type="text" id="nombre_grupo" name="nombre_grupo" required>
        </div>
        <button type="submit" class="btn">Agregar Grupo</button>
    </form>
</div>

<!-- Pestaña: Listar Grupos -->
<div id="list-groups" class="tab-content">
    <h2>Lista de Grupos</h2>
    <?php if (empty($grupos)): ?>
        <p>No hay grupos registrados.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($grupos as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g['nombre']) ?></td>
                    <td>
                    <?php if (in_array($current_role, $allowed_roles)): ?>
                        <a href="edit_group.php?id=<?= $g['id_grupo'] ?>" class="btn edit-btn">
                            ✏️ Editar
                        </a>
                        <a href="delete_group.php?id=<?= $g['id_grupo'] ?>" class="btn delete-btn"
                           onclick="return confirm('¿Eliminar este grupo?');">
                            🗑 Eliminar
                        </a>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
    </main>

<script>
    // Ejecuta el código cuando el DOM esté completamente cargado
    document.addEventListener("DOMContentLoaded", function () {
        // ------------------- TOGGLE DEL SIDEBAR -------------------
        // Selecciona los elementos necesarios para el sidebar
        const sidebarToggle = document.getElementById("sidebar-toggle"); // Botón de hamburguesa
        const sidebar = document.querySelector(".sidebar"); // Barra lateral
        const content = document.querySelector(".content"); // Contenido principal

        // Agrega un evento de clic al botón de hamburguesa si existe
        if (sidebarToggle) {
            sidebarToggle.addEventListener("click", function () {
                // Alterna la clase 'collapsed' para mostrar u ocultar la barra lateral
                sidebar.classList.toggle("collapsed");
                // Ajusta el contenido según el estado de la barra lateral
                content.classList.toggle("collapsed");
            });
        }


        // ------------------- MANEJO DE PESTAÑAS -------------------
        window.openTab = function(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(button => button.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            document.querySelector(`button[onclick="openTab('${tabId}')"]`).classList.add('active');
        };
    });
</script>

</body>
</html>