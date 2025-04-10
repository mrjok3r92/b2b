<?php
// classes/User.php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Înregistrare utilizator nou
    public function register($data) {
        // Verificare email duplicat
        if ($this->findUserByEmail($data['email'])) {
            return false;
        }
        
        // Criptare parolă
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Pregătirea interogării
        $this->db->query('INSERT INTO users (client_id, location_id, first_name, last_name, email, password, role) 
                           VALUES (:client_id, :location_id, :first_name, :last_name, :email, :password, :role)');
        
        // Legare parametri
        $this->db->bind(':client_id', $data['client_id']);
        $this->db->bind(':location_id', $data['location_id']);
        $this->db->bind(':first_name', $data['first_name']);
        $this->db->bind(':last_name', $data['last_name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $data['password']);
        $this->db->bind(':role', $data['role']);
        
        // Executare
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    // Autentificare utilizator
    public function login($email, $password) {
        $this->db->query('SELECT * FROM users WHERE email = :email AND status = "active"');
        $this->db->bind(':email', $email);
        
        $row = $this->db->single();
        
        if (!$row) {
            return false;
        }
        
        $hashed_password = $row['password'];
        
        if (password_verify($password, $hashed_password)) {
            return $row;
        } else {
            return false;
        }
    }
    
    // Găsește utilizator după email
    public function findUserByEmail($email) {
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        
        $row = $this->db->single();
        
        return $row ? true : false;
    }
    
    // Obține utilizator după ID
    public function getUserById($id) {
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Actualizare utilizator
    public function updateUser($data) {
        // Verificare dacă se actualizează parola
        if (!empty($data['password'])) {
            $this->db->query('UPDATE users SET 
                              client_id = :client_id, 
                              location_id = :location_id,
                              first_name = :first_name, 
                              last_name = :last_name, 
                              email = :email, 
                              password = :password, 
                              role = :role, 
                              status = :status 
                              WHERE id = :id');
            
            $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        } else {
            $this->db->query('UPDATE users SET 
                              client_id = :client_id, 
                              location_id = :location_id,
                              first_name = :first_name, 
                              last_name = :last_name, 
                              email = :email, 
                              role = :role, 
                              status = :status 
                              WHERE id = :id');
        }
        
        // Legare parametri
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':client_id', $data['client_id']);
        $this->db->bind(':location_id', $data['location_id']);
        $this->db->bind(':first_name', $data['first_name']);
        $this->db->bind(':last_name', $data['last_name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':status', $data['status']);
        
        return $this->db->execute();
    }
    
    // Obține toți utilizatorii
    public function getAllUsers() {
        $this->db->query('SELECT u.*, c.company_name, l.name as location_name 
                           FROM users u
                           LEFT JOIN clients c ON u.client_id = c.id
                           LEFT JOIN locations l ON u.location_id = l.id
                           ORDER BY u.id DESC');
        
        return $this->db->resultSet();
    }
    
    // Obține utilizatorii unui client
    public function getClientUsers($client_id) {
        $this->db->query('SELECT u.*, l.name as location_name 
                           FROM users u
                           LEFT JOIN locations l ON u.location_id = l.id
                           WHERE u.client_id = :client_id
                           ORDER BY u.id DESC');
        
        $this->db->bind(':client_id', $client_id);
        
        return $this->db->resultSet();
    }
    
    // Șterge utilizator
    public function deleteUser($id) {
        $this->db->query('DELETE FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }

    public function getAllUsersPaginated($limit = 10, $offset = 0, $search = '') {
        $sql = 'SELECT u.*, c.company_name, l.name as location_name  
                FROM users u 
                LEFT JOIN clients c ON u.client_id = c.id 
                LEFT JOIN locations l ON u.location_id = l.id';
        
        if (!empty($search)) {
            $sql .= ' WHERE u.first_name LIKE :search 
                      OR u.last_name LIKE :search 
                      OR u.email LIKE :search 
                      OR c.company_name LIKE :search';
        }
        
        $sql .= ' ORDER BY u.id DESC LIMIT :limit OFFSET :offset';
        
        $this->db->query($sql);
        
        if (!empty($search)) {
            $this->db->bind(':search', '%' . $search . '%');
        }
        
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    public function countAllUsers($search = '') {
        $sql = 'SELECT COUNT(*) as count FROM users u LEFT JOIN clients c ON u.client_id = c.id';
        
        if (!empty($search)) {
            $sql .= ' WHERE u.first_name LIKE :search 
                      OR u.last_name LIKE :search 
                      OR u.email LIKE :search 
                      OR c.company_name LIKE :search';
        }
        
        $this->db->query($sql);
        
        if (!empty($search)) {
            $this->db->bind(':search', '%' . $search . '%');
        }
        
        $result = $this->db->single();
        return $result['count'];
    }

    /**

 */
    public function getTotalUsers($search = '') {
        return $this->countAllUsers($search);
    }

}