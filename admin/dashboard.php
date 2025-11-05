<?php
require_once __DIR__ . '/../includes/config.php';
protegerPagina([1]); 

$adminID = $_SESSION['user_id'];
$db = new Database();

// contadores generales 
$db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id != 1");
$totalUsuarios = $db->single()->total ?? 0;

$db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 3");
$totalMaestros = $db->single()->total ?? 0;

$db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2");
$totalRectores = $db->single()->total ?? 0;

// historial usuarios 
$db->query("
    SELECT u.*, r.nombre as rol 
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    WHERE u.rol_id != 1 
    ORDER BY u.fecha_creacion DESC 
    LIMIT 2
");
$usuarios = $db->resultSet();

// usuario por rol
$db->query("
    SELECT r.nombre AS rol, COUNT(u.id) AS total 
    FROM roles r 
    LEFT JOIN usuarios u ON u.rol_id = r.id 
    WHERE r.id != 1 
    GROUP BY r.nombre
");
$rolesData = $db->resultSet();

$rolesLabels = [];
$rolesCounts = [];
$totalRoles = 0;
foreach ($rolesData as $r) {
    $rolesLabels[] = $r->rol;
    $rolesCounts[] = (int) $r->total;
    $totalRoles += $r->total;
}

// activo o inactivo
$db->query("SELECT activo, COUNT(*) as total FROM usuarios WHERE rol_id != 1 GROUP BY activo");
$activosRaw = $db->resultSet();
$activoCount = 0;
$inactivoCount = 0;
foreach ($activosRaw as $a) {
    if ($a->activo == 1)
        $activoCount = (int) $a->total;
    else
        $inactivoCount = (int) $a->total;
}

// historial actividades mensuales
$db->query("
    SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') AS mes, COUNT(*) AS total
    FROM actividades
    WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY mes
    ORDER BY mes ASC
");
$actividadesRaw = $db->resultSet();

$meses = [];
$totalesMes = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-{$i} months"));
    $date = new DateTime($mes . '-01');
    $mesTexto = ucfirst($date->format('M'));
    $meses[] = $mesTexto;
    $encontrado = false;
    foreach ($actividadesRaw as $dato) {
        if ($dato->mes === $mes) {
            $totalesMes[] = (int) $dato->total * 1.6;
            $encontrado = true;
            break;
        }
    }
    if (!$encontrado)
        $totalesMes[] = 0;
}

// bitácora 
$db->query("
    SELECT usuario_sistema, fecha_hora_sistema, nombre_tabla, accion, modulo 
    FROM bitacora 
    ORDER BY fecha_hora_sistema DESC 
    LIMIT 4
");
$bitacora = $db->resultSet();

$rolesLabelsJson = json_encode($rolesLabels);
$rolesCountsJson = json_encode($rolesCounts);
$mesesJson = json_encode($meses);
$totalesMesJson = json_encode($totalesMes);
$activoInactivoJson = json_encode([$activoCount, $inactivoCount]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartBoard - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" />
    <style>
        body { background-color: #f7f9fc; }
        .chart-fixed { width: 100%; height: 240px; }
        .card { background: white; border-radius: 0.9rem; box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08); }
        .fade-in { animation: fadeIn 0.4s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .tag { font-size: 0.75rem; background-color: #dbeafe; color: #1e40af; padding: 0.25rem 0.6rem; border-radius: 9999px; }
    </style>
</head>

<body class="min-h-screen">
    <div class="flex h-screen w-screen overflow-hidden">
        <?php require_once('./partials/sidebar.php') ?>

        <main class="flex-1 p-6 space-y-6 fade-in overflow-y-auto">
            <header class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-chart-line text-blue-600"></i> Panel de Administración
                    </h1>
                    <p class="text-gray-500 mt-1">Visión general y métricas del sistema</p>
                </div>
                <div class="text-sm text-gray-400">
                    <?php echo date('d M Y'); ?>
                </div>
            </header>

            <!-- estadisticas -->
            <div class="grid grid-cols-3 gap-4">
                <div class="card p-5 flex justify-between items-center border-l-4 border-blue-500">
                    <div>
                        <h3 class="text-gray-500 text-sm uppercase">Usuarios Totales</h3>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $totalUsuarios; ?></p>
                    </div>
                    <div class="p-3 bg-blue-100 text-blue-600 rounded-full"><i class="fa-solid fa-users fa-lg"></i></div>
                </div>
                <div class="card p-5 flex justify-between items-center border-l-4 border-green-500">
                    <div>
                        <h3 class="text-gray-500 text-sm uppercase">Maestros</h3>
                        <p class="text-2xl font-bold text-green-600"><?php echo $totalMaestros; ?></p>
                    </div>
                    <div class="p-3 bg-green-100 text-green-600 rounded-full"><i class="fa-solid fa-user-graduate fa-lg"></i></div>
                </div>
                <div class="card p-5 flex justify-between items-center border-l-4 border-cyan-500">
                    <div>
                        <h3 class="text-gray-500 text-sm uppercase">Rectores</h3>
                        <p class="text-2xl font-bold text-cyan-600"><?php echo $totalRectores; ?></p>
                    </div>
                    <div class="p-3 bg-cyan-100 text-cyan-600 rounded-full"><i class="fa-solid fa-chalkboard-teacher fa-lg"></i></div>
                </div>
            </div>

            <!-- graficas -->
            <div class="grid grid-cols-3 gap-6">
                <div class="col-span-2 grid grid-cols-3 gap-4">
                    <div class="card p-4 flex flex-col justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Actividades creadas</h3>
                        <canvas id="chartMeses" class="chart-fixed"></canvas>
                    </div>
                    <div class="card p-4 flex flex-col justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Activos vs Inactivos</h3>
                        <canvas id="chartActivo" class="chart-fixed"></canvas>
                    </div>
                    <div class="card p-4 flex flex-col justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Distribución por rol</h3>
                        <canvas id="chartRoles" class="chart-fixed"></canvas>
                    </div>
                </div>

                <aside class="card flex flex-col justify-between p-5">
                    <div>
                        <h3 class="text-md font-semibold mb-3 text-gray-700">Usuarios por Rol</h3>
                        <?php foreach ($rolesData as $r):
                            $porcentaje = $totalRoles > 0 ? round(($r->total / $totalRoles) * 100) : 0;
                            ?>
                            <div class="mb-3">
                                <div class="flex justify-between text-sm text-gray-500 mb-1">
                                    <span><?php echo htmlspecialchars($r->rol); ?></span>
                                    <span><?php echo $porcentaje; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-100 h-2 rounded-full">
                                    <div class="h-2 bg-blue-500 rounded-full" style="width:<?php echo $porcentaje; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <h3 class="text-gray-500 text-xs mb-1">Hora actual</h3>
                        <p id="hora" class="text-2xl font-bold text-blue-600 mb-1"></p>
                        <p id="fecha" class="text-xs text-gray-500"></p>
                    </div>
                </aside>
            </div>

            <!-- ultimos usuarios y actividad -->
            <div class="grid grid-cols-2 gap-6">
                <div class="card p-5">
                    <h3 class="text-md font-semibold mb-4 text-gray-700">
                        <i class="fa-solid fa-user-clock text-blue-500 mr-2"></i>
                        Últimos usuarios registrados
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <?php foreach ($usuarios as $usuario): ?>
                            <div class="border border-gray-100 rounded-lg p-4 hover:shadow-md transition-all bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 text-white font-semibold">
                                        <?php echo strtoupper(substr($usuario->nombre_completo, 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($usuario->nombre_completo); ?></p>
                                        <p class="text-xs text-gray-500">@<?php echo htmlspecialchars($usuario->username); ?></p>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-gray-600">
                                    <p><i class="fa-regular fa-calendar mr-1 text-green-500"></i><?php echo date('d/m/Y', strtotime($usuario->fecha_creacion)); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!--  historial -->
                <div class="card p-5">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-md font-semibold text-gray-700 flex items-center gap-2">
                            <i class="fa-solid fa-bell text-blue-500"></i> Actividad Reciente
                        </h3>
                        <span class="tag">Tiempo real</span>
                    </div>
                    <div class="space-y-3 text-sm">
                        <?php foreach ($bitacora as $item): ?>
                            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                               <span class="text-gray-700">
                                    <i class="fa-solid 
                                       <?php 
                                        echo $item->accion === 'INSERT' ? 'fa-plus text-green-500' : 
                                            ($item->accion === 'UPDATE' ? 'fa-pen text-yellow-500' : 'fa-trash text-red-500');
                                       ?> mr-2"></i>
                                     <span class="font-normal">
                                         Se realizó este cambio en <?php echo htmlspecialchars($item->nombre_tabla); ?>
                                     </span>
                                </span>

                                <span class="text-gray-400 text-xs">
                                    <?php echo date('H:i:s', strtotime($item->fecha_hora_sistema)); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // datos
        const meses = <?php echo $mesesJson; ?>;
        const totalesMes = <?php echo $totalesMesJson; ?>;
        const activoInactivo = <?php echo $activoInactivoJson; ?>;
        const rolesLabels = <?php echo $rolesLabelsJson; ?>;
        const rolesCounts = <?php echo $rolesCountsJson; ?>;

        function crearGraficoFijo(id, config) {
            const ctx = document.getElementById(id);
            config.options = config.options || {};
            config.options.responsive = true;
            config.options.maintainAspectRatio = true;
            return new Chart(ctx, config);
        }
    // graficas
        crearGraficoFijo('chartMeses', {
            type: 'bar',
            data: { labels: meses, datasets: [{ data: totalesMes, backgroundColor: '#2563eb', borderRadius: 6 }] },
            options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
        });
        crearGraficoFijo('chartActivo', {
            type: 'doughnut',
            data: { labels: ['Activos', 'Inactivos'], datasets: [{ data: activoInactivo, backgroundColor: ['#22c55e', '#eab308'] }] },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
        crearGraficoFijo('chartRoles', {
            type: 'pie',
            data: { labels: rolesLabels, datasets: [{ data: rolesCounts, backgroundColor: ['#2563eb', '#22c55e', '#06b6d4', '#a78bfa', '#f97316'] }] },
            options: { plugins: { legend: { position: 'bottom' } } }
        });

        // Reloj
        function actualizarReloj() {
            const now = new Date();
            const hora = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const fecha = now.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('hora').textContent = hora;
            document.getElementById('fecha').textContent = fecha.charAt(0).toUpperCase() + fecha.slice(1);
        }
        setInterval(actualizarReloj, 1000);
        actualizarReloj();
    </script>
</body>
</html>