<?php
// classes/Company.php
require_once __DIR__ . '/../config/database.php';

class Company {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Obține informațiile companiei
     * 
     * @return array Informațiile companiei
     */
    public function getCompanyInfo() {
        $this->db->query('SELECT * FROM company_info LIMIT 1');
        $result = $this->db->single();
        
        // Dacă nu există înregistrări, returnează valori implicite
        if (!$result) {
            return [
                'id' => 1,
                'name' => 'Compania Dvs. SRL',
                'fiscal_code' => 'RO12345678',
                'registration_number' => 'J12/345/2020',
                'address' => 'Str. Exemplu, Nr. 1, București',
                'phone' => '0123456789',
                'email' => 'contact@compania.ro',
                'bank_name' => 'Banca Exemplu',
                'bank_account' => 'RO12BANK1234567890',
                'logo' => '',
                'website' => 'www.compania.ro',
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return $result;
    }
    
    /**
     * Actualizează informațiile companiei
     * 
     * @param array $data Datele companiei
     * @return bool Succes sau eșec
     */
    public function updateCompanyInfo($data) {
        // Verifică dacă există deja o înregistrare
        $this->db->query('SELECT id FROM company_info LIMIT 1');
        $existing = $this->db->single();
        
        if ($existing) {
            // Actualizare
            $this->db->query('UPDATE company_info SET 
                                name = :name, 
                                fiscal_code = :fiscal_code, 
                                registration_number = :registration_number, 
                                address = :address, 
                                phone = :phone, 
                                email = :email, 
                                bank_name = :bank_name, 
                                bank_account = :bank_account, 
                                logo = :logo, 
                                website = :website, 
                                updated_at = NOW()
                              WHERE id = :id');
            
            $this->db->bind(':id', $existing['id']);
        } else {
            // Inserare
            $this->db->query('INSERT INTO company_info (
                                name, fiscal_code, registration_number, address, 
                                phone, email, bank_name, bank_account, logo, website, updated_at
                              ) VALUES (
                                :name, :fiscal_code, :registration_number, :address, 
                                :phone, :email, :bank_name, :bank_account, :logo, :website, NOW()
                              )');
        }
        
        // Legare parametri
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':fiscal_code', $data['fiscal_code']);
        $this->db->bind(':registration_number', $data['registration_number']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':bank_name', $data['bank_name']);
        $this->db->bind(':bank_account', $data['bank_account']);
        $this->db->bind(':logo', $data['logo']);
        $this->db->bind(':website', $data['website']);
        
        return $this->db->execute();
    }
    
    /**
     * Actualizează logo-ul companiei
     * 
     * @param string $logo Numele fișierului logo
     * @return bool Succes sau eșec
     */
    public function updateCompanyLogo($logo) {
        // Verifică dacă există deja o înregistrare
        $this->db->query('SELECT id FROM company_info LIMIT 1');
        $existing = $this->db->single();
        
        if ($existing) {
            // Actualizare logo
            $this->db->query('UPDATE company_info SET logo = :logo, updated_at = NOW() WHERE id = :id');
            $this->db->bind(':id', $existing['id']);
            $this->db->bind(':logo', $logo);
            
            return $this->db->execute();
        }
        
        return false;
    }
    
    /**
     * Obține logo-ul companiei
     * 
     * @return string|null Numele fișierului logo sau null dacă nu există
     */
    public function getCompanyLogo() {
        $this->db->query('SELECT logo FROM company_info LIMIT 1');
        $result = $this->db->single();
        
        return $result ? $result['logo'] : null;
    }
}