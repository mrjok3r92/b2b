<?php
// classes/DeliveryNote.php
require_once __DIR__ . '/../config/database.php';

class DeliveryNote {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Adaugă un aviz nou în baza de date
     * 
     * @param array $data Datele avizului
     * @return int|bool ID-ul avizului nou sau false în caz de eroare
     */
    public function addDeliveryNote($data) {
        $this->db->query('INSERT INTO delivery_notes (
                            order_id, client_id, location_id, series, delivery_note_number, 
                            issue_date, status, notes, created_by
                          ) VALUES (
                            :order_id, :client_id, :location_id, :series, :delivery_note_number, 
                            :issue_date, :status, :notes, :created_by
                          )');
        
        // Legare parametri
        $this->db->bind(':order_id', $data['order_id']);
        $this->db->bind(':client_id', $data['client_id']);
        $this->db->bind(':location_id', $data['location_id']);
        $this->db->bind(':series', $data['series']);
        $this->db->bind(':delivery_note_number', $data['delivery_note_number']);
        $this->db->bind(':issue_date', $data['issue_date']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':notes', $data['notes']);
        $this->db->bind(':created_by', $data['created_by']);
        
        // Executare
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    /**
     * Adaugă un produs în aviz
     * 
     * @param array $data Datele produsului
     * @return bool Succes sau eșec
     */
    public function addDeliveryNoteItem($data) {
        $this->db->query('INSERT INTO delivery_note_items (
                            delivery_note_id, order_item_id, product_id, product_code, product_name, 
                            unit, quantity, unit_price
                          ) VALUES (
                            :delivery_note_id, :order_item_id, :product_id, :product_code, :product_name, 
                            :unit, :quantity, :unit_price
                          )');
        
        // Legare parametri
        $this->db->bind(':delivery_note_id', $data['delivery_note_id']);
        $this->db->bind(':order_item_id', $data['order_item_id']);
        $this->db->bind(':product_id', $data['product_id']);
        $this->db->bind(':product_code', $data['product_code']);
        $this->db->bind(':product_name', $data['product_name']);
        $this->db->bind(':unit', $data['unit']);
        $this->db->bind(':quantity', $data['quantity']);
        $this->db->bind(':unit_price', $data['unit_price']);
        
        return $this->db->execute();
    }
    
    /**
     * Obține un aviz după ID
     * 
     * @param int $id ID-ul avizului
     * @return array|false Informațiile avizului sau false dacă nu există
     */
    public function getDeliveryNoteById($id) {
        $this->db->query('SELECT dn.*, 
                            CONCAT(u.first_name, " ", u.last_name) as created_by_name 
                          FROM delivery_notes dn 
                          LEFT JOIN users u ON dn.created_by = u.id 
                          WHERE dn.id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Obține toate avizele
     * 
     * @return array Lista tuturor avizelor
     */
    public function getAllDeliveryNotes() {
        $this->db->query('SELECT dn.*, 
                            o.order_number, c.company_name, c.fiscal_code, l.name as location_name
                          FROM delivery_notes dn 
                          JOIN orders o ON dn.order_id = o.id 
                          JOIN clients c ON dn.client_id = c.id 
                          JOIN locations l ON dn.location_id = l.id 
                          ORDER BY dn.issue_date DESC');
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține avizele filtrate
     * 
     * @param int $client_id ID-ul clientului (0 pentru toți)
     * @param string $status Status-ul ('' pentru toate)
     * @param string $date_from Data de început ('' pentru toate)
     * @param string $date_to Data de sfârșit ('' pentru toate)
     * @param string $search Termeni de căutare ('' pentru toți)
     * @param int $limit Limita de rezultate
     * @param int $offset Offset-ul pentru paginare
     * @return array Lista avizelor filtrate
     */
    public function getFilteredDeliveryNotes($client_id = 0, $status = '', $date_from = '', $date_to = '', $search = '', $limit = 20, $offset = 0) {
        $sql = 'SELECT dn.*, 
                  o.order_number, c.company_name, c.fiscal_code, l.name as location_name, 
                  (SELECT SUM(dni.quantity * dni.unit_price) FROM delivery_note_items dni WHERE dni.delivery_note_id = dn.id) as total_amount
                FROM delivery_notes dn 
                JOIN orders o ON dn.order_id = o.id 
                JOIN clients c ON dn.client_id = c.id 
                JOIN locations l ON dn.location_id = l.id 
                WHERE 1=1';
        
        // Adăugare condiții de filtrare
        if ($client_id > 0) {
            $sql .= ' AND dn.client_id = :client_id';
        }
        
        if (!empty($status)) {
            $sql .= ' AND dn.status = :status';
        }
        
        if (!empty($date_from)) {
            $sql .= ' AND DATE(dn.issue_date) >= :date_from';
        }
        
        if (!empty($date_to)) {
            $sql .= ' AND DATE(dn.issue_date) <= :date_to';
        }
        
        if (!empty($search)) {
            $sql .= ' AND (dn.delivery_note_number LIKE :search OR o.order_number LIKE :search OR c.company_name LIKE :search)';
        }
        
        $sql .= ' ORDER BY dn.issue_date DESC LIMIT :limit OFFSET :offset';
        
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
    
    /**
     * Numără avizele filtrate
     * 
     * @param int $client_id ID-ul clientului (0 pentru toți)
     * @param string $status Status-ul ('' pentru toate)
     * @param string $date_from Data de început ('' pentru toate)
     * @param string $date_to Data de sfârșit ('' pentru toate)
     * @param string $search Termeni de căutare ('' pentru toți)
     * @return int Numărul de avize
     */
    public function countFilteredDeliveryNotes($client_id = 0, $status = '', $date_from = '', $date_to = '', $search = '') {
        $sql = 'SELECT COUNT(*) as count
                FROM delivery_notes dn 
                JOIN orders o ON dn.order_id = o.id 
                JOIN clients c ON dn.client_id = c.id 
                WHERE 1=1';
        
        // Adăugare condiții de filtrare
        if ($client_id > 0) {
            $sql .= ' AND dn.client_id = :client_id';
        }
        
        if (!empty($status)) {
            $sql .= ' AND dn.status = :status';
        }
        
        if (!empty($date_from)) {
            $sql .= ' AND DATE(dn.issue_date) >= :date_from';
        }
        
        if (!empty($date_to)) {
            $sql .= ' AND DATE(dn.issue_date) <= :date_to';
        }
        
        if (!empty($search)) {
            $sql .= ' AND (dn.delivery_note_number LIKE :search OR o.order_number LIKE :search OR c.company_name LIKE :search)';
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
    
    /**
     * Obține avizele pentru o comandă
     * 
     * @param int $order_id ID-ul comenzii
     * @return array Lista avizelor pentru comandă
     */
    public function getDeliveryNotesByOrderId($order_id) {
        $this->db->query('SELECT dn.*, 
                            (SELECT SUM(dni.quantity * dni.unit_price) FROM delivery_note_items dni WHERE dni.delivery_note_id = dn.id) as total_amount
                          FROM delivery_notes dn 
                          WHERE dn.order_id = :order_id 
                          ORDER BY dn.issue_date DESC');
        $this->db->bind(':order_id', $order_id);
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține avizele pentru un client
     * 
     * @param int $client_id ID-ul clientului
     * @return array Lista avizelor pentru client
     */
    public function getDeliveryNotesByClientId($client_id) {
        $this->db->query('SELECT dn.*, 
                            o.order_number, l.name as location_name,
                            (SELECT SUM(dni.quantity * dni.unit_price) FROM delivery_note_items dni WHERE dni.delivery_note_id = dn.id) as total_amount
                          FROM delivery_notes dn 
                          JOIN orders o ON dn.order_id = o.id 
                          JOIN locations l ON dn.location_id = l.id 
                          WHERE dn.client_id = :client_id 
                          ORDER BY dn.issue_date DESC');
        $this->db->bind(':client_id', $client_id);
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține produsele din aviz
     * 
     * @param int $delivery_note_id ID-ul avizului
     * @return array Lista produselor din aviz
     
      *  public function getDeliveryNoteItems($delivery_note_id) {
      *      $this->db->query('SELECT * FROM delivery_note_items WHERE delivery_note_id = :delivery_note_id');
      *      $this->db->bind(':delivery_note_id', $delivery_note_id);
      *      
      *      return $this->db->resultSet();
      *  }
    
    
     * Actualizează statusul unui aviz
     * 
     * @param int $id ID-ul avizului
     * @param string $status Noul status
     * @return bool Succes sau eșec
     */
    public function updateDeliveryNoteStatus($id, $status) {
        $this->db->query('UPDATE delivery_notes SET status = :status WHERE id = :id');
        $this->db->bind(':id', $id);
        $this->db->bind(':status', $status);
        
        return $this->db->execute();
    }
    
    /**
     * Marchează avizul ca trimis
     * 
     * @param int $id ID-ul avizului
     * @return bool Succes sau eșec
     */
    public function markAsSent($id) {
        $this->db->query('UPDATE delivery_notes SET 
                            status = "sent", 
                            sent_date = NOW() 
                          WHERE id = :id AND status = "draft"');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Marchează avizul ca livrat
     * 
     * @param int $id ID-ul avizului
     * @return bool Succes sau eșec
     */
    public function markAsDelivered($id) {
        $this->db->query('UPDATE delivery_notes SET 
                            status = "delivered", 
                            delivery_date = NOW() 
                          WHERE id = :id AND status = "sent"');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Anulează un aviz
     * 
     * @param int $id ID-ul avizului
     * @param string $reason Motivul anulării
     * @return bool Succes sau eșec
     */
    public function cancelDeliveryNote($id, $reason = '') {
        // Verifică statusul curent
        $this->db->query('SELECT status FROM delivery_notes WHERE id = :id');
        $this->db->bind(':id', $id);
        $deliveryNote = $this->db->single();
        
        if (!$deliveryNote || $deliveryNote['status'] === 'cancelled' || $deliveryNote['status'] === 'delivered') {
            return false;
        }
        
        // Actualizează statusul
        $this->db->query('UPDATE delivery_notes SET 
                            status = "cancelled", 
                            cancelled_date = NOW(),
                            cancel_reason = :cancel_reason
                          WHERE id = :id');
        $this->db->bind(':id', $id);
        $this->db->bind(':cancel_reason', $reason);
        
        return $this->db->execute();
    }
    
    /**
     * Șterge un aviz (doar în status draft)
     * 
     * @param int $id ID-ul avizului
     * @return bool Succes sau eșec
     */
    public function deleteDeliveryNote($id) {
        // Verifică statusul curent
        $this->db->query('SELECT status FROM delivery_notes WHERE id = :id');
        $this->db->bind(':id', $id);
        $deliveryNote = $this->db->single();
        
        if (!$deliveryNote || $deliveryNote['status'] !== 'draft') {
            return false;
        }
        
        // Șterge produsele din aviz
        $this->db->query('DELETE FROM delivery_note_items WHERE delivery_note_id = :delivery_note_id');
        $this->db->bind(':delivery_note_id', $id);
        $this->db->execute();
        
        // Șterge avizul
        $this->db->query('DELETE FROM delivery_notes WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Obține următorul număr de aviz pentru o serie
     * 
     * @param string $series Seria avizului
     * @return int Următorul număr de aviz disponibil
     */
    public function getNextDeliveryNoteNumber($series) {
        $this->db->query('SELECT MAX(CAST(delivery_note_number AS UNSIGNED)) as max_number 
                          FROM delivery_notes 
                          WHERE series = :series');
        $this->db->bind(':series', $series);
        
        $result = $this->db->single();
        
        if ($result && $result['max_number']) {
            return $result['max_number'] + 1;
        }
        
        return 1; // Primul aviz pentru această serie
    }
    
   
    public function deliveryNoteNumberExists($series, $number) {
        $this->db->query('SELECT id FROM delivery_notes 
                          WHERE series = :series AND delivery_note_number = :number');
        $this->db->bind(':series', $series);
        $this->db->bind(':number', $number);
        
        $result = $this->db->single();
        
        return $result ? true : false;
    }
    
  
    public function getDeliveredQuantityForOrderItem($order_item_id) {
        $this->db->query('SELECT SUM(dni.quantity) as delivered_quantity 
                          FROM delivery_note_items dni 
                          JOIN delivery_notes dn ON dni.delivery_note_id = dn.id 
                          WHERE dni.order_item_id = :order_item_id 
                          AND dn.status != "cancelled"');
        $this->db->bind(':order_item_id', $order_item_id);
        
        $result = $this->db->single();
        
        return $result && $result['delivered_quantity'] ? (float)$result['delivered_quantity'] : 0;
    }
    
  
    public function countDeliveryNotesByStatus($status = '') {
        if (empty($status)) {
            $this->db->query('SELECT COUNT(*) as count FROM delivery_notes');
            $result = $this->db->single();
            return $result ? $result['count'] : 0;
        } else {
            $this->db->query('SELECT COUNT(*) as count FROM delivery_notes WHERE status = :status');
            $this->db->bind(':status', $status);
            $result = $this->db->single();
            return $result ? $result['count'] : 0;
        }
    }
    
   
    public function isOrderCompletelyDelivered($order_id) {
        $this->db->query('SELECT oi.id, oi.quantity, 
                           (SELECT SUM(dni.quantity) 
                            FROM delivery_note_items dni 
                            JOIN delivery_notes dn ON dni.delivery_note_id = dn.id 
                            WHERE dni.order_item_id = oi.id 
                            AND dn.status != "cancelled") as delivered_quantity 
                          FROM order_items oi 
                          WHERE oi.order_id = :order_id');
        $this->db->bind(':order_id', $order_id);
        
        $items = $this->db->resultSet();
        
        if (empty($items)) {
            return false;
        }
        
        foreach ($items as $item) {
            $delivered = $item['delivered_quantity'] ? (float)$item['delivered_quantity'] : 0;
            $ordered = (float)$item['quantity'];
            
            // Verifică cu o marjă mică de eroare pentru numere cu virgulă
            if ($delivered < ($ordered - 0.001)) {
                return false;
            }
        }
        
        return true;
    }
    
 
    public function getDeliveryNotesByProductId($product_id) {
        $this->db->query('SELECT dn.*, 
                            o.order_number, c.company_name, l.name as location_name,
                            dni.quantity, dni.unit_price
                          FROM delivery_notes dn 
                          JOIN delivery_note_items dni ON dn.id = dni.delivery_note_id
                          JOIN orders o ON dn.order_id = o.id 
                          JOIN clients c ON dn.client_id = c.id 
                          JOIN locations l ON dn.location_id = l.id 
                          WHERE dni.product_id = :product_id 
                          ORDER BY dn.issue_date DESC');
        $this->db->bind(':product_id', $product_id);
        
        return $this->db->resultSet();
    }

    public function getDeliveryNotesByLocationId($location_id) {
        $this->db->query('SELECT dn.*, 
                            o.order_number, c.company_name,
                            (SELECT SUM(dni.quantity * dni.unit_price) FROM delivery_note_items dni WHERE dni.delivery_note_id = dn.id) as total_amount
                          FROM delivery_notes dn 
                          JOIN orders o ON dn.order_id = o.id 
                          JOIN clients c ON dn.client_id = c.id 
                          WHERE dn.location_id = :location_id 
                          ORDER BY dn.issue_date DESC');
        $this->db->bind(':location_id', $location_id);
        
        return $this->db->resultSet();
    }
    
  
    public function getDeliveryNoteReport($date_from, $date_to, $client_id = 0) {
        $sql = 'SELECT dn.id, dn.delivery_note_number, dn.series, dn.issue_date, dn.status,
                   o.order_number, c.company_name, l.name as location_name,
                   (SELECT SUM(dni.quantity * dni.unit_price) FROM delivery_note_items dni WHERE dni.delivery_note_id = dn.id) as total_amount
                FROM delivery_notes dn 
                JOIN orders o ON dn.order_id = o.id 
                JOIN clients c ON dn.client_id = c.id 
                JOIN locations l ON dn.location_id = l.id 
                WHERE DATE(dn.issue_date) BETWEEN :date_from AND :date_to';
                
        if ($client_id > 0) {
            $sql .= ' AND dn.client_id = :client_id';
        }
        
        $sql .= ' ORDER BY dn.issue_date DESC';
        
        $this->db->query($sql);
        
        $this->db->bind(':date_from', $date_from);
        $this->db->bind(':date_to', $date_to);
        
        if ($client_id > 0) {
            $this->db->bind(':client_id', $client_id);
        }
        
        return $this->db->resultSet();
    }

    public function getOrdersWithoutFullDeliveryNotes() {
        $this->db->query('SELECT o.id, o.order_number, o.order_date, o.total_amount, 
                            c.company_name, c.fiscal_code
                          FROM orders o 
                          JOIN clients c ON o.client_id = c.id 
                          WHERE o.status = "approved" AND 
                          (
                            -- Comenzi care nu au niciun aviz
                            NOT EXISTS (
                              SELECT 1 FROM delivery_notes dn 
                              WHERE dn.order_id = o.id
                            )
                            OR
                            -- Comenzi care au avize, dar nu toate produsele sunt complet livrate
                            EXISTS (
                              SELECT 1 FROM order_details od 
                              WHERE od.order_id = o.id 
                              AND (
                                SELECT COALESCE(SUM(dni.quantity), 0) 
                                FROM delivery_note_items dni 
                                JOIN delivery_notes dn ON dni.delivery_note_id = dn.id 
                                WHERE dni.order_item_id = od.id 
                                AND dn.status != "cancelled"
                              ) < od.quantity
                            )
                          )
                          ORDER BY o.order_date DESC');
        
        return $this->db->resultSet();
    }

    public function getClientDeliveryNotes($client_id, $limit = 0, $offset = 0) {
        $sql = 'SELECT dn.*, 
                    o.order_number, l.name as location_name,
                    (SELECT SUM(dni.quantity * dni.unit_price) FROM delivery_note_items dni WHERE dni.delivery_note_id = dn.id) as total_amount
                  FROM delivery_notes dn 
                  JOIN orders o ON dn.order_id = o.id 
                  JOIN locations l ON dn.location_id = l.id 
                  WHERE dn.client_id = :client_id 
                  ORDER BY dn.issue_date DESC';
        
        // Adaugă limita dacă este specificată
        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }
        
        $this->db->query($sql);
        $this->db->bind(':client_id', $client_id);
        
        if ($limit > 0) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        }
        
        return $this->db->resultSet();
    }
    
   
    public function countClientDeliveryNotes($client_id) {
        $this->db->query('SELECT COUNT(*) as count 
                          FROM delivery_notes 
                          WHERE client_id = :client_id');
        $this->db->bind(':client_id', $client_id);
        
        $result = $this->db->single();
        return $result['count'];
    }

    public function getOrderDeliveryNotes($order_id) {
        $this->db->query('SELECT dn.*, 
                            l.name as location_name,
                            c.company_name
                          FROM delivery_notes dn 
                          JOIN locations l ON dn.location_id = l.id 
                          JOIN clients c ON dn.client_id = c.id
                          WHERE dn.order_id = :order_id 
                          ORDER BY dn.issue_date DESC');
        
        $this->db->bind(':order_id', $order_id);
        
        $deliveryNotes = $this->db->resultSet();
    
        // Adaugă detaliile pentru fiecare aviz
        foreach ($deliveryNotes as &$note) {
            $note['total_amount'] = $this->calculateDeliveryNoteTotal($note['id']);
            $note['items'] = $this->getDeliveryNoteItems($note['id']);
        }
    
        return $deliveryNotes;
    }

    public function getTotalDeliveredQuantityForProduct($order_id, $product_id) {
        $this->db->query('SELECT COALESCE(SUM(dni.quantity), 0) as total_delivered
        FROM delivery_note_items dni
        JOIN delivery_notes dn ON dni.delivery_note_id = dn.id
        WHERE dn.order_id = 
        AND dni.product_id = 
        AND dn.status != "cancelled"');
        $this->db->bind('', $order_id);
        $this->db->bind('', $product_id);
        $result = $this->db->single();
        return $result['total_delivered'];
    }
       

    private function calculateDeliveryNoteTotal($delivery_note_id) {
        $this->db->query('SELECT COALESCE(SUM(quantity * unit_price), 0) as total 
                          FROM delivery_note_items 
                          WHERE delivery_note_id = :delivery_note_id');
        
        $this->db->bind(':delivery_note_id', $delivery_note_id);
        
        $result = $this->db->single();
        return $result['total'];
    }

    private function getDeliveryNoteItems($delivery_note_id) {
        $this->db->query('SELECT dni.*, 
                            p.name as product_name, 
                            p.code as product_code
                          FROM delivery_note_items dni
                          JOIN products p ON dni.product_id = p.id
                          WHERE dni.delivery_note_id = :delivery_note_id');
        
        $this->db->bind(':delivery_note_id', $delivery_note_id);
        
        return $this->db->resultSet();
    }

}