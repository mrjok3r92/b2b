<?php
// classes/Order.php
require_once __DIR__ . '/../config/database.php';

class Order {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Generare număr de comandă unic
    private function generateOrderNumber() {
        $prefix = 'CMD';
        $year = date('Y');
        $month = date('m');
        
        // Obține ultimul număr de comandă din luna curentă
        $this->db->query('SELECT MAX(CAST(SUBSTRING(order_number, 12) AS UNSIGNED)) as max_number 
                          FROM orders 
                          WHERE order_number LIKE :pattern');
        
        $this->db->bind(':pattern', $prefix . $year . $month . '%');
        
        $result = $this->db->single();
        
        $next_number = 1;
        if ($result && $result['max_number']) {
            $next_number = $result['max_number'] + 1;
        }
        
        // Formatare număr comandă
        return $prefix . $year . $month . str_pad($next_number, 5, '0', STR_PAD_LEFT);
    }
    
    // Adaugă comandă nouă
    public function addOrder($data) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            $order_number = $this->generateOrderNumber();
            
            // Adaugă antetul comenzii
            $this->db->query('INSERT INTO orders (client_id, location_id, user_id, order_number, order_date, 
                                                 status, notes, total_amount) 
                              VALUES (:client_id, :location_id, :user_id, :order_number, :order_date, 
                                     :status, :notes, :total_amount)');
            
            // Legare parametri antet
            $this->db->bind(':client_id', $data['client_id']);
            $this->db->bind(':location_id', $data['location_id']);
            $this->db->bind(':user_id', $data['user_id']);
            $this->db->bind(':order_number', $order_number);
            $this->db->bind(':order_date', date('Y-m-d H:i:s'));
            $this->db->bind(':status', 'pending');
            $this->db->bind(':notes', $data['notes'] ?? '');
            $this->db->bind(':total_amount', $data['total_amount']);
            
            $this->db->execute();
            $order_id = $this->db->lastInsertId();
            
            // Adaugă detaliile comenzii
            foreach ($data['products'] as $product) {
                $this->db->query('INSERT INTO order_details (order_id, product_id, quantity, unit_price, amount) 
                                 VALUES (:order_id, :product_id, :quantity, :unit_price, :amount)');
                
                // Legare parametri detalii
                $this->db->bind(':order_id', $order_id);
                $this->db->bind(':product_id', $product['product_id']);
                $this->db->bind(':quantity', $product['quantity']);
                $this->db->bind(':unit_price', $product['unit_price']);
                $this->db->bind(':amount', $product['quantity'] * $product['unit_price']);
                
                $this->db->execute();
            }
            
            // Commit transaction
            $this->db->endTransaction();
            
            return $order_id;
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->db->cancelTransaction();
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    // Aprobă comandă
    public function approveOrder($order_id, $agent_id, $notes = '') {
        $this->db->query('UPDATE orders SET 
                            status = "approved", 
                            agent_id = :agent_id, 
                            approval_date = :approval_date,
                            notes = CONCAT(notes, :notes)
                          WHERE id = :order_id');
        
        // Legare parametri
        $this->db->bind(':order_id', $order_id);
        $this->db->bind(':agent_id', $agent_id);
        $this->db->bind(':approval_date', date('Y-m-d H:i:s'));
        $this->db->bind(':notes', $notes ? "\n\nAgent notes: " . $notes : '');
        
        return $this->db->execute();
    }
    
    // Respinge comandă
    public function rejectOrder($order_id, $agent_id, $notes = '') {
        $this->db->query('UPDATE orders SET 
                            status = "rejected", 
                            agent_id = :agent_id, 
                            approval_date = :approval_date,
                            notes = CONCAT(notes, :notes)
                          WHERE id = :order_id');
        
        // Legare parametri
        $this->db->bind(':order_id', $order_id);
        $this->db->bind(':agent_id', $agent_id);
        $this->db->bind(':approval_date', date('Y-m-d H:i:s'));
        $this->db->bind(':notes', $notes ? "\n\nRejection reason: " . $notes : '');
        
        return $this->db->execute();
    }
    
    // Obține comandă după ID
    public function getOrderById($id) {
        $this->db->query('SELECT o.*, 
                            c.company_name, 
                            l.name as location_name, 
                            CONCAT(u.first_name, " ", u.last_name) as user_name,
                            CONCAT(a.first_name, " ", a.last_name) as agent_name
                          FROM orders o
                          JOIN clients c ON o.client_id = c.id
                          JOIN locations l ON o.location_id = l.id
                          JOIN users u ON o.user_id = u.id
                          LEFT JOIN users a ON o.agent_id = a.id
                          WHERE o.id = :id');
                          
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Obține detaliile comenzii
    public function getOrderDetails($order_id) {
        $this->db->query('SELECT od.*, p.code, p.name, p.unit
                          FROM order_details od
                          JOIN products p ON od.product_id = p.id
                          WHERE od.order_id = :order_id');
                          
        $this->db->bind(':order_id', $order_id);
        
        return $this->db->resultSet();
    }
    
    // Obține toate comenzile
    public function getAllOrders() {
        $this->db->query('SELECT o.*, 
                            c.company_name, 
                            l.name as location_name, 
                            CONCAT(u.first_name, " ", u.last_name) as user_name
                          FROM orders o
                          JOIN clients c ON o.client_id = c.id
                          JOIN locations l ON o.location_id = l.id
                          JOIN users u ON o.user_id = u.id
                          ORDER BY o.order_date DESC');
        
        return $this->db->resultSet();
    }
    
    // Obține comenzile în așteptare
    public function getPendingOrders() {
        $this->db->query('SELECT o.*, 
                            c.company_name, 
                            l.name as location_name, 
                            CONCAT(u.first_name, " ", u.last_name) as user_name
                          FROM orders o
                          JOIN clients c ON o.client_id = c.id
                          JOIN locations l ON o.location_id = l.id
                          JOIN users u ON o.user_id = u.id
                          WHERE o.status = "pending"
                          ORDER BY o.order_date ASC');
        
        return $this->db->resultSet();
    }
    
    // Obține comenzile unui client
    public function getClientOrders($client_id) {
        $this->db->query('SELECT o.*, 
                            l.name as location_name, 
                            CONCAT(u.first_name, " ", u.last_name) as user_name,
                            CONCAT(a.first_name, " ", a.last_name) as agent_name
                          FROM orders o
                          JOIN locations l ON o.location_id = l.id
                          JOIN users u ON o.user_id = u.id
                          LEFT JOIN users a ON o.agent_id = a.id
                          WHERE o.client_id = :client_id
                          ORDER BY o.order_date DESC');
                          
        $this->db->bind(':client_id', $client_id);
        
        return $this->db->resultSet();
    }
    
    // Obține comenzile unei locații
    public function getLocationOrders($location_id) {
        $this->db->query('SELECT o.*, 
                            CONCAT(u.first_name, " ", u.last_name) as user_name,
                            CONCAT(a.first_name, " ", a.last_name) as agent_name
                          FROM orders o
                          JOIN users u ON o.user_id = u.id
                          LEFT JOIN users a ON o.agent_id = a.id
                          WHERE o.location_id = :location_id
                          ORDER BY o.order_date DESC');
                          
        $this->db->bind(':location_id', $location_id);
        
        return $this->db->resultSet();
    }
    public function getRecentApprovedOrders($limit = 5) {
        $this->db->query('SELECT o.*, 
                            c.company_name, 
                            l.name as location_name, 
                            CONCAT(u.first_name, " ", u.last_name) as user_name
                          FROM orders o
                          JOIN clients c ON o.client_id = c.id
                          JOIN locations l ON o.location_id = l.id
                          JOIN users u ON o.user_id = u.id
                          WHERE o.status = "approved"
                          ORDER BY o.approval_date DESC
                          LIMIT :limit');
        
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }

    public function getFilteredOrders($client_id = 0, $status = '', $date_from = '', $date_to = '', $search = '', $limit = 10, $offset = 0) {
        $sql = 'SELECT o.*, 
                  c.company_name, 
                  l.name as location_name, 
                  CONCAT(u.first_name, " ", u.last_name) as user_name
                FROM orders o
                JOIN clients c ON o.client_id = c.id
                JOIN locations l ON o.location_id = l.id
                JOIN users u ON o.user_id = u.id
                WHERE 1=1';
        
        // Adaugă condiții de filtrare
        if ($client_id > 0) {
            $sql .= ' AND o.client_id = :client_id';
        }
        
        if (!empty($status)) {
            $sql .= ' AND o.status = :status';
        }
        
        if (!empty($date_from)) {
            $sql .= ' AND DATE(o.order_date) >= :date_from';
        }
        
        if (!empty($date_to)) {
            $sql .= ' AND DATE(o.order_date) <= :date_to';
        }
        
        if (!empty($search)) {
            $sql .= ' AND (o.order_number LIKE :search OR c.company_name LIKE :search)';
        }
        
        $sql .= ' ORDER BY o.order_date DESC LIMIT :limit OFFSET :offset';
        
        $this->db->query($sql);
        
        // Legare parametri
        if ($client_id > 0) {
            $this->db->bind(':client_id', $client_id);
        }
        
        if (!empty($status)) {
            $this->db->bind(':status', $status);
        }
        
        if (!empty($date_from)) {
            $this->db->bind(':date_from', $date_from);
        }
        
        if (!empty($date_to)) {
            $this->db->bind(':date_to', $date_to);
        }
        
        if (!empty($search)) {
            $this->db->bind(':search', '%' . $search . '%');
        }
        
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    // Numără comenzile filtrate
    public function countFilteredOrders($client_id = 0, $status = '', $date_from = '', $date_to = '', $search = '') {
        $sql = 'SELECT COUNT(*) as count 
                FROM orders o
                JOIN clients c ON o.client_id = c.id
                WHERE 1=1';
        
        // Adaugă condiții de filtrare
        if ($client_id > 0) {
            $sql .= ' AND o.client_id = :client_id';
        }
        
        if (!empty($status)) {
            $sql .= ' AND o.status = :status';
        }
        
        if (!empty($date_from)) {
            $sql .= ' AND DATE(o.order_date) >= :date_from';
        }
        
        if (!empty($date_to)) {
            $sql .= ' AND DATE(o.order_date) <= :date_to';
        }
        
        if (!empty($search)) {
            $sql .= ' AND (o.order_number LIKE :search OR c.company_name LIKE :search)';
        }
        
        $this->db->query($sql);
        
        // Legare parametri
        if ($client_id > 0) {
            $this->db->bind(':client_id', $client_id);
        }
        
        if (!empty($status)) {
            $this->db->bind(':status', $status);
        }
        
        if (!empty($date_from)) {
            $this->db->bind(':date_from', $date_from);
        }
        
        if (!empty($date_to)) {
            $this->db->bind(':date_to', $date_to);
        }
        
        if (!empty($search)) {
            $this->db->bind(':search', '%' . $search . '%');
        }
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    // Numără comenzile după status
    public function countOrdersByStatus($status) {
        $this->db->query('SELECT COUNT(*) as count FROM orders WHERE status = :status');
        $this->db->bind(':status', $status);
        
        $result = $this->db->single();
        return $result['count'];
    }
}