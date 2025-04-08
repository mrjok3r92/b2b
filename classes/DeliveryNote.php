<?php
// classes/DeliveryNote.php
require_once __DIR__ . '/../config/database.php';

class DeliveryNote {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Generare număr de aviz unic
    private function generateDeliveryNoteNumber() {
        $prefix = 'AVZ';
        $year = date('Y');
        $month = date('m');
        
        // Obține ultimul număr de aviz din luna curentă
        $this->db->query('SELECT MAX(CAST(SUBSTRING(delivery_note_number, 12) AS UNSIGNED)) as max_number 
                          FROM delivery_notes 
                          WHERE delivery_note_number LIKE :pattern');
        
        $this->db->bind(':pattern', $prefix . $year . $month . '%');
        
        $result = $this->db->single();
        
        $next_number = 1;
        if ($result && $result['max_number']) {
            $next_number = $result['max_number'] + 1;
        }
        
        // Formatare număr aviz
        return $prefix . $year . $month . str_pad($next_number, 5, '0', STR_PAD_LEFT);
    }
    
    // Creează aviz pentru o comandă
    public function createDeliveryNote($order_id, $products = [], $notes = '') {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Verifică dacă comanda este aprobată
            $this->db->query('SELECT * FROM orders WHERE id = :order_id AND status = "approved"');
            $this->db->bind(':order_id', $order_id);
            
            $order = $this->db->single();
            if (!$order) {
                throw new Exception("Comanda nu este aprobată sau nu există.");
            }
            
            $delivery_number = $this->generateDeliveryNoteNumber();
            
            // Adaugă antetul avizului
            $this->db->query('INSERT INTO delivery_notes (order_id, delivery_note_number, delivery_date, status, notes) 
                              VALUES (:order_id, :delivery_note_number, :delivery_date, :status, :notes)');
            
            // Legare parametri antet
            $this->db->bind(':order_id', $order_id);
            $this->db->bind(':delivery_note_number', $delivery_number);
            $this->db->bind(':delivery_date', date('Y-m-d H:i:s'));
            $this->db->bind(':status', 'pending');
            $this->db->bind(':notes', $notes);
            
            $this->db->execute();
            $delivery_note_id = $this->db->lastInsertId();
            
            // Dacă nu sunt specificate produse, folosește toate produsele din comandă
            if (empty($products)) {
                $this->db->query('SELECT product_id, quantity FROM order_details WHERE order_id = :order_id');
                $this->db->bind(':order_id', $order_id);
                $products = $this->db->resultSet();
            }
            
            // Adaugă detaliile avizului
            foreach ($products as $product) {
                $this->db->query('INSERT INTO delivery_note_details (delivery_note_id, product_id, quantity) 
                                 VALUES (:delivery_note_id, :product_id, :quantity)');
                
                // Legare parametri detalii
                $this->db->bind(':delivery_note_id', $delivery_note_id);
                $this->db->bind(':product_id', $product['product_id']);
                $this->db->bind(':quantity', $product['quantity']);
                
                $this->db->execute();
            }
            
            // Actualizează statusul comenzii la completed dacă este cazul
            $this->db->query('UPDATE orders SET status = "completed" WHERE id = :order_id');
            $this->db->bind(':order_id', $order_id);
            $this->db->execute();
            
            // Commit transaction
            $this->db->endTransaction();
            
            return $delivery_note_id;
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->db->cancelTransaction();
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    // Marchează avizul ca livrat
    public function markAsDelivered($id) {
        $this->db->query('UPDATE delivery_notes SET status = "delivered" WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Obține aviz după ID
    public function getDeliveryNoteById($id) {
        $this->db->query('SELECT dn.*, 
                            o.order_number, o.client_id,
                            c.company_name, 
                            l.name as location_name, l.address as location_address
                          FROM delivery_notes dn
                          JOIN orders o ON dn.order_id = o.id
                          JOIN clients c ON o.client_id = c.id
                          JOIN locations l ON o.location_id = l.id
                          WHERE dn.id = :id');
                          
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Obține detaliile avizului
    public function getDeliveryNoteDetails($delivery_note_id) {
        $this->db->query('SELECT dnd.*, p.code, p.name, p.unit
                          FROM delivery_note_details dnd
                          JOIN products p ON dnd.product_id = p.id
                          WHERE dnd.delivery_note_id = :delivery_note_id');
                          
        $this->db->bind(':delivery_note_id', $delivery_note_id);
        
        return $this->db->resultSet();
    }
    
    // Obține toate avizele
    public function getAllDeliveryNotes() {
        $this->db->query('SELECT dn.*, 
                            o.order_number, 
                            c.company_name, 
                            l.name as location_name
                          FROM delivery_notes dn
                          JOIN orders o ON dn.order_id = o.id
                          JOIN clients c ON o.client_id = c.id
                          JOIN locations l ON o.location_id = l.id
                          ORDER BY dn.delivery_date DESC');
        
        return $this->db->resultSet();
    }
    
    // Obține avizele unui client
    public function getClientDeliveryNotes($client_id) {
        $this->db->query('SELECT dn.*, 
                            o.order_number, 
                            l.name as location_name
                          FROM delivery_notes dn
                          JOIN orders o ON dn.order_id = o.id
                          JOIN locations l ON o.location_id = l.id
                          WHERE o.client_id = :client_id
                          ORDER BY dn.delivery_date DESC');
                          
        $this->db->bind(':client_id', $client_id);
        
        return $this->db->resultSet();
    }
    
    // Obține avizele unei comenzi
    public function getOrderDeliveryNotes($order_id) {
        $this->db->query('SELECT dn.* 
                          FROM delivery_notes dn
                          WHERE dn.order_id = :order_id
                          ORDER BY dn.delivery_date DESC');
                          
        $this->db->bind(':order_id', $order_id);
        
        return $this->db->resultSet();
    }
}