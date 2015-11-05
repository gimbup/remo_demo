<?php
    
class DBconnection{
    private $servername = "localhost";
    private $username = "jayce";
    private $password = "880630";
    private $dbname = "bookDB";

    public $conn;
    public $result;

    public function connect(){
        try{
            $this->conn = new PDO("mysql: host=$this->servername; 
                       dbname=$this->dbname", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo 'dbconnection success <br>';
        }
        catch(PDOException $e){
            echo "Connection failed: " . $e->getMessage(). '<br>';
        }
        
    }

    public function disconnect(){
        if(isset($this->conn)){
            $this->conn = null;
        }
        return true;
    }

    public function sqlQuery($query){
        try{
            if(!isset($this->conn)){
                echo 'query failed, db is not connected <br>';
            }
            else{
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $this->_result = $stmt->fetchAll();
            }
        }
        catch(PDOException $e){
            echo "Query failed: " . $e->getMessage(). '<br>';
        }
    }

?>
