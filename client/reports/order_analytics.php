<?php
// client/reports/order_analytics.php
// Pagina pentru raportul analitic al comenzilor

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Order.php';
require_once '../../classes/Client.php';
require_once '../../classes/Product.php';
require_once '../components/dashboard_stats.php';

// Inițializare obiecte
$orderObj = new Order();
$clientObj = new Client();
$productObj = new Product();
$statsObj = new DashboardStats($_SESSION['client_id']);

// Parametri filtrare
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$location_id = isset($_GET['location_id']) && is_numeric($_GET['location_id']) ? (int)$_GET['location_id'] : 0;

// Obține locațiile pentru filtrare
$locations = $clientObj->getClientLocations($_SESSION['client_id']);

// Obține statisticile de comenzi
$orderStats = $statsObj->getOrderStats();
$monthlyStats = $statsObj->getMonthlyOrderStats(12); // Ultimele 12 luni
$topProducts = $statsObj->getTopProducts(10); // Top 10 produse
$locationStats = $statsObj->getLocationStats();
$orderTrend = $statsObj->getOrderTrend();

// Pregătire date pentru grafice
$months = [];
$orderCounts = [];
$orderValues = [];

foreach ($monthlyStats as $stat) {
    $formattedMonth = date('M Y', strtotime($stat['month'] . '-01'));
    $months[] = $formattedMonth;
    $orderCounts[] = $stat['order_count'];
    $orderValues[] = $stat['order_value'];
}

// Titlu pagină
$pageTitle = 'Analiză Comenzi - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Analiză Comenzi</h1>
    
    <a href="../index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la dashboard
    </a>
</div>

<!-- Filtre raport -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="order_analytics.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Dată început</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>"
                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Dată sfârșit</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>"
                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        
        <div>
            <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">Locație</label>
            <select id="location_id" name="location_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate locațiile</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?php echo $location['id']; ?>" <?php echo $location_id == $location['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($location['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-filter mr-1"></i> Aplică filtre
            </button>
        </div>
    </form>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                <i class="fas fa-shopping-cart fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Total comenzi</p>
                <p class="text-2xl font-semibold"><?php echo $orderStats['total_orders'] ?? 0; ?></p>
                
                <?php if (isset($orderTrend['trend_percentage'])): ?>
                    <p class="text-xs text-<?php echo $orderTrend['trend_direction'] == 'up' ? 'green' : 'red'; ?>-600">
                        <i class="fas fa-arrow-<?php echo $orderTrend['trend_direction']; ?>"></i>
                        <?php echo number_format(abs($orderTrend['trend_percentage']), 1); ?>% față de luna anterioară
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                <i class="fas fa-money-bill-wave fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Valoare totală</p>
                <p class="text-2xl font-semibold"><?php echo formatAmount($orderStats['total_value'] ?? 0); ?> Lei</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                <i class="fas fa-clock fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">În așteptare</p>
                <p class="text-2xl font-semibold"><?php echo $orderStats['pending_orders'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-500 mr-4">
                <i class="fas fa-check-circle fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Finalizate</p>
                <p class="text-2xl font-semibold"><?php echo $orderStats['completed_orders'] ?? 0; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Grafice și analize -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Grafic evoluție comenzi -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Evoluție comenzi lunare</h2>
        </div>
        
        <div class="p-4">
            <canvas id="monthlyOrdersChart" height="300"></canvas>
        </div>
    </div>
    
    <!-- Grafic valoare comenzi -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Evoluție valoare comenzi</h2>
        </div>
        
        <div class="p-4">
            <canvas id="monthlyValuesChart" height="300"></canvas>
        </div>
    </div>
</div>

<!-- Top produse și locații -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Top produse comandate -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Top produse comandate</h2>
        </div>
        
        <div class="p-4">
            <?php if (count($topProducts) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Produs
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cantitate totală
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Comenzi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($product['code']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo number_format($product['total_quantity'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $product['order_count']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-gray-500">
                    Nu există date disponibile.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Statistici locații -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Comenzi per locație</h2>
        </div>
        
        <div class="p-4">
            <?php if (count($locationStats) > 0): ?>
                <canvas id="locationsChart" height="300"></canvas>
            <?php else: ?>
                <div class="text-center py-4 text-gray-500">
                    Nu există date disponibile.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Import Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Grafic evoluție comenzi
    const monthlyOrdersCtx = document.getElementById('monthlyOrdersChart').getContext('2d');
    new Chart(monthlyOrdersCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Număr comenzi',
                data: <?php echo json_encode($orderCounts); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Grafic valoare comenzi
    const monthlyValuesCtx = document.getElementById('monthlyValuesChart').getContext('2d');
    new Chart(monthlyValuesCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Valoare comenzi (Lei)',
                data: <?php echo json_encode($orderValues); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Grafic locații
    <?php if (count($locationStats) > 0): ?>
    const locationsCtx = document.getElementById('locationsChart').getContext('2d');
    new Chart(locationsCtx, {
        type: 'pie',
        data: {
            labels: <?php 
                $locationNames = array_map(function($loc) {
                    return $loc['name'];
                }, $locationStats);
                echo json_encode($locationNames); 
            ?>,
            datasets: [{
                data: <?php 
                    $locationOrders = array_map(function($loc) {
                        return $loc['order_count'];
                    }, $locationStats);
                    echo json_encode($locationOrders); 
                ?>,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.6)',
                    'rgba(16, 185, 129, 0.6)',
                    'rgba(245, 158, 11, 0.6)',
                    'rgba(139, 92, 246, 0.6)',
                    'rgba(239, 68, 68, 0.6)',
                    'rgba(56, 189, 248, 0.6)',
                    'rgba(236, 72, 153, 0.6)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>