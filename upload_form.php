<?php
// C:\xampp\htdocs\CrudXamp\upload_form.php

// 1. Declarar la variable de conexión como GLOBAL para usarla en este script.
// Esta variable fue definida en db.php, que fue cargado por index.php.
global $conn;

// 2. Define roles permitidos para acceder a la gestión de usuarios
$allowed_roles = ['administrador', 'jefe', 'coordinador', 'admin', 'visitante'];
$current_role = strtolower($_SESSION['rol'] ?? '');
$is_authorized = isset($_SESSION['usuario_id']) && in_array($current_role, $allowed_roles);

// 3. Verifica si el usuario está autenticado y tiene un rol permitido
if (!$is_authorized) {
    // Si no está autorizado, simplemente detenemos la ejecución de este archivo
    // El HTML de error se mostrará abajo.
    // **NOTA:** La redirección a login.php ya debería estar en index.php,
    // pero mantenemos esta bandera para el HTML.
    $is_authorized_to_display_form = false;
} else {
    $is_authorized_to_display_form = true;
}

// **SOLO EJECUTAR SI ESTÁ AUTORIZADO para prevenir consultas innecesarias**
if ($is_authorized_to_display_form) {
    
    // Consulta para obtener todas las materias, ordenadas por clave_materia
    $query_materias = "SELECT clave_materia, nombre FROM materias ORDER BY clave_materia";
    // Si $conn fuera NULL, el error fatal ocurriría aquí. Ahora debe funcionar.
    $stmt_materias = $conn->prepare($query_materias); 
    if (!$stmt_materias) {
        error_log("Error al preparar la consulta de materias: " . $conn->error);
        die("Error en la base de datos (Materias). Por favor, intenta de nuevo más tarde.");
    }
    $stmt_materias->execute();
    $result_materias = $stmt_materias->get_result();
    $materias = [];
    while ($row = $result_materias->fetch_assoc()) {
        $materias[] = $row;
    }
    $stmt_materias->close();

    // Consulta para obtener todos los profesores, ordenados por nombre
    $query_profesores = "SELECT id_profesor, nombre FROM profesores ORDER BY nombre";
    $stmt_profesores = $conn->prepare($query_profesores); 
    if (!$stmt_profesores) {
        error_log("Error al preparar la consulta de profesores: " . $conn->error);
        die("Error en la base de datos (Profesores). Por favor, intenta de nuevo más tarde.");
    }
    $stmt_profesores->execute();
    $result_profesores = $stmt_profesores->get_result();
    $profesores = [];
    while ($row = $result_profesores->fetch_assoc()) {
        $profesores[] = $row;
    }
    $stmt_profesores->close();

    // Consulta para obtener todos los grupos, ordenados por nombre
    $query_grupos = "SELECT nombre FROM grupos ORDER BY nombre";
    $stmt_grupos = $conn->prepare($query_grupos);
    if (!$stmt_grupos) {
        error_log("Error al preparar la consulta de grupos: " . $conn->error);
        die("Error en la base de datos (Grupos). Por favor, intenta de nuevo más tarde.");
    }
    $stmt_grupos->execute();
    $result_grupos = $stmt_grupos->get_result();
    $grupos = [];
    while ($row = $result_grupos->fetch_assoc()) {
        $grupos[] = $row;
    }
    $stmt_grupos->close();
    
    // Consulta para obtener los valores únicos de periodo de la tabla documentos
    $query_periodos = "SELECT DISTINCT periodo FROM documentos WHERE periodo IS NOT NULL ORDER BY periodo";
    $stmt_periodos = $conn->prepare($query_periodos);
    if (!$stmt_periodos) {
        error_log("Error al preparar la consulta de periodos: " . $conn->error);
        die("Error en la base de datos (Periodos). Por favor, intenta de nuevo más tarde.");
    }
    $stmt_periodos->execute();
    $result_periodos = $stmt_periodos->get_result();
    $periodos = [];
    while ($row = $result_periodos->fetch_assoc()) {
        $periodos[] = $row['periodo'];
    }
    $stmt_periodos->close();

} else {
    // Inicializa arrays vacíos si no está autorizado, para evitar errores en el HTML
    $materias = $profesores = $grupos = $periodos = [];
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Define la codificación de caracteres para soportar caracteres especiales -->
    <meta charset="UTF-8">
    <!-- Configura el diseño responsivo para adaptarse a diferentes dispositivos -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título de la página que aparece en la pestaña del navegador -->
    <title>Sistema de Gestión de Imágenes</title>
    <!-- Carga Font Awesome para usar iconos vectoriales -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>
    
    <!-- Enlaces a las hojas de estilo CSS -->
    <link rel="stylesheet" href="../CRUD/styles/styles_UploadForm.css"> <!-- Estilos específicos para el formulario de subida -->
    <link rel="stylesheet" href="../CRUD/styles/label.css"> <!-- Estilos para etiquetas -->
    <link rel="stylesheet" href="../CRUD/styles/normalize.css"> <!-- Normalización de estilos para consistencia entre navegadores -->
</head>
<body>
    <!-- Botón de hamburguesa para alternar la visibilidad de la barra lateral -->
    <button id="sidebar-toggle" class="sidebar-toggle">
        <i class="fas fa-bars"></i> <!-- Icono de hamburguesa de Font Awesome -->
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

    <!-- Contenido principal de la página -->
    <div class="content">
        <?php if ($is_authorized): ?> <!-- Verifica si el usuario está autorizado -->
        <div class="upload-box">
            <!-- Título del formulario -->
            <h2>Subir Archivo</h2>
            <!-- Formulario para subir archivos, con soporte para datos multipart -->
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-container">
                    <!-- Contenedor para los campos del formulario -->
                    <div class="form-fields">
                        <!-- Campo para seleccionar el profesor -->
                        <div class="input-group">
                            <label for="id_profesor">Nombre del profesor:</label>
                            <select id="id_profesor" name="id_profesor" required>
                                <option value="">Selecciona un profesor</option> <!-- Opción por defecto -->
                                <?php foreach ($profesores as $profesor): ?>
                                    <!-- Opciones dinámicas para los profesores -->
                                    <option value="<?= htmlspecialchars($profesor['id_profesor']) ?>">
                                        <?= htmlspecialchars($profesor['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Campo para seleccionar el tipo de documento -->
                        <div class="input-group">
                            <label for="tipo_documento">Tipo:</label>
                            <!-- Grupo de botones para seleccionar el tipo de documento -->
                            <div class="button-group" id="button-group">
                                <button type="button" class="type-button" data-type="acta">Acta</button>
                                <button type="button" class="type-button" data-type="horario">Horario</button>
                                <button type="button" class="type-button" data-type="otros">Otros</button>
                            </div>
                            <!-- Campo oculto para almacenar el tipo seleccionado -->
                            <input type="hidden" name="tipo_documento" id="tipo_documento" required>

                            <!-- Contenedor para el selector de subtipos de "Otros", oculto inicialmente -->
                            <div id="otros-tipo-container" style="display: none; margin-top: 10px;">
                                <label for="otros_tipo">Especificar tipo:</label>
                                <select id="otros_tipo" name="otros_tipo" class="form-control">
                                    <option value="licenciatura">Licenciatura</option>
                                    <option value="maestria">Maestría</option>
                                    <option value="doctorado">Doctorado</option>
                                    <option value="cursos">Cursos</option>
                                    <option value="otros">Otros</option>
                                </select>
                            </div>
                        </div>

                        <!-- Campos académicos (periodo, año, materia, grupo) -->
                        <div class="campos-academicos">
                            <!-- Campo para seleccionar el periodo -->
<div class="input-group">
    <label for="periodo">Periodo:</label>
    <select id="periodo" name="periodo" required>
        <option value="">Selecciona un periodo</option>
        <?php foreach ($periodos as $periodo): ?>
            <option value="<?= htmlspecialchars($periodo) ?>">
                <?= htmlspecialchars($periodo) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

                            <!-- Campo para seleccionar el año -->
                            <div class="input-group">
                                <label for="anio">Año:</label>
                                <select id="anio" name="anio" required>
                                    <?php for ($year = 2011; $year <= 2025; $year++): ?>
                                        <option value="<?= $year ?>"><?= $year ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Campo para seleccionar la clave de la materia -->
                            <div class="input-group">
                                <label for="clave_materia">Clave Materia:</label>
                                <select id="clave_materia" name="clave_materia" required>
                                    <option value="">Selecciona una materia</option> <!-- Opción por defecto -->
                                    <?php foreach ($materias as $materia): ?>
                                        <!-- Opciones dinámicas para las materias -->
                                        <option value="<?= htmlspecialchars($materia['clave_materia']) ?>">
                                            <?= htmlspecialchars($materia['clave_materia'] . ' - ' . $materia['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

<div class="input-group">
    <label for="grupo">Grupo:</label>
    <select id="grupo" name="grupo" required>
        <option value="">Selecciona un grupo</option>
        <?php foreach ($grupos as $grupo): ?>
            <option value="<?= htmlspecialchars($grupo['nombre']) ?>">
                <?= htmlspecialchars($grupo['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                        </div>
                    </div>

                    <!-- Sección para subir el archivo -->
                    <div class="file-upload">
                        <!-- Área para arrastrar y soltar archivos -->
                        <div class="file-drop-area" id="file-drop-area">
                            <!-- Input oculto para seleccionar archivos -->
                            <input type="file" id="fileInput" name="imagen" accept="image/*" hidden>
                            <i class="fas fa-upload"></i> <!-- Icono de subida -->
                            <p>Subir un archivo o arrastrar y soltar</p>
                            <p style="font-size: 12px; color: #999;">Imagen (JPG, PNG) hasta 350KB</p>
                            <!-- Contenedor para la vista previa de la imagen -->
                            <div id="preview-container"></div>
                        </div>
                    </div>
                </div>

                <!-- Contenedor para los botones del formulario -->
                <div class="button-container">
                    <div>
                        <!-- Botón para eliminar la vista previa, oculto inicialmente -->
                        <button type="button" id="deletePreviewBtn" style="display:none;">Eliminar</button>
                    </div>
                    <div>
                        <!-- Botón para enviar el formulario -->
                        <button type="submit" class="button-save">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
        <?php else: ?>
            <!-- Mensaje de error si el usuario no está autorizado -->
            <div class="error-message">
                <h2>No tienes permisos para subir archivos.</h2>
                <p>Por favor, inicia sesión con una cuenta autorizada.</p>
                <a href="login.php">Iniciar Sesión</a> <!-- Enlace para iniciar sesión -->
            </div>
        <?php endif; ?>
    </div>

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

        // ------------------- BOTONES DE TIPO DE DOCUMENTO -------------------
        // Selecciona todos los botones de tipo de documento
        const buttons = document.querySelectorAll(".type-button");
        // Selecciona el contenedor para el selector de subtipos de "Otros"
        const otrosTipoContainer = document.getElementById("otros-tipo-container");
        // Selecciona el elemento del selector de subtipos
        const otrosTipoSelect = document.getElementById("otros_tipo");
        // Selecciona el contenedor de los campos académicos
        const camposAcademicos = document.querySelector(".campos-academicos");

        // Agrega un evento de clic a cada botón de tipo
buttons.forEach(button => {
    button.addEventListener("click", function() {
        // Remueve la clase 'active' de todos los botones
        buttons.forEach(btn => btn.classList.remove("active"));
        // Agrega la clase 'active' al botón clicado
        this.classList.add("active");

        const type = this.dataset.type;
        
        // Referencias a los inputs y sus contenedores
        const claveInput = document.getElementById("clave_materia");
        const grupoInput = document.getElementById("grupo");
        // Buscamos el contenedor .input-group padre para ocultarlo visualmente
        const claveContainer = claveInput.closest('.input-group');
        const grupoContainer = grupoInput.closest('.input-group');

        // LÓGICA DE VISIBILIDAD
        if (type === "otros") {
            // Caso OTROS: Muestra subtipos, oculta campos académicos
            otrosTipoContainer.style.display = "block";
            camposAcademicos.style.display = "none";
            
            document.getElementById("tipo_documento").value = "otros_" + otrosTipoSelect.value;
            
            // Quitar required de todo
            document.getElementById("periodo").removeAttribute("required");
            document.getElementById("anio").removeAttribute("required");
            claveInput.removeAttribute("required");
            grupoInput.removeAttribute("required");

        } else if (type === "horario") {
            // CASO HORARIO: Muestra Periodo/Año, pero OCULTA Clave y Grupo
            otrosTipoContainer.style.display = "none";
            camposAcademicos.style.display = "block"; // Asegura que el contenedor principal sea visible
            
            // Ocultar específicamente Clave y Grupo
            claveContainer.style.display = "none";
            grupoContainer.style.display = "none";
            
            document.getElementById("tipo_documento").value = "horario";

            // Required solo para Periodo y Año
            document.getElementById("periodo").setAttribute("required", "");
            document.getElementById("anio").setAttribute("required", "");
            
            // Quitar required de los ocultos
            claveInput.removeAttribute("required");
            grupoInput.removeAttribute("required");
            // Limpiar valores para evitar envíos sucios
            claveInput.value = "";
            grupoInput.value = "";

        } else {
            // CASO ACTA (o Default): Muestra todo
            otrosTipoContainer.style.display = "none";
            camposAcademicos.style.display = "block";
            
            // Mostrar Clave y Grupo
            claveContainer.style.display = "block";
            grupoContainer.style.display = "block";

            document.getElementById("tipo_documento").value = this.dataset.type;
            
            // Todos required
            document.getElementById("periodo").setAttribute("required", "");
            document.getElementById("anio").setAttribute("required", "");
            claveInput.setAttribute("required", "");
            grupoInput.setAttribute("required", "");
        }
    });
});

        // Actualiza el valor del campo oculto cuando cambia el selector de subtipos
        otrosTipoSelect.addEventListener("change", function() {
            // Verifica si el botón "Otros" está activo
            if (document.querySelector(".type-button[data-type='otros']").classList.contains("active")) {
                // Actualiza el valor del campo oculto con el subtipo seleccionado
                document.getElementById("tipo_documento").value = "otros_" + this.value;
            }
        });

        // ------------------- MANEJO DE ARCHIVO -------------------
        // Selecciona los elementos relacionados con la subida de archivos
        const fileDropArea = document.getElementById("file-drop-area"); // Área de arrastrar y soltar
        const fileInput = document.getElementById("fileInput"); // Input de archivo oculto
        const previewContainer = document.getElementById("preview-container"); // Contenedor de vista previa
        const uploadForm = document.getElementById("uploadForm"); // Formulario
        const deletePreviewBtn = document.getElementById("deletePreviewBtn"); // Botón para eliminar vista previa

        // Función para manejar la selección y vista previa de archivos
        function handleFileChange() {
            const file = fileInput.files[0]; // Obtiene el primer archivo seleccionado
            const allowedTypes = ["image/jpeg", "image/png"]; // Tipos de archivo permitidos
            const maxSize = 350 * 1024; // Tamaño máximo permitido (350KB)


            // Limpia el contenedor de vista previa
            previewContainer.innerHTML = "";

            if (file) {
                // Valida el tamaño del archivo
                if (file.size > maxSize) {
                    alert("El archivo es demasiado grande. El tamaño máximo permitido es 350KB.");
                    fileInput.value = ''; // Limpia el input
                    return;
                }

                // Valida el tipo de archivo
                if (!allowedTypes.includes(file.type)) {
                    alert("Por favor, sube una imagen válida (JPEG, PNG, GIF, WEBP).");
                    fileInput.value = ''; // Limpia el input
                    return;
                }

                // Crea una vista previa de la imagen
                const reader = new FileReader();
                reader.onload = function (e) {
                    const imgPreview = document.createElement("img"); // Crea un elemento de imagen
                    imgPreview.src = e.target.result; // Establece la fuente como la imagen leída
                    imgPreview.style.maxWidth = "100%"; // Limita el ancho
                    imgPreview.style.height = "auto"; // Mantiene la proporción
                    previewContainer.appendChild(imgPreview); // Añade la imagen al contenedor
                    deletePreviewBtn.style.display = "inline-block"; // Muestra el botón de eliminar
                };
                reader.readAsDataURL(file); // Lee el archivo como URL de datos
            } else {
                // Oculta el botón de eliminar si no hay archivo
                deletePreviewBtn.style.display = "none";
            }
        }

        // Agrega un evento para manejar cambios en el input de archivo
        fileInput.addEventListener("change", handleFileChange);

        // Permite abrir el selector de archivos al hacer clic en el área de arrastrar
        fileDropArea.addEventListener("click", () => fileInput.click());

        // Agrega un efecto visual cuando se arrastra un archivo sobre el área
        fileDropArea.addEventListener("dragover", (event) => {
            event.preventDefault(); // Evita el comportamiento predeterminado
            fileDropArea.classList.add("drag-over"); // Añade clase para estilo
        });

        // Elimina el efecto visual cuando el archivo sale del área
        fileDropArea.addEventListener("dragleave", () => {
            fileDropArea.classList.remove("drag-over"); // Remueve clase de estilo
        });

        // Maneja el evento de soltar un archivo en el área
        fileDropArea.addEventListener("drop", (event) => {
            event.preventDefault(); // Evita el comportamiento predeterminado
            fileDropArea.classList.remove("drag-over"); // Remueve clase de estilo
            fileInput.files = event.dataTransfer.files; // Asigna los archivos soltados al input
            handleFileChange(); // Procesa el archivo
        });

        // Maneja el clic en el botón de eliminar vista previa
        deletePreviewBtn.addEventListener("click", () => {
            fileInput.value = ''; // Limpia el input de archivo
            previewContainer.innerHTML = ''; // Limpia el contenedor de vista previa
            deletePreviewBtn.style.display = "none"; // Oculta el botón
        });

        // ------------------- ENVÍO DEL FORMULARIO VIA AJAX -------------------
        // Agrega un evento para manejar el envío del formulario
        if (uploadForm) {
            uploadForm.addEventListener("submit", (e) => {
                e.preventDefault(); // Evita el envío predeterminado del formulario
                const formData = new FormData(uploadForm); // Crea un objeto FormData con los datos del formulario

                // Agrega el archivo al FormData si existe
                if (fileInput.files.length > 0) {
                    formData.append("imagen", fileInput.files[0]);
                }

                // Envía los datos al servidor mediante una solicitud Fetch
                fetch("upload.php", {
                    method: "POST",
                    body: formData // Envía el FormData con los datos y el archivo
                })
                .then(response => response.json()) // Parsea la respuesta como JSON
                .then(data => {
                    // Muestra un mensaje al usuario
                    alert(data.message);
                    if (data.status === 'success') {
                        // Resetea el formulario si la subida fue exitosa
                        uploadForm.reset();
                        previewContainer.innerHTML = ""; // Limpia la vista previa
                        deletePreviewBtn.style.display = "none"; // Oculta el botón de eliminar
                        buttons.forEach(btn => btn.classList.remove("active")); // Desactiva los botones de tipo
                        const tipoInput = document.getElementById("tipo_documento");
                        if (tipoInput) tipoInput.value = ""; // Limpia el campo oculto
                        otrosTipoContainer.style.display = "none"; // Oculta el selector de subtipos
                        camposAcademicos.style.display = "block"; // Muestra los campos académicos
                        // Restaura los atributos 'required'
                        document.getElementById("periodo").setAttribute("required", "");
                        document.getElementById("anio").setAttribute("required", "");
                        document.getElementById("clave_materia").setAttribute("required", "");
                        document.getElementById("grupo").setAttribute("required", "");
                    }
                })
                .catch(err => console.error("Error:", err)); // Maneja errores de la solicitud
            });
        }
        // ------------------- FIN DEL ENVÍO DEL FORMULARIO -------------------
    });
    </script>
</body>
</html>