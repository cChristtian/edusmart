<?php
// Incluir el archivo de configuración para acceder a las configuraciones globales y proteger la página
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); // Solo administradores

// Crear una instancia de la base de datos
$db = new Database();

// Operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_grupo'])) {
        // Crear un nuevo grupo
        $nombre = trim($_POST['nombre']); // Nombre del grupo
        $grado = trim($_POST['grado']); // Grado del grupo
        $ciclo_escolar = trim($_POST['ciclo_escolar']); // Ciclo escolar del grupo

        // Preparar la consulta para insertar un nuevo grupo
        $db->query("INSERT INTO grupos (nombre, grado, ciclo_escolar) VALUES (:nombre, :grado, :ciclo)");
        $db->bind(':nombre', $nombre);
        $db->bind(':grado', $grado);
        $db->bind(':ciclo', $ciclo_escolar);

        // Ejecutar la consulta y redirigir si es exitosa
        if ($db->execute()) {
            $_SESSION['success'] = "Grupo creado correctamente";
            header("Location: grupos.php");
            exit;
        }
    } elseif (isset($_POST['editar_grupo'])) {
        // Editar un grupo existente
        $id = intval($_POST['id']); // ID del grupo
        $nombre = trim($_POST['nombre']); // Nombre del grupo
        $grado = trim($_POST['grado']); // Grado del grupo
        $ciclo_escolar = trim($_POST['ciclo_escolar']); // Ciclo escolar del grupo
        $maestro_id = !empty($_POST['maestro_id']) ? intval($_POST['maestro_id']) : null; // Maestro asignado (puede ser null)

        // Preparar la consulta para actualizar el grupo
        $db->query("UPDATE grupos SET 
                   nombre = :nombre,
                   grado = :grado,
                   ciclo_escolar = :ciclo,
                   maestro_id = :maestro_id
                   WHERE id = :id");
        $db->bind(':nombre', $nombre);
        $db->bind(':grado', $grado);
        $db->bind(':ciclo', $ciclo_escolar);
        $db->bind(':maestro_id', $maestro_id);
        $db->bind(':id', $id);

        // Ejecutar la consulta y redirigir si es exitosa
        if ($db->execute()) {
            $_SESSION['success'] = "Grupo actualizado correctamente";
            header("Location: grupos.php");
            exit;
        }
    }
} elseif (isset($_GET['eliminar'])) {
    // Eliminar un grupo
    $id = intval($_GET['eliminar']); // ID del grupo a eliminar

    // Verificar que el grupo no tenga estudiantes antes de eliminar
    $db->query("SELECT COUNT(*) as total FROM estudiantes WHERE grupo_id = :id");
    $db->bind(':id', $id);
    $result = $db->single();

    if ($result->total == 0) {
        // Si no tiene estudiantes, eliminar el grupo
        $db->query("DELETE FROM grupos WHERE id = :id");
        $db->bind(':id', $id);

        if ($db->execute()) {
            $_SESSION['success'] = "Grupo eliminado correctamente";
        }
    } else {
        // Si tiene estudiantes, mostrar un mensaje de error
        $_SESSION['error'] = "No se puede eliminar, el grupo tiene estudiantes asignados";
    }

    // Redirigir a la página de grupos
    header("Location: grupos.php");
    exit;
}

// Obtener datos de los grupos y maestros
$db->query("SELECT g.*, u.nombre_completo as maestro FROM grupos g LEFT JOIN usuarios u ON g.maestro_id = u.id ORDER BY g.grado, g.nombre");
$grupos = $db->resultSet();

