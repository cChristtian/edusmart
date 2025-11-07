<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([3]); // Solo maestros

$db = new Database();
$maestro_id = $_SESSION['user_id'];

// Obtener estadísticas
$db->query("SELECT COUNT(DISTINCT g.id) as total_grupos, 
                   COUNT(DISTINCT e.id) as total_estudiantes,
                   COUNT(DISTINCT m.id) as total_materias
            FROM grupos g
            JOIN estudiantes e ON g.id = e.grupo_id
            JOIN maestros_materias mm ON mm.maestro_id = :mm_maestro
            JOIN materias m ON mm.materia_id = m.id
            WHERE g.maestro_id = :g_maestro");
$db->bind(':mm_maestro', $maestro_id);
$db->bind(':g_maestro', $maestro_id);
$estadisticas = $db->single();

// Obtener grupos recientes
$db->query("SELECT g.id, g.nombre, g.grado, COUNT(e.id) as num_estudiantes
            FROM grupos g
            LEFT JOIN estudiantes e ON g.id = e.grupo_id
            WHERE g.maestro_id = :maestro_id
            GROUP BY g.id
            ORDER BY g.id DESC
            LIMIT 5");
$db->bind(':maestro_id', $maestro_id);
$grupos = $db->resultSet();

// Obtener materias asignadas
$db->query("SELECT m.id, m.nombre 
            FROM materias m
            JOIN maestros_materias mm ON m.id = mm.materia_id
            WHERE mm.maestro_id = :maestro_id
            ORDER BY m.nombre");
$db->bind(':maestro_id', $maestro_id);
$materias = $db->resultSet();

// Obtener actividades recientes con información de materia y grupo
$db->query("SELECT a.id, a.nombre, a.fecha_entrega, m.nombre as materia, g.nombre as grupo
            FROM actividades a
            JOIN materias m ON a.materia_id = m.id
            JOIN grupos g ON a.grupo_id = g.id
            JOIN maestros_materias mm ON mm.materia_id = m.id
            WHERE mm.maestro_id = :maestro_id
            ORDER BY a.fecha_entrega DESC
            LIMIT 5");
$db->bind(':maestro_id', $maestro_id);
$actividades = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <?php include './partials/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Panel del Maestro</h2>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer"
                    onclick="window.location.href='grupos.php'">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-500">Grupos</h3>
                            <p class="text-2xl font-bold"><?= $estadisticas->total_grupos ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-user-graduate text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-500">Estudiantes</h3>
                            <p class="text-2xl font-bold"><?= $estadisticas->total_estudiantes ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer"
                    onclick="window.location.href='materias.php'">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-book text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-500">Materias</h3>
                            <p class="text-2xl font-bold"><?= $estadisticas->total_materias ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Grupos y Materias -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Grupos Recientes -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold">Mis Grupos</h3>
                        <a href="grupos.php" class="text-blue-500 hover:text-blue-700">Ver todos</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left">Grupo</th>
                                    <th class="px-6 py-3 text-left">Grado</th>
                                    <th class="px-6 py-3 text-left">Estudiantes</th>
                                    <th class="px-6 py-3 text-left">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($grupos as $grupo): ?>
                                    <tr>
                                        <td class="px-6 py-4"><?= htmlspecialchars($grupo->nombre) ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($grupo->grado) ?></td>
                                        <td class="px-6 py-4"><?= $grupo->num_estudiantes ?></td>
                                        <td class="px-6 py-4">
                                            <a href="ver_grupo.php?id=<?= $grupo->id ?>"
                                                class="text-blue-500 hover:text-blue-700 mr-2">
                                                <i class="fas fa-eye mr-1"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Próximas Actividades -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold">Próximas Actividades</h3>
                        <a href="actividades.php" class="text-blue-500 hover:text-blue-700">Ver todas</a>
                    </div>
                    <div class="space-y-4">
                        <?php foreach ($actividades as $actividad): ?>
                            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                                <div>
                                    <h4 class="font-medium"><?= htmlspecialchars($actividad->nombre) ?></h4>
                                    <p class="text-sm text-gray-500">
                                        <?= htmlspecialchars($actividad->materia) ?> -
                                        <?= htmlspecialchars($actividad->grupo) ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium">Fecha de entrega</p>
                                    <p class="text-sm text-gray-500">
                                        <?= date('d/m/Y', strtotime($actividad->fecha_entrega)) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>


        </div>
    </div>
</body>

</html>