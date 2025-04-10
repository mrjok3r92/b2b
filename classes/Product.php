<?php
// classes/Product.php
require_once __DIR__ . '/../config/database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Adaugă un produs nou
     * 
     * @param array $data Datele produsului
     * @return int|bool ID-ul produsului nou sau false în caz de eroare
     */
    public function addProduct($data) {
        // Verifică dacă codul produsului există deja
        $this->db->query('SELECT id FROM products WHERE code = :code');
        $this->db->bind(':code', $data['code']);
        $existingProduct = $this->db->single();
        
        if ($existingProduct) {
            return false;
        }
        
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
    
    /**
     * Actualizează un produs existent
     * 
     * @param array $data Datele produsului
     * @return bool Succes sau eșec
     */
    public function updateProduct($data) {
        // Verifică dacă există alt produs cu acest cod
        $this->db->query('SELECT id FROM products WHERE code = :code AND id != :id');
        $this->db->bind(':code', $data['code']);
        $this->db->bind(':id', $data['id']);
        $existingProduct = $this->db->single();
        
        if ($existingProduct) {
            return false;
        }
        
        $this->db->query('UPDATE products SET 
                            category_id = :category_id, 
                            code = :code, 
                            name = :name, 
                            description = :description, 
                            unit = :unit, 
                            price = :price, 
                            status = :status,
                            updated_at = NOW()
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
    
    /**
     * Actualizează imaginea unui produs
     * 
     * @param int $product_id ID-ul produsului
     * @param string $image Numele fișierului imagine
     * @return bool Succes sau eșec
     */
    public function updateProductImage($product_id, $image) {
        $this->db->query('UPDATE products SET image = :image, updated_at = NOW() WHERE id = :id');
        
        // Legare parametri
        $this->db->bind(':id', $product_id);
        $this->db->bind(':image', $image);
        
        return $this->db->execute();
    }
    
    /**
     * Obține un produs după ID
     * 
     * @param int $id ID-ul produsului
     * @return array|false Informațiile produsului sau false dacă nu există
     */
    public function getProductById($id) {
        $this->db->query('SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN product_categories c ON p.category_id = c.id 
                          WHERE p.id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Obține toate produsele
     * 
     * @return array Lista tuturor produselor
     */
    public function getAllProducts() {
        $this->db->query('SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN product_categories c ON p.category_id = c.id 
                          ORDER BY p.name ASC');
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține produsele cu paginare
     * 
     * @param int $limit Numărul de produse pe pagină
     * @param int $offset De unde începe
     * @return array Lista de produse
     */
    public function getAllProductsPaginated($limit = 10, $offset = 0) {
        $this->db->query('SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN product_categories c ON p.category_id = c.id 
                          ORDER BY p.name ASC 
                          LIMIT :limit OFFSET :offset');
        
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține numărul total de produse
     * 
     * @return int Numărul total de produse
     */
    public function getTotalProducts() {
        $this->db->query('SELECT COUNT(*) as count FROM products');
        $result = $this->db->single();
        return $result['count'];
    }
    
    /**
     * Șterge un produs
     * 
     * @param int $id ID-ul produsului
     * @return bool Succes sau eșec
     */
    public function deleteProduct($id) {
        // Verifică mai întâi dacă produsul are comenzi asociate
        if ($this->hasOrders($id)) {
            return false;
        }
        
        // Șterge toate prețurile specifice pentru clienți
        $this->db->query('DELETE FROM client_specific_prices WHERE product_id = :product_id');
        $this->db->bind(':product_id', $id);
        $this->db->execute();
        
        // Șterge produsul
        $this->db->query('DELETE FROM products WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Verifică dacă un produs are comenzi asociate
     * 
     * @param int $id ID-ul produsului
     * @return bool True dacă are comenzi, false altfel
     */
    public function hasOrders($id) {
        $this->db->query('SELECT COUNT(*) as count FROM order_items WHERE product_id = :product_id');
        $this->db->bind(':product_id', $id);
        $result = $this->db->single();
        
        return $result['count'] > 0;
    }
    
    /**
     * Caută produse după nume, cod sau descriere
     * 
     * @param string $search Termenul de căutare
     * @param int $limit Numărul de produse pe pagină
     * @param int $offset De unde începe
     * @return array Rezultatele căutării
     */
    public function searchProducts($search, $limit = 10, $offset = 0) {
        $this->db->query('SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN product_categories c ON p.category_id = c.id 
                          WHERE p.name LIKE :search 
                             OR p.code LIKE :search 
                             OR p.description LIKE :search 
                          ORDER BY p.name ASC 
                          LIMIT :limit OFFSET :offset');
        
        $this->db->bind(':search', '%' . $search . '%');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Numără rezultatele căutării
     * 
     * @param string $search Termenul de căutare
     * @return int Numărul de produse găsite
     */
    public function countSearchResults($search) {
        $this->db->query('SELECT COUNT(*) as count 
                          FROM products 
                          WHERE name LIKE :search 
                             OR code LIKE :search 
                             OR description LIKE :search');
        
        $this->db->bind(':search', '%' . $search . '%');
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    /**
     * Obține produsele din o anumită categorie
     * 
     * @param int $category_id ID-ul categoriei
     * @param int $limit Numărul de produse pe pagină
     * @param int $offset De unde începe
     * @return array Lista de produse
     */
    public function getProductsByCategory($category_id, $limit = 10, $offset = 0) {
        $this->db->query('SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN product_categories c ON p.category_id = c.id 
                          WHERE p.category_id = :category_id 
                          ORDER BY p.name ASC 
                          LIMIT :limit OFFSET :offset');
        
        $this->db->bind(':category_id', $category_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Numără produsele dintr-o categorie
     * 
     * @param int $category_id ID-ul categoriei
     * @return int Numărul de produse
     */
    public function countProductsByCategory($category_id) {
        $this->db->query('SELECT COUNT(*) as count FROM products WHERE category_id = :category_id');
        $this->db->bind(':category_id', $category_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    /**
     * Obține produsele după status
     * 
     * @param string $status Status-ul (active/inactive)
     * @param int $limit Numărul de produse pe pagină
     * @param int $offset De unde începe
     * @return array Lista de produse
     */
    public function getProductsByStatus($status, $limit = 10, $offset = 0) {
        $this->db->query('SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN product_categories c ON p.category_id = c.id 
                          WHERE p.status = :status 
                          ORDER BY p.name ASC 
                          LIMIT :limit OFFSET :offset');
        
        $this->db->bind(':status', $status);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Numără produsele după status
     * 
     * @param string $status Status-ul (active/inactive)
     * @return int Numărul de produse
     */
    public function countProductsByStatus($status) {
        $this->db->query('SELECT COUNT(*) as count FROM products WHERE status = :status');
        $this->db->bind(':status', $status);
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    /**
     * Obține produsele active pentru un client
     * (inclusiv prețuri specifice dacă există)
     * 
     * @param int $client_id ID-ul clientului
     * @param int $limit Numărul de produse pe pagină
     * @param int $offset De unde începe
     * @return array Lista de produse cu prețuri
     */
    public function getProductsForClient($client_id, $limit = 10, $offset = 0) {
        $this->db->query('SELECT p.*, c.name as category_name, 
                                 COALESCE(csp.price, p.price) as client_price,
                                 CASE WHEN csp.price IS NOT NULL THEN 1 ELSE 0 END as has_specific_price
                          FROM products p 
                          LEFT JOIN product_categories c ON p.category_id = c.id 
                          LEFT JOIN client_specific_prices csp ON p.id = csp.product_id AND csp.client_id = :client_id
                          WHERE p.status = "active" 
                          ORDER BY p.name ASC 
                          LIMIT :limit OFFSET :offset');
        
        $this->db->bind(':client_id', $client_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Numără produsele active pentru un client
     * 
     * @param int $client_id ID-ul clientului
     * @return int Numărul de produse
     */
    public function countProductsForClient($client_id) {
        $this->db->query('SELECT COUNT(*) as count FROM products WHERE status = "active"');
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    /**
     * Caută produse pentru un client
     * 
     * @param int $client_id ID-ul clientului
     * @param string $search Termenul de căutare
     * @param int $limit Numărul de produse pe pagină
     * @param int $offset De unde începe
     * @return array Rezultatele căutării
     */
    public function searchProductsForClient($client_id, $search, $limit = 10, $offset = 0) {
        $this->db->query('SELECT p.*, c.name as category_name, 
                                 COALESCE(csp.price, p.price) as client_price,
                                 CASE WHEN csp.price IS NOT NULL THEN 1 ELSE 0 END as has_specific_price
                          FROM products p 
                          LEFT JOIN product_categories c ON p.category_id = c.id 
                          LEFT JOIN client_specific_prices csp ON p.id = csp.product_id AND csp.client_id = :client_id
                          WHERE p.status = "active" 
                            AND (p.name LIKE :search OR p.code LIKE :search OR p.description LIKE :search) 
                          ORDER BY p.name ASC 
                          LIMIT :limit OFFSET :offset');
        
        $this->db->bind(':client_id', $client_id);
        $this->db->bind(':search', '%' . $search . '%');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține produsele dintr-o categorie pentru client
     * 
     * @param int $client_id ID-ul clientului
     * @param int $category_id ID-ul categoriei
     * @param int $limit Numărul de produse pe pagină
     * @param int $offset De unde începe
     * @return array Lista de produse
     */
    public function getProductsByCategoryForClient($client_id, $category_id, $limit = 10, $offset = 0) {
        $this->db->query('SELECT p.*, c.name as category_name, 
                                 COALESCE(csp.price, p.price) as client_price,
                                 CASE WHEN csp.price IS NOT NULL THEN 1 ELSE 0 END as has_specific_price
                          FROM products p 
                          LEFT JOIN product_categories c ON p.category_id = c.id 
                          LEFT JOIN client_specific_prices csp ON p.id = csp.product_id AND csp.client_id = :client_id
                          WHERE p.status = "active" AND p.category_id = :category_id 
                          ORDER BY p.name ASC 
                          LIMIT :limit OFFSET :offset');
        
        $this->db->bind(':client_id', $client_id);
        $this->db->bind(':category_id', $category_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține prețul unui produs pentru un client specific
     * (preț specific sau standard)
     * 
     * @param int $product_id ID-ul produsului
     * @param int $client_id ID-ul clientului
     * @return float Prețul pentru client
     */
    public function getProductPriceForClient($product_id, $client_id) {
        $this->db->query('SELECT COALESCE(
                            (SELECT price FROM client_specific_prices 
                             WHERE product_id = :product_id AND client_id = :client_id LIMIT 1),
                            (SELECT price FROM products WHERE id = :product_id)
                          ) as price');
        
        $this->db->bind(':product_id', $product_id);
        $this->db->bind(':client_id', $client_id);
        
        $result = $this->db->single();
        return $result['price'];
    }
    
    /**
     * Adaugă un preț specific pentru un client
     * 
     * @param array $data Datele prețului
     * @return bool Succes sau eșec
     */
    public function addClientSpecificPrice($data) {
        $this->db->query('INSERT INTO client_specific_prices (product_id, client_id, price) 
                           VALUES (:product_id, :client_id, :price)');
        
        // Legare parametri
        $this->db->bind(':product_id', $data['product_id']);
        $this->db->bind(':client_id', $data['client_id']);
        $this->db->bind(':price', $data['price']);
        
        return $this->db->execute();
    }
    
    /**
     * Actualizează un preț specific pentru un client
     * 
     * @param array $data Datele prețului
     * @return bool Succes sau eșec
     */
    public function updateClientSpecificPrice($data) {
        $this->db->query('UPDATE client_specific_prices 
                           SET price = :price, 
                               updated_at = NOW()
                           WHERE id = :id');
        
        // Legare parametri
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':price', $data['price']);
        
        return $this->db->execute();
    }
    
    /**
     * Șterge un preț specific pentru un client
     * 
     * @param int $id ID-ul prețului specific
     * @return bool Succes sau eșec
     */
    public function deleteClientSpecificPrice($id) {
        $this->db->query('DELETE FROM client_specific_prices WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Obține un preț specific după ID
     * 
     * @param int $id ID-ul prețului specific
     * @return array|false Informațiile prețului sau false dacă nu există
     */
    public function getClientSpecificPriceById($id) {
        $this->db->query('SELECT * FROM client_specific_prices WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Obține toate prețurile specifice pentru un produs
     * 
     * @param int $product_id ID-ul produsului
     * @return array Lista de prețuri specifice cu informații despre clienți
     */
    public function getClientSpecificPrices($product_id) {
        $this->db->query('SELECT csp.*, c.company_name, c.fiscal_code 
                          FROM client_specific_prices csp 
                          JOIN clients c ON csp.client_id = c.id 
                          WHERE csp.product_id = :product_id 
                          ORDER BY c.company_name ASC');
        
        $this->db->bind(':product_id', $product_id);
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține istoricul comenzilor pentru un produs
     * 
     * @param int $product_id ID-ul produsului
     * @param int $limit Limita de rezultate
     * @return array Istoricul comenzilor
     */
    public function getProductOrderHistory($product_id, $limit = 10) {
        $this->db->query('SELECT oi.*, o.order_number, o.order_date, o.status, c.company_name
                          FROM order_items oi
                          JOIN orders o ON oi.order_id = o.id
                          JOIN clients c ON o.client_id = c.id
                          WHERE oi.product_id = :product_id
                          ORDER BY o.order_date DESC
                          LIMIT :limit');
        
        $this->db->bind(':product_id', $product_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Adaugă o categorie nouă
     * 
     * @param array $data Datele categoriei
     * @return int|bool ID-ul categoriei noi sau false în caz de eroare
     */
    public function addCategory($data) {
        $this->db->query('INSERT INTO product_categories (name, description, parent_id) 
                           VALUES (:name, :description, :parent_id)');
        
        // Legare parametri
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':parent_id', $data['parent_id']);
        
        // Executare
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    /**
     * Actualizează o categorie existentă
     * 
     * @param array $data Datele categoriei
     * @return bool Succes sau eșec
     */
    public function updateCategory($data) {
        $this->db->query('UPDATE product_categories SET 
                            name = :name, 
                            description = :description, 
                            parent_id = :parent_id,
                            updated_at = NOW()
                          WHERE id = :id');
        
        // Legare parametri
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':parent_id', $data['parent_id']);
        
        return $this->db->execute();
    }
    
    /**
     * Șterge o categorie
     * 
     * @param int $id ID-ul categoriei
     * @return bool Succes sau eșec
     */
    public function deleteCategory($id) {
        // Verifică mai întâi dacă categoria are produse
        if ($this->countProductsByCategory($id) > 0) {
            return false;
        }
        
        // Verifică dacă categoria are subcategorii
        if ($this->hasSubcategories($id)) {
            return false;
        }
        
        $this->db->query('DELETE FROM product_categories WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Obține o categorie după ID
     * 
     * @param int $id ID-ul categoriei
     * @return array|false Informațiile categoriei sau false dacă nu există
     */
    public function getCategoryById($id) {
        $this->db->query('SELECT * FROM product_categories WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Obține toate categoriile
     * 
     * @return array Lista tuturor categoriilor
     */
    public function getAllCategories() {
        $this->db->query('SELECT * FROM product_categories ORDER BY name ASC');
        
        return $this->db->resultSet();
    }
    
    /**
     * Verifică dacă o categorie are subcategorii
     * 
     * @param int $id ID-ul categoriei
     * @return bool True dacă are subcategorii, false altfel
     */
    public function hasSubcategories($id) {
        $this->db->query('SELECT COUNT(*) as count FROM product_categories WHERE parent_id = :id');
        $this->db->bind(':id', $id);
        $result = $this->db->single();
        
        return $result['count'] > 0;
    }
    
    /**
     * Obține categoriile de nivel superior (fără părinte)
     * 
     * @return array Lista categoriilor de nivel superior
     */
    public function getTopLevelCategories() {
        $this->db->query('SELECT * FROM product_categories WHERE parent_id IS NULL ORDER BY name ASC');
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține subcategoriile pentru o categorie părinte
     * 
     * @param int $parent_id ID-ul categoriei părinte
     * @return array Lista subcategoriilor
     */
    public function getSubcategories($parent_id) {
        $this->db->query('SELECT * FROM product_categories WHERE parent_id = :parent_id ORDER BY name ASC');
        $this->db->bind(':parent_id', $parent_id);
        
        return $this->db->resultSet();
    }

    public function getActiveProducts($limit = 0, $offset = 0) {
        $sql = 'SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN product_categories c ON p.category_id = c.id 
                WHERE p.status = "active" 
                ORDER BY p.name ASC';
        
        // Adaugă limitarea doar dacă este specificată
        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }
        
        $this->db->query($sql);
        
        // Leagă parametrii de limitare doar dacă sunt folosiți
        if ($limit > 0) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Numără produsele active
     * 
     * @return int Numărul total de produse active
     */
    public function countActiveProducts() {
        $this->db->query('SELECT COUNT(*) as count FROM products WHERE status = "active"');
        $result = $this->db->single();
        return $result['count'];
    }

    public function getClientPrice($product_id, $client_id) {
        // Verifică mai întâi dacă există un preț specific pentru client
        $this->db->query('SELECT price FROM client_prices 
                          WHERE product_id = :product_id AND client_id = :client_id 
                          LIMIT 1');
        $this->db->bind(':product_id', $product_id);
        $this->db->bind(':client_id', $client_id);
        
        $result = $this->db->single();
        
        if ($result) {
            // Există un preț specific pentru client
            return (float)$result['price'];
        } else {
            // Nu există preț specific, returnam prețul standard al produsului
            $this->db->query('SELECT price FROM products WHERE id = :product_id');
            $this->db->bind(':product_id', $product_id);
            
            $result = $this->db->single();
            return $result ? (float)$result['price'] : false;
        }
    }
    
}