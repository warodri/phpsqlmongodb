<?php

require "vendor/autoload.php";

use PHPSQL\Parser as PHPSQLParser;

class PHPMongoSql {
    
    public function __construct() {
    }
    
    public function __destruct() {
    }
    
    /*
        Use this function to set
        the data base name to use
    */
    public function useDb($dbName) {
        
        $this->m = new MongoClient();
        $this->db = $this->m->selectDB( $dbName );
        
    }
    
    /*
        Get the last executed resultset
    */
    public function getResultset() {
        
        return $this->resultset;
    }
    
    public function getLastQuery() {
        
        return $this->lastQuery;
    }
    
    /*
        This is the EXECUTE function.
        Executes any SQL statement in MongoDB
    */
    public function execute( $statement = NULL ) {
        
        if ($statement == NULL) {
            throw new Exception("Empty statement.");
        }
        
        /*
            Assure user already invoke "useDb" function
        */
        if (!$this->db) {
            
            throw new Exception("You must invoke Use first.");
        }
        
        $parser = new PHPSQLParser();
        $arrResult = $parser->parse( $statement );
        
        $insert = isset( $arrResult['INSERT'] ) ? $arrResult['INSERT'] : NULL;
        $values = isset( $arrResult['VALUES'] ) ? $arrResult['VALUES'] : NULL;
        $select = isset( $arrResult['SELECT'] ) ? $arrResult['SELECT'] : NULL;

        /*
            Handle INSERTS
            We use $insert array and the $values array
        */
        if ($insert) {
            
            // INSERT INTO Artists (ArtistId, ArtistName) VALUES ('001', 'Michael Jackson');
            
            $theCommand = 'INSERT';     // This is just for reference
            $theTable = '';             // Table name
            $arrColumns = NULL;         // Array with the columns for the INSERT
            $arrValues = NULL;          // Array with the values for the INSERT
            $query = array();           // This is the QUERY we use with MONGODB
            
            // Get the table name and the field names for the INSERT
            for ($i=0; $ < sizeof( $insert ); $i++) {
                $row = $insert[ $i ];
                $theTable = $row['table'];
                $arrColumns = isset( $row['columns'] ) ? $row['columns'] : '';
            }

            // Get the values for the insert
            for ($i=0; $ < sizeof( $values ); $i++) {
                $row = $values[ $i ];
                $arrValues = isset( $row['data'] ) ? $row['data'] : '';
            }

            // Validate input...
            if ($arrColumns == '' || sizeof( $arrColumns ) == 0) {
                throw new Exception("Version 1.0 of this library needs column names for INSERT.");
            }
            if ($arrValues == '' || sizeof( $arrValues ) == 0) {
                throw new Exception("Missing column values for INSERT!");
            }
            if (sizeof($arrValues) != sizeof($arrColumns)) {
                throw new Exception("The number of column names is different from column values!");
            }
            
            // Build the query for MongoDB
            for ($c=0; $c < sizeof( $arrColumns ); $c++ ) {
                
                $column = $arrColumns[$c];
                $columnName = $column['base_expr'];
                
                $value = $arrValues[$c]['base_expr'];
                $value = str_replace( "'", "", $value );
                
                $query[ $columnName ] = $value;
            }
            
            $finalObj = array(
                'comman' => $theCommand,
                'table' => $theTable,
                'query' => $query
            );
            
            // Execute MongoDB!
            $this->processInsert( $finalObj );
            
        }
        
    }
    
    
    /*
        Inserts into MONGODB the data we receive
        in our INSERT statement
    */
    private function processInsert( $obj ) {
        
        $tableName = $obj['table'];
        $query = $obj['query'];
        
        // Open the table
        $this->collection = new MongoCollection( $this->db, $tableName );
        
        // Add some extra parameters to our Query for MongoDB
        $id = uniqid();
        $query[ 'id' ] = $id;
        $query[ 'lastupdated' ] = new MongoDate();
        
        // Do the insert
        $this->collection->insert( $query );
        
        $this->resultset = $id;

        $this->lastQuery = $query;
        
    }
    
}

?>