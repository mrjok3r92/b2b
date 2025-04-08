<?php
// config/database.php
// Configurare conexiune bază de date

define('DB_HOST', 'localhost');
define('DB_USER', 'cuewydxh_strauss');     // Modificați cu utilizatorul dvs. MySQL
define('DB_PASS', 'FAmLgL&ds=h0');         // Modificați cu parola dvs. MySQL
define('DB_NAME', 'cuewydxh_straussv2');
define('DB_CHARSET', 'utf8mb4');

// Clasa pentru gestionarea conexiunii la baza de date
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $charset = DB_CHARSET;
    
    private $dbh;
    private $error;
    private $stmt;
    
    public function __construct() {
        // Set DSN
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        
        // Set options
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        // Create PDO instance
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            echo "Database Error: " . $this->error;
        }
    }
    
    // Prepare statement with query
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
        return $this;
    }
    
    // Bind values
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
    
    // Execute the prepared statement
    public function execute() {
        return $this->stmt->execute();
    }
    
    // Get result set as array of objects
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    // Get single record as object
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
    
    // Transactions
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }
    
    public function endTransaction() {
        return $this->dbh->commit();
    }
    
    public function cancelTransaction() {
        return $this->dbh->rollBack();
    }
    
    // Debug - dump parameters
    public function debugDumpParams() {
        return $this->stmt->debugDumpParams();
    }
}