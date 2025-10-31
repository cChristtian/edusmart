<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([2]); // Solo rector

$db = new Database();

// Obtener lista de grupos para filtro
$db->query("SELECT * FROM grupos ORDER BY grado, nombre");
$grupos = $db->resultSet();

// Obtener lista de materias para filtro
$db->query("SELECT * FROM materias WHERE activa = 1 ORDER BY nombre");
$materias = $db->resultSet();

// Procesar filtros
$grupo_id = isset($_GET['grupo_id']) ? intval($_GET['grupo_id']) : null;
$materia_id = isset($_GET['materia_id']) ? intval($_GET['materia_id']) : null;
$trimestre = isset($_GET['trimestre']) ? intval($_GET['trimestre']) : 1;

// Obtener reporte según filtros
$reporte = [];
if ($grupo_id && $materia_id) {
    // Obtener información básica
    $db->query("SELECT g.nombre as grupo_nombre, g.grado, m.nombre as materia_nombre 
               FROM grupos g, materias m 
               WHERE g.id = :grupo_id AND m.id = :materia_id");
    $db->bind(':grupo_id', $grupo_id);
    $db->bind(':materia_id', $materia_id);
    $info_reporte = $db->single();

    // Obtener estudiantes y sus calificaciones
    $db->query("SELECT e.id, e.nombre_completo 
               FROM estudiantes e 
               WHERE e.grupo_id = :grupo_id  
               ORDER BY e.nombre_completo");
    $db->bind(':grupo_id', $grupo_id);
    $estudiantes = $db->resultSet();

    if ($trimestre == 0) {
        // Promedio final de todos los trimestres
        $reporte_final = [];

        foreach ($estudiantes as $estudiante) {
            $db->query("
            SELECT AVG(trimestre_promedio) AS promedio
            FROM (
                SELECT SUM(n.calificacion * a.porcentaje / 100) / SUM(a.porcentaje) AS trimestre_promedio
                FROM actividades a
                JOIN notas n ON n.actividad_id = a.id
                WHERE n.estudiante_id = :estudiante_id
                  AND a.materia_id = :materia_id
                GROUP BY a.trimestre
            ) t
        ");
            $db->bind(':estudiante_id', $estudiante->id);
            $db->bind(':materia_id', $materia_id);
            $promedio_final = $db->single()->promedio;

            $reporte_final[] = [
                'estudiante' => $estudiante->nombre_completo,
                'promedio' => $promedio_final !== null ? round($promedio_final, 2) : '0'
            ];
        }

    } else {
        // Promedio de un trimestre específico
        $reporte = [];

        foreach ($estudiantes as $estudiante) {
            $db->query("
            SELECT a.id, a.nombre, a.porcentaje, n.calificacion
            FROM actividades a
            LEFT JOIN notas n ON a.id = n.actividad_id AND n.estudiante_id = :estudiante_id
            WHERE a.grupo_id = :grupo_id
              AND a.materia_id = :materia_id
              AND a.trimestre = :trimestre
            ORDER BY a.id
        ");
            $db->bind(':estudiante_id', $estudiante->id);
            $db->bind(':grupo_id', $grupo_id);
            $db->bind(':materia_id', $materia_id);
            $db->bind(':trimestre', $trimestre);
            $actividades = $db->resultSet();

            $total_porcentaje = 0;
            $total_calificacion = 0;
            $calificaciones = [];

            foreach ($actividades as $actividad) {
                if ($actividad->calificacion !== null) {
                    $calificacion_ponderada = ($actividad->calificacion * $actividad->porcentaje) / 100;
                    $total_calificacion += $calificacion_ponderada;
                    $total_porcentaje += $actividad->porcentaje;

                    $calificaciones[] = [
                        'actividad' => $actividad->nombre,
                        'porcentaje' => $actividad->porcentaje,
                        'calificacion' => $actividad->calificacion,
                        'calificacion_ponderada' => $calificacion_ponderada
                    ];
                }
            }

            $promedio = $total_porcentaje > 0 ? ($total_calificacion / $total_porcentaje) * 100 : 'N/A';

            $reporte[] = [
                'estudiante' => $estudiante->nombre_completo,
                'actividades' => $calificaciones,
                'promedio' => is_numeric($promedio) ? round($promedio, 2) : '0',
                'total_porcentaje' => $total_porcentaje
            ];
        }
    }
}

$datos_mostrar = $trimestre == 0 ? $reporte_final : $reporte;

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar (repetir el mismo de dashboard.php) -->
        <?php include './partials/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Reportes Académicos</h2>

            <!-- Filtros -->
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h3 class="text-xl font-semibold mb-4">Filtrar Reporte</h3>

                <form action="reportes.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="grupo_id" class="block text-gray-700 mb-2">Grupo</label>
                        <select id="grupo_id" name="grupo_id"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Seleccionar Grupo</option>
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?php echo $grupo->id; ?>" <?php echo $grupo_id == $grupo->id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($grupo->nombre); ?> -
                                    <?php echo htmlspecialchars($grupo->grado); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="materia_id" class="block text-gray-700 mb-2">Materia</label>
                        <select id="materia_id" name="materia_id"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Seleccionar Materia</option>
                            <?php foreach ($materias as $materia): ?>
                                <option value="<?php echo $materia->id; ?>" <?php echo $materia_id == $materia->id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($materia->nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="trimestre" class="block text-gray-700 mb-2">Trimestre</label>
                        <select id="trimestre" name="trimestre"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1" <?= $trimestre == 1 ? 'selected' : ''; ?>>Primer Trimestre</option>
                            <option value="2" <?= $trimestre == 2 ? 'selected' : ''; ?>>Segundo Trimestre</option>
                            <option value="3" <?= $trimestre == 3 ? 'selected' : ''; ?>>Tercer Trimestre</option>
                            <option value="0" <?= $trimestre == 0 ? 'selected' : ''; ?>>Promedio Final</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit"
                            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 w-full">
                            Generar Reporte
                        </button>
                    </div>
                </form>
            </div>

            <!-- Resultados del reporte -->
            <?php if (($trimestre == 0 && !empty($reporte_final)) || ($trimestre != 0 && !empty($reporte))): ?>
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-xl font-semibold">Reporte de Calificaciones</h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($info_reporte->materia_nombre); ?> -
                                <?php echo htmlspecialchars($info_reporte->grupo_nombre); ?>
                                <?php echo htmlspecialchars($info_reporte->grado); ?>
                            </p>
                            <p class="text-gray-600"><?= $trimestre == 0 ? "Promedio Final" : "Trimestre: {$trimestre}" ?></p>
                        </div>
                        <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                            Imprimir Reporte
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estudiante</th>
                                    <?php if (!empty($reporte[0]['actividades'])): ?>
                                        <?php foreach ($reporte[0]['actividades'] as $actividad): ?>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                title="<?php echo htmlspecialchars($actividad['actividad']); ?> (<?php echo $actividad['porcentaje']; ?>%)">
                                                <?php echo substr($actividad['actividad'], 0, 15); ?>...
                                            </th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Promedio</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($datos_mostrar as $item): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo htmlspecialchars($item['estudiante']); ?>
                                        </td>
                                        <?php
                                        // Mostrar actividades solo si existen
                                        if (!empty($item['actividades'])):
                                            foreach ($item['actividades'] as $actividad): ?>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap <?php echo ($actividad['calificacion'] !== null && $actividad['calificacion'] < 60) ? 'text-red-500' : ''; ?>">
                                                    <?php echo $actividad['calificacion'] !== null ? $actividad['calificacion'] : '-'; ?>
                                                </td>
                                            <?php endforeach;
                                        endif;
                                        ?>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap font-semibold <?php echo $item['promedio'] < 60 ? 'text-red-500' : ''; ?>">
                                            <?php echo $item['promedio']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    // Calcular promedio general del grupo
                    $total_promedios = 0;
                    $total_estudiantes = 0;
                    foreach ($reporte as $item) {
                        if ($item['total_porcentaje'] > 0) {
                            $total_promedios += $item['promedio'];
                            $total_estudiantes++;
                        }
                    }
                    $promedio_general = $total_estudiantes > 0 ? $total_promedios / $total_estudiantes : 0;
                    ?>

                    <div class="mt-6 pt-4 border-t">
                        <p class="text-lg font-semibold">Promedio General del Grupo: <span
                                class="<?php echo $promedio_general < 60 ? 'text-red-500' : ''; ?>"><?php echo round($promedio_general, 2); ?></span>
                        </p>
                    </div>
                </div>
            <?php elseif (isset($_GET['grupo_id'])): ?>
                <div class="bg-white p-6 rounded-lg shadow text-center">
                    <p class="text-gray-500">No hay datos disponibles para los filtros seleccionados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>