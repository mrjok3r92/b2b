<?php
// classes/Notification.php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Adaugă o notificare pentru un client
    public function addClientNotification($client_id, $type, $title, $message, $link = null) {
        $this->db->query('INSERT INTO notifications (client_id, user_id, type, title, message, link) 
                          VALUES (:client_id, NULL, :type, :title, :message, :link)');
        
        $this->db->bind(':client_id', $client_id);
        $this->db->bind(':type', $type);
        $this->db->bind(':title', $title);
        $this->db->bind(':message', $message);
        $this->db->bind(':link', $link);
        
        return $this->db->execute();
    }
    
    // Adaugă o notificare pentru un utilizator specific
    public function addUserNotification($user_id, $type, $title, $message, $link = null) {
        $this->db->query('INSERT INTO notifications (client_id, user_id, type, title, message, link) 
                          VALUES (NULL, :user_id, :type, :title, :message, :link)');
        
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':type', $type);
        $this->db->bind(':title', $title);
        $this->db->bind(':message', $message);
        $this->db->bind(':link', $link);
        
        return $this->db->execute();
    }
    
    // Obține notificările pentru un client
    public function getClientNotifications($client_id, $limit = 10) {
        $this->db->query('SELECT * FROM notifications 
                          WHERE client_id = :client_id 
                          ORDER BY created_at DESC 
                          LIMIT :limit');
        
        $this->db->bind(':client_id', $client_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    // Obține notificările pentru un utilizator
    public function getUserNotifications($user_id, $limit = 10) {
        $this->db->query('SELECT * FROM notifications 
                          WHERE user_id = :user_id OR 
                                (client_id = (SELECT client_id FROM users WHERE id = :user_id) AND user_id IS NULL)
                          ORDER BY created_at DESC 
                          LIMIT :limit');
        
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    // Obține numărul de notificări necitite pentru un utilizator
    public function getUnreadCount($user_id) {
        $this->db->query('SELECT COUNT(*) as count FROM notifications 
                          WHERE is_read = 0 AND
                                (user_id = :user_id OR 
                                (client_id = (SELECT client_id FROM users WHERE id = :user_id) AND user_id IS NULL))');
        
        $this->db->bind(':user_id', $user_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    // Marchează o notificare ca citită
    public function markAsRead($notification_id) {
        $this->db->query('UPDATE notifications SET is_read = 1 WHERE id = :id');
        $this->db->bind(':id', $notification_id);
        
        return $this->db->execute();
    }
    
    // Marchează toate notificările unui utilizator ca citite
    public function markAllAsRead($user_id) {
        $this->db->query('UPDATE notifications 
                          SET is_read = 1 
                          WHERE is_read = 0 AND
                                (user_id = :user_id OR 
                                (client_id = (SELECT client_id FROM users WHERE id = :user_id) AND user_id IS NULL))');
        
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->execute();
    }
    
    // Obține o notificare după ID
    public function getNotificationById($id) {
        $this->db->query('SELECT * FROM notifications WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    // Șterge o notificare
    public function deleteNotification($id) {
        $this->db->query('DELETE FROM notifications WHERE id = :id');
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // Șterge notificările vechi (mai vechi de numărul de zile specificat)
    public function deleteOldNotifications($days = 30) {
        $this->db->query('DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)');
        $this->db->bind(':days', $days, PDO::PARAM_INT);
        
        return $this->db->execute();
    }
    
    // Creare notificare pentru o comandă nouă
    public function createOrderNotification($order_id, $order_number, $client_name) {
        // Notificare pentru administratori
        $title = "Comandă nouă #" . $order_number;
        $message = "Clientul " . $client_name . " a plasat o comandă nouă.";
        $link = "admin/orders/view.php?id=" . $order_id;
        
        // Obținem toți utilizatorii de tip admin sau agent
        $this->db->query('SELECT id FROM users WHERE role IN ("admin", "agent")');
        $admins = $this->db->resultSet();
        
        foreach ($admins as $admin) {
            $this->addUserNotification($admin['id'], 'order_new', $title, $message, $link);
        }
    }
    
    // Creare notificare pentru o comandă aprobată
    public function createOrderApprovedNotification($order_id, $order_number, $client_id, $user_id) {
        $title = "Comandă aprobată #" . $order_number;
        $message = "Comanda ta a fost aprobată și va fi procesată.";
        $link = "client/orders/view.php?id=" . $order_id;
        
        // Notificare pentru client (toți utilizatorii)
        $this->addClientNotification($client_id, 'order_approved', $title, $message, $link);
        
        // Notificare specifică pentru utilizatorul care a plasat comanda
        $this->addUserNotification($user_id, 'order_approved', $title, $message, $link);
    }
    
    // Creare notificare pentru o comandă respinsă
    public function createOrderRejectedNotification($order_id, $order_number, $client_id, $user_id, $reason) {
        $title = "Comandă respinsă #" . $order_number;
        $message = "Comanda ta a fost respinsă. Motiv: " . $reason;
        $link = "client/orders/view.php?id=" . $order_id;
        
        // Notificare pentru client (toți utilizatorii)
        $this->addClientNotification($client_id, 'order_rejected', $title, $message, $link);
        
        // Notificare specifică pentru utilizatorul care a plasat comanda
        $this->addUserNotification($user_id, 'order_rejected', $title, $message, $link);
    }
    
    // Creare notificare pentru un aviz de livrare
    public function createDeliveryNoteNotification($note_id, $note_number, $client_id, $order_id) {
        $title = "Aviz de livrare nou #" . $note_number;
        $message = "A fost generat un aviz de livrare pentru comanda ta.";
        $link = "client/delivery-notes/view.php?id=" . $note_id;
        
        // Notificare pentru client
        $this->addClientNotification($client_id, 'delivery_note', $title, $message, $link);
    }
    public function getFilteredUserNotifications($user_id, $limit, $offset, $type = '', $is_read = null) {
        $sql = 'SELECT * FROM notifications 
                WHERE (user_id = :user_id OR 
                      (client_id = (SELECT client_id FROM users WHERE id = :user_id) AND user_id IS NULL))';
        
        // Adaugă condiții de filtrare
        if (!empty($type)) {
            $sql .= ' AND type = :type';
        }
        
        if ($is_read !== null) {
            $sql .= ' AND is_read = :is_read';
        }
        
        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        
        $this->db->query($sql);
        
        // Leagă parametrii
        $this->db->bind(':user_id', $user_id);
        
        if (!empty($type)) {
            $this->db->bind(':type', $type);
        }
        
        if ($is_read !== null) {
            $this->db->bind(':is_read', $is_read, PDO::PARAM_INT);
        }
        
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Obține numărul total de notificări pentru un utilizator
     * @param int $user_id ID-ul utilizatorului
     * @return int Numărul total de notificări
     */
    public function getTotalUserNotifications($user_id) {
        $this->db->query('SELECT COUNT(*) as count FROM notifications 
                          WHERE user_id = :user_id OR 
                                (client_id = (SELECT client_id FROM users WHERE id = :user_id) AND user_id IS NULL)');
        
        $this->db->bind(':user_id', $user_id);
        
        $result = $this->db->single();
        return $result['count'];
    }
    

    public function getTotalFilteredNotifications($user_id, $type = '', $is_read = null) {
        $sql = 'SELECT COUNT(*) as count FROM notifications 
                WHERE (user_id = :user_id OR 
                      (client_id = (SELECT client_id FROM users WHERE id = :user_id) AND user_id IS NULL))';
        
        // Adaugă condiții de filtrare
        if (!empty($type)) {
            $sql .= ' AND type = :type';
        }
        
        if ($is_read !== null) {
            $sql .= ' AND is_read = :is_read';
        }
        
        $this->db->query($sql);
        
        // Leagă parametrii
        $this->db->bind(':user_id', $user_id);
        
        if (!empty($type)) {
            $this->db->bind(':type', $type);
        }
        
        if ($is_read !== null) {
            $this->db->bind(':is_read', $is_read, PDO::PARAM_INT);
        }
        
        $result = $this->db->single();
        return $result['count'];
    }
}