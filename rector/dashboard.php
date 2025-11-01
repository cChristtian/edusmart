<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([2]); // Solo rector
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Rector - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <?php include './partials/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Panel del Rector</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fa-solid fa-person-chalkboard"></i>
                    </div>
                    <div class="">
                        <h3 class="text-xl font-semibold mb-2">Total Maestros</h3>
                        <?php
                        $db = new Database();
                        $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 3");
                        $result = $db->single();
                        ?>
                        <p class="text-3xl font-bold"><?php echo $result->total; ?></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="">
                        <h3 class="text-xl font-semibold mb-2">Total Grupos</h3>
                        <?php
                        $db->query("SELECT COUNT(*) as total FROM grupos");
                        $result = $db->single();
                        ?>
                        <p class="text-3xl font-bold"><?php echo $result->total; ?></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-user-graduate text-xl"></i>
                    </div>
                    <div class="">
                        <h3 class="text-xl font-semibold mb-2">Total Estudiantes</h3>
                        <?php
                        $db->query("SELECT COUNT(*) as total FROM estudiantes");
                        $result = $db->single();
                        ?>
                        <p class="text-3xl font-bold"><?php echo $result->total; ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Últimos maestros registrados -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-4">Últimos Maestros Registrados</h3>
                    <?php
                    $db->query("SELECT u.* FROM usuarios u WHERE u.rol_id = 3 ORDER BY u.fecha_creacion DESC LIMIT 5");
                    $maestros = $db->resultSet();
                    ?>

                    <div class="space-y-4">
                        <?php foreach ($maestros as $maestro): ?>
                            <div class="flex items-center justify-between border-b pb-2">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($maestro->nombre_completo); ?></p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($maestro->fecha_creacion)); ?>
                                    </p>
                                </div>
                                <a href="maestros.php?ver=<?php echo $maestro->id; ?>"
                                    class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                            </div>
                        <?php endforeach; ?>

                        <div class="mt-4">
                            <a href="maestros.php" class="text-blue-500 hover:text-blue-700">Ver todos los maestros
                                →</a>
                        </div>
                    </div>
                </div>

                <!-- Grupos recientes -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-4">Grupos Recientes</h3>
                    <?php
                    $db->query("SELECT g.*, u.nombre_completo as maestro FROM grupos g 
                              LEFT JOIN usuarios u ON g.maestro_id = u.id 
                              ORDER BY g.id DESC LIMIT 5");
                    $grupos = $db->resultSet();
                    ?>

                    <div class="space-y-4">
                        <?php foreach ($grupos as $grupo): ?>
                            <div class="flex items-center justify-between border-b pb-2">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($grupo->nombre); ?> -
                                        <?php echo htmlspecialchars($grupo->grado); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo $grupo->maestro ? 'Maestro: ' . htmlspecialchars($grupo->maestro) : 'Sin maestro asignado'; ?>
                                    </p>
                                </div>
                                <a href="grupos.php?ver=<?php echo $grupo->id; ?>"
                                    class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                            </div>
                        <?php endforeach; ?>

                        <div class="mt-4">
                            <a href="grupos.php" class="text-blue-500 hover:text-blue-700">Ver todos los grupos →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>