$db->query("SELECT id, nombre_completo FROM usuarios WHERE rol_id = 3 ORDER BY nombre_completo");
$maestros = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - <?php echo APP_NAME; ?></title>
    <!-- Incluye Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- O si prefieres una versión específica -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <?php require_once('./partials/sidebar.php') ?>

        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold">Gestión de Grupos</h2>
                <button onclick="document.getElementById('modal-crear-grupo').classList.remove('hidden')"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    + Nuevo Grupo
                </button>
            </div>

            <!-- Mensajes -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Tabla de grupos -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">Nombre</th>
                            <th class="px-6 py-3 text-left">Grado</th>
                            <th class="px-6 py-3 text-left">Ciclo Escolar</th>
                            <th class="px-6 py-3 text-left">Maestro</th>
                            <th class="px-6 py-3 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($grupos as $grupo): ?>
                            <tr>
                                <td class="px-6 py-4"><?= htmlspecialchars($grupo->nombre) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($grupo->grado) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($grupo->ciclo_escolar) ?></td>
                                <td class="px-6 py-4">
                                    <?= $grupo->maestro ? htmlspecialchars($grupo->maestro) : 'Sin asignar' ?>
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <button onclick="editarGrupo(<?= $grupo->id ?>)"
                                        class="text-blue-500 hover:text-blue-700">Editar</button>
                                    <a href="grupos.php?eliminar=<?= $grupo->id ?>" class="text-red-500 hover:text-red-700"
                                        onclick="return confirm('¿Eliminar este grupo?')">Eliminar</a>
                                    <a href="ver_grupo.php?id=<?= $grupo->id ?>"
                                        class="text-green-500 hover:text-green-700">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-8 bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-4">Importar Estudiantes desde Excel</h3>
                <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                    <h4 class="font-medium text-blue-800">Formato requerido:</h4>
                    <ul class="list-disc list-inside text-sm text-blue-700">
                        <li>Archivo Excel (.xlsx o .xls) con formato oficial</li>
                        <li>Debe contener columnas: No. | NIE | Nombre del estudiante</li>
                        <li>Los metadatos de la escuela se ignorarán automáticamente</li>
                    </ul>
                </div>

                <form action="importar_estudiantes.php" method="post" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="grupo_id" class="block text-gray-700 mb-2">Grupo Destino</label>
                            <select id="grupo_id" name="grupo_id" class="w-full px-3 py-2 border rounded-lg" required>
                                <option value="">Seleccione un grupo</option>
                                <?php foreach ($grupos as $grupo): ?>
                                    <option value="<?= $grupo->id ?>">
                                        <?= htmlspecialchars($grupo->nombre) ?> - <?= htmlspecialchars($grupo->grado) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="archivo" class="block text-gray-700 mb-2">Archivo Excel</label>
                            <input type="file" id="archivo" name="archivo" accept=".xlsx,.xls"
                                class="w-full px-3 py-2 border rounded-lg" required>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600">
                            <i class="fas fa-upload mr-2"></i> Importar Estudiantes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para crear grupo -->
    <div id="modal-crear-grupo"
        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">Nuevo Grupo</h3>
                    <button onclick="cerrarModal('modal-crear-grupo')"
                        class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="crear_grupo">

                    <div class="space-y-4">
                        <div>
                            <label for="nombre" class="block mb-2">Nombre del Grupo</label>
                            <input type="text" id="nombre" name="nombre" class="w-full p-2 border rounded" required>
                        </div>

                        <div>
                            <label for="grado" class="block mb-2">Grado</label>
                            <input type="text" id="grado" name="grado" class="w-full p-2 border rounded" required>
                        </div>

                        <div>
                            <label for="ciclo" class="block mb-2">Ciclo Escolar</label>
                            <input type="text" id="ciclo" name="ciclo_escolar" class="w-full p-2 border rounded"
                                placeholder="Ej: 2023-2024" required>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="cerrarModal('modal-crear-grupo')"
                            class="px-4 py-2 border rounded">Cancelar</button>
                        <button type="submit"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar grupo -->
    <div id="modal-editar-grupo"
        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">Editar Grupo</h3>
                    <button onclick="cerrarModal('modal-editar-grupo')"
                        class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form id="form-editar-grupo" method="POST">
                    <input type="hidden" name="id" id="edit-grupo-id">
                    <input type="hidden" name="editar_grupo">

                    <div class="space-y-4">
                        <div>
                            <label for="edit-grupo-nombre" class="block mb-2">Nombre</label>
                            <input type="text" id="edit-grupo-nombre" name="nombre" class="w-full p-2 border rounded"
                                required>
                        </div>

                        <div>
                            <label for="edit-grupo-grado" class="block mb-2">Grado</label>
                            <input type="text" id="edit-grupo-grado" name="grado" class="w-full p-2 border rounded"
                                required>
                        </div>

                        <div>
                            <label for="edit-grupo-ciclo" class="block mb-2">Ciclo Escolar</label>
                            <input type="text" id="edit-grupo-ciclo" name="ciclo_escolar"
                                class="w-full p-2 border rounded" required>
                        </div>

                        <div>
                            <label for="edit-grupo-maestro" class="block mb-2">Maestro Asignado</label>
                            <select id="edit-grupo-maestro" name="maestro_id" class="w-full p-2 border rounded">
                                <option value="">Sin asignar</option>
                                <?php foreach ($maestros as $maestro): ?>
                                    <option value="<?= $maestro->id ?>"><?= htmlspecialchars($maestro->nombre_completo) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="cerrarModal('modal-editar-grupo')"
                            class="px-4 py-2 border rounded">Cancelar</button>
                        <button type="submit"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editarGrupo(id) {
            fetch(`obtener_grupo.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit-grupo-id').value = data.id;
                    document.getElementById('edit-grupo-nombre').value = data.nombre;
                    document.getElementById('edit-grupo-grado').value = data.grado;
                    document.getElementById('edit-grupo-ciclo').value = data.ciclo_escolar;
                    document.getElementById('edit-grupo-maestro').value = data.maestro_id || '';

                    document.getElementById('modal-editar-grupo').classList.remove('hidden');
                });
        }

        function cerrarModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
    </script>
</body>

</html>