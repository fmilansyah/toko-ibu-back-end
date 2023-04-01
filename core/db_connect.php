<?php

/**
 * A class file to connect to database
 */
// set_time_limit(1800);

require_once __DIR__ . '/db_config.php';

class DB_CONNECT {
    var $conn = null;

    // constructor
    function __construct() {
        // connecting to database
        $this->connect();
    }

    // destructor
    function __destruct() {
        // closing db connection
        $this->close();
    }

    /**
     * Function to connect with database
     */
    function connect() {
        // import database connection variables
        
        $counter = 0;
        $MAX_RETRY = 30;
        
        while ($counter < $MAX_RETRY) {
            // Connecting to mysql database
            try {
                $host = DB_SERVER;
                $database = DB_DATABASE;
                $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", DB_USER, DB_PASSWORD, array(PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
                $conn->setAttribute(PDO::ATTR_PERSISTENT, true);
                $conn->setAttribute(PDO::ATTR_TIMEOUT, 600);
                break;
            } catch (PDOException $ex) {
                $counter++;
                if ($counter == $MAX_RETRY) {
                    $response = array();
                    $response["success"] = 0;
                    $response["message"] = $ex->getCode() . "|" . $ex->getMessage();
                    die(json_encode($response));
                }
            }
            sleep(1000);
        }
        
        // returing connection cursor
        return $conn;
    }

    /**
     * Function to close db connection
     */
    function close() {
        // closing db connection
        $conn = null;
    }

}

?>