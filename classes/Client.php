<?php
// classes/Client.php
require_once __DIR__ . '/../config/database.php';

class Client {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Adaugă client nou
    public function addClient($data) {
        $this->db->query('INSERT INTO clients (company_name, company_code, fiscal_code, address, phone, email) 
                           VALUES (:company_name, :company_code, :fiscal_code, :address, :phone, :email)');
        
        // Legare parametri
        $this->db->bind(':company_name', $data['company_name']);
        $this->db->bind(':company_code', $data['company_code']);
        $this->db->bind(':fiscal_code', $data['fiscal_code']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':email', $data['email']);
        
        // Executare
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    // Actualizare client
    public function updateClient($data) {
        $this->db->query('UPDATE clients SET 
                            company_name = :company_name, 
                            company_code = :company_code, 
                            fiscal_code = :fiscal_code, 
                            address = :address, 
                            phone = :phone, 
                            email = :email 
                          WHERE id = :id');
        
        // Legare parametri
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':company_name', $data['company_name']);
        $this->db->bind(':company_code', $data['company_code']);
        $this->db->bind(':fiscal_code', $data['fiscal_code']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':email', $data['email']);
        
        return $this->db->execute();
    }
    
    // Obține client după ID
    public function getClientById($id) {
        $this->db->query('SELECT * FROM clients WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Obține toți clienții
    public function getAllClients() {
        $this->db->query('SELECT * FROM clients ORDER BY company_name ASC');
        
        return $this->db->resultSet();
    }
    
    // Șterge client
    public function deleteClient($id) {
        $this->db->query('DELETE FROM clients WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Obține locațiile unui client
    public function getClientLocations($client_id) {
        $this->db->query('SELECT * FROM locations WHERE client_id = :client_id ORDER BY name ASC');
        $this->db->bind(':client_id', $client_id);
        
        return $this->db->resultSet();
    }
    
    // Adaugă locație pentru client
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
    
    // Șterge locație
    public function deleteLocation($id) {
        $this->db->query('DELETE FROM locations WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
}