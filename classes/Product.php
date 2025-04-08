<?php
// classes/Product.php
require_once __DIR__ . '/../config/database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Adaugă produs nou
    public function addProduct($data) {
        $this->db->query('INSERT INTO products (category_id, code, name, description, unit, price, image, status) 
                          VALUES (:category_id, :code, :name, :description, :unit, :price, :image, :status)');
        
        // Legare parametri
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':code', $data['code']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':unit', $data['unit']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':status', $data['status']);
        
        // Executare
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    // Actualizare produs
    public function updateProduct($data) {
        $this->db->query('UPDATE products SET 
                            category_id = :category_id, 
                            code = :code, 
                            name = :name, 
                            description = :description, 
                            unit = :unit, 
                            price = :price, 
                            status = :status
                          WHERE id = :id');
        
        // Legare parametri
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':code', $data['code']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':unit', $data['unit']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':status', $data['status']);
        
        return $this->db->execute();
    }
    
    // Actualizare imagine produs
    public function updateProductImage($id, $image) {
        $this->db->query('UPDATE products SET image = :image WHERE id = :id');
        
        // Legare parametri
        $this->db->bind(':id', $id);
        $this->db->bind(':image', $image);
        
        return $this->db->execute();
    }
    
    // Obține produs după ID
    public function getProductById($id) {
        $this->db->query('SELECT p.*, pc.name as category_name 
                          FROM products p
                          LEFT JOIN product_categories pc ON p.category_id = pc.id
                          WHERE p.id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Obține toate produsele
    public function getAllProducts() {
        $this->db->query('SELECT p.*, pc.name as category_name 
                          FROM products p
                          LEFT JOIN product_categories pc ON p.category_id = pc.id
                          ORDER BY p.name ASC');
        
        return $this->db->resultSet();
    }
    
    // Obține produse active
    public function getActiveProducts() {
        $this->db->query('SELECT p.*, pc.name as category_name 
                          FROM products p
                          LEFT JOIN product_categories pc ON p.category_id = pc.id
                          WHERE p.status = "active"
                          ORDER BY p.name ASC');
        
        return $this->db->resultSet();
    }
    
    // Obține produse după categorie
    public function getProductsByCategory($category_id) {
        $this->db->query('SELECT p.*, pc.name as category_name 
                          FROM products p
                          LEFT JOIN product_categories pc ON p.category_id = pc.id
                          WHERE p.category_id = :category_id AND p.status = "active"
                          ORDER BY p.name ASC');
        
        $this->db->bind(':category_id', $category_id);
        
        return $this->db->resultSet();
    }
    
    // Șterge produs
    public function deleteProduct($id) {
        $this->db->query('DELETE FROM products WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Adaugă categorie de produse
    public function addCategory($data) {
        $this->db->query('INSERT INTO product_categories (name, description) 
                          VALUES (:name, :description)');
        
        // Legare parametri
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        
        // Executare
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    // Actualizare categorie
    public function updateCategory($data) {
        $this->db->query('UPDATE product_categories SET 
                            name = :name, 
                            description = :description
                          WHERE id = :id');
        
        // Legare parametri
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        
        return $this->db->execute();
    }
    
    // Obține categoria după ID
    public function getCategoryById($id) {
        $this->db->query('SELECT * FROM product_categories WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Obține toate categoriile
    public function getAllCategories() {
        $this->db->query('SELECT * FROM product_categories ORDER BY name ASC');
        
        return $this->db->resultSet();
    }
    
    // Șterge categorie
    public function deleteCategory($id) {
        $this->db->query('DELETE FROM product_categories WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Obține prețul specific pentru client
    public function getClientPrice($client_id, $product_id) {
        $this->db->query('SELECT price FROM client_prices 
                          WHERE client_id = :client_id AND product_id = :product_id');
        
        $this->db->bind(':client_id', $client_id);
        $this->db->bind(':product_id', $product_id);
        
        $result = $this->db->single();
        
        if ($result) {
            return $result['price'];
        } else {
            // Returnează prețul standard dacă nu există preț specific
            $this->db->query('SELECT price FROM products WHERE id = :product_id');
            $this->db->bind(':product_id', $product_id);
            
            $result = $this->db->single();
            return $result['price'];
        }
    }
    
    // Setare preț specific pentru client
    public function setClientPrice($client_id, $product_id, $price) {
        // Verifică dacă există deja un preț pentru acest client și produs
        $this->db->query('SELECT id FROM client_prices 
                          WHERE client_id = :client_id AND product_id = :product_id');
        
        $this->db->bind(':client_id', $client_id);
        $this->db->bind(':product_id', $product_id);
        
        $result = $this->db->single();
        
        if ($result) {
            // Actualizare preț existent
            $this->db->query('UPDATE client_prices SET price = :price 
                              WHERE client_id = :client_id AND product_id = :product_id');
        } else {
            // Adăugare preț nou
            $this->db->query('INSERT INTO client_prices (client_id, product_id, price) 
                              VALUES (:client_id, :product_id, :price)');
        }
        
        $this->db->bind(':client_id', $client_id);
        $this->db->bind(':product_id', $product_id);
        $this->db->bind(':price', $price);
        
        return $this->db->execute();
    }
  
   public function searchProducts($search) {
       $search = '%' . $search . '%';
       
       $this->db->query('SELECT p.*, pc.name as category_name 
                         FROM products p
                         LEFT JOIN product_categories pc ON p.category_id = pc.id
                         WHERE p.status = "active" AND 
                              (p.name LIKE :search OR 
                               p.code LIKE :search OR 
                               p.description LIKE :search)
                         ORDER BY p.name ASC');
       
       $this->db->bind(':search', $search);
       
       return $this->db->resultSet();
   }
}