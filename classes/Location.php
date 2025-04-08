<?php
// classes/Location.php
require_once __DIR__ . '/../config/database.php';

class Location {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Adaugă o locație nouă
    public function addLocation($data) {
        $this->db->query('INSERT INTO locations (client_id, name, address, contact_person, phone, email) 
                          VALUES (:client_id, :name, :address, :contact_person, :phone, :email)');
        
        // Legare parametri
        $this->db->bind(':client_id', $data['client_id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':contact_person', $data['contact_person']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':email', $data['email']);
        
        // Executare
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    // Actualizare locație
    public function updateLocation($data) {
        $this->db->query('UPDATE locations SET 
                            name = :name, 
                            address = :address, 
                            contact_person = :contact_person, 
                            phone = :phone, 
                            email = :email 
                          WHERE id = :id AND client_id = :client_id');
        
        // Legare parametri
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':client_id', $data['client_id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':contact_person', $data['contact_person']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':email', $data['email']);
        
        return $this->db->execute();
    }
    
    // Obține locația după ID
    public function getLocationById($id) {
        $this->db->query('SELECT * FROM locations WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Obține toate locațiile
    public function getAllLocations() {
        $this->db->query('SELECT l.*, c.company_name as client_name 
                          FROM locations l
                          JOIN clients c ON l.client_id = c.id
                          ORDER BY c.company_name, l.name');
        
        return $this->db->resultSet();
    }
    
    // Obține locațiile unui client
    public function getClientLocations($client_id) {
        $this->db->query('SELECT * FROM locations WHERE client_id = :client_id ORDER BY name');
        $this->db->bind(':client_id', $client_id);
        
        return $this->db->resultSet();
    }
    
    // Verifică dacă o locație aparține unui client
    public function isClientLocation($location_id, $client_id) {
        $this->db->query('SELECT id FROM locations WHERE id = :id AND client_id = :client_id');
        $this->db->bind(':id', $location_id);
        $this->db->bind(':client_id', $client_id);
        
        $result = $this->db->single();
        return $result ? true : false;
    }
    
    // Șterge locație
    public function deleteLocation($id) {
        $this->db->query('DELETE FROM locations WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Verifică dacă o locație are comenzi asociate
    public function hasOrders($location_id) {
        $this->db->query('SELECT COUNT(*) as count FROM orders WHERE location_id = :location_id');
        $this->db->bind(':location_id', $location_id);
        
        $result = $this->db->single();
        return $result['count'] > 0;
    }
    
    // Verifică dacă o locație are utilizatori asociați
    public function hasUsers($location_id) {
        $this->db->query('SELECT COUNT(*) as count FROM users WHERE location_id = :location_id');
        $this->db->bind(':location_id', $location_id);
        
        $result = $this->db->single();
        return $result['count'] > 0;
    }
    
    // Obține utilizatorii asociați unei locații
    public function getLocationUsers($location_id) {
        $this->db->query('SELECT * FROM users WHERE location_id = :location_id');
        $this->db->bind(':location_id', $location_id);
        
        return $this->db->resultSet();
    }
    
    // Obține numărul de comenzi pentru o locație
    public function getOrderCount($location_id) {
        $this->db->query('SELECT COUNT(*) as count FROM orders WHERE location_id = :location_id');
        $this->db->bind(':location_id', $location_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    // Obține valoarea totală a comenzilor pentru o locație
    public function getTotalOrderValue($location_id) {
        $this->db->query('SELECT SUM(total_amount) as total FROM orders WHERE location_id = :location_id');
        $this->db->bind(':location_id', $location_id);
        
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }
    
    // Obține statistici pentru locații (comenzi și valoare)
    public function getLocationStats($client_id) {
        $this->db->query('SELECT l.id, l.name, 
                            COUNT(o.id) as order_count,
                            SUM(o.total_amount) as total_value
                          FROM locations l
                          LEFT JOIN orders o ON l.id = o.location_id
                          WHERE l.client_id = :client_id
                          GROUP BY l.id
                          ORDER BY total_value DESC');
        
        $this->db->bind(':client_id', $client_id);
        
        return $this->db->resultSet();
    }
    
    // Caută locații după nume sau adresă
    public function searchLocations($search_term, $client_id = null) {
        if ($client_id) {
            // Caută doar locațiile unui client
            $this->db->query('SELECT * FROM locations 
                              WHERE client_id = :client_id 
                                AND (name LIKE :search OR address LIKE :search)
                              ORDER BY name');
            $this->db->bind(':client_id', $client_id);
        } else {
            // Caută toate locațiile
            $this->db->query('SELECT l.*, c.company_name as client_name 
                              FROM locations l
                              JOIN clients c ON l.client_id = c.id
                              WHERE l.name LIKE :search OR l.address LIKE :search
                              ORDER BY c.company_name, l.name');
        }
        
        $this->db->bind(':search', '%' . $search_term . '%');
        
        return $this->db->resultSet();
    }
}