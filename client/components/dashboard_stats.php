<?php
// client/components/dashboard_stats.php
// Componenta pentru afișarea statisticilor în dashboard-ul clientului

class DashboardStats {
    private $db;
    private $client_id;
    
    public function __construct($client_id) {
        $this->db = new Database();
        $this->client_id = $client_id;
    }
    
    // Obține statisticile generale despre comenzi
    public function getOrderStats() {
        $this->db->query("SELECT 
                            COUNT(*) as total_orders,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_orders,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_orders,
                            SUM(total_amount) as total_value
                          FROM orders 
                          WHERE client_id = :client_id");
        
        $this->db->bind(':client_id', $this->client_id);
        
        return $this->db->single();
    }
    
    // Obține statisticile lunare pentru comenzi
    public function getMonthlyOrderStats($months = 6) {
        $this->db->query("SELECT 
                            DATE_FORMAT(order_date, '%Y-%m') as month,
                            COUNT(*) as order_count,
                            SUM(total_amount) as order_value
                          FROM orders 
                          WHERE client_id = :client_id
                            AND order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL :months MONTH)
                          GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                          ORDER BY month ASC");
        
        $this->db->bind(':client_id', $this->client_id);
        $this->db->bind(':months', $months);
        
        return $this->db->resultSet();
    }
    
    // Obține produsele cele mai comandate
    public function getTopProducts($limit = 5) {
        $this->db->query("SELECT 
                            p.id, p.name, p.code, 
                            SUM(od.quantity) as total_quantity,
                            COUNT(DISTINCT o.id) as order_count
                          FROM order_details od
                          JOIN orders o ON od.order_id = o.id
                          JOIN products p ON od.product_id = p.id
                          WHERE o.client_id = :client_id
                          GROUP BY p.id
                          ORDER BY total_quantity DESC
                          LIMIT :limit");
        
        $this->db->bind(':client_id', $this->client_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    // Obține statisticile pentru locații
    public function getLocationStats() {
        $this->db->query("SELECT 
                            l.id, l.name,
                            COUNT(o.id) as order_count,
                            SUM(o.total_amount) as total_value
                          FROM locations l
                          LEFT JOIN orders o ON l.id = o.location_id AND o.client_id = :client_id
                          WHERE l.client_id = :client_id
                          GROUP BY l.id
                          ORDER BY total_value DESC");
        
        $this->db->bind(':client_id', $this->client_id);
        
        return $this->db->resultSet();
    }
    
    // Obține statistici despre utilizatori
    public function getUserStats() {
        $this->db->query("SELECT 
                            u.id, CONCAT(u.first_name, ' ', u.last_name) as name,
                            COUNT(o.id) as order_count,
                            SUM(o.total_amount) as total_value
                          FROM users u
                          LEFT JOIN orders o ON u.id = o.user_id
                          WHERE u.client_id = :client_id
                          GROUP BY u.id
                          ORDER BY order_count DESC");
        
        $this->db->bind(':client_id', $this->client_id);
        
        return $this->db->resultSet();
    }
    
    // Calculează tendința comenzilor (creștere/scădere procentuală)
    public function getOrderTrend() {
        // Obține suma comenzilor din luna curentă
        $this->db->query("SELECT 
                            SUM(total_amount) as current_month_value
                          FROM orders 
                          WHERE client_id = :client_id
                            AND MONTH(order_date) = MONTH(CURRENT_DATE())
                            AND YEAR(order_date) = YEAR(CURRENT_DATE())");
        
        $this->db->bind(':client_id', $this->client_id);
        $current = $this->db->single();
        
        // Obține suma comenzilor din luna anterioară
        $this->db->query("SELECT 
                            SUM(total_amount) as previous_month_value
                          FROM orders 
                          WHERE client_id = :client_id
                            AND MONTH(order_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                            AND YEAR(order_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))");
        
        $this->db->bind(':client_id', $this->client_id);
        $previous = $this->db->single();
        
        $currentValue = $current['current_month_value'] ?? 0;
        $previousValue = $previous['previous_month_value'] ?? 0;
        
        // Calculare tendință
        $trend = 0;
        if ($previousValue > 0) {
            $trend = (($currentValue - $previousValue) / $previousValue) * 100;
        } elseif ($currentValue > 0) {
            $trend = 100; // Creștere de la 0 la ceva => 100%
        }
        
        return [
            'current_value' => $currentValue,
            'previous_value' => $previousValue,
            'trend_percentage' => $trend,
            'trend_direction' => $trend >= 0 ? 'up' : 'down'
        ];
    }
}