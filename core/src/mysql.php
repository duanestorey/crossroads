<?php

namespace CR;

class MYSQL {
    protected $sql;
    protected $config;

    public function __construct( $config ) {   
        $this->config = $config;

        $this->sql = new \SQLite3( CROSSROADS_DB_DIR . '/db.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE );
    }

    public function __destruct() {
        if ( $this->sql ) {
            $this->sql->close();
        }
    }

    public function rebuild() {
        $schemaFiles = Utils::findAllFilesWithExtension( CROSSROADS_CORE_DIR . '/schemas', 'sql' );
        if ( $schemaFiles ) {
            foreach( $schemaFiles as $schema ) {
                $schemaContents = file_get_contents( $schema ); 
                $this->sql->query( $schemaContents );
            }
        }
    }

    public function escape( $str ) {
        return $this->sql->escapeString( $str );
    }

    public function escapeWithTicks( $str ) {
        return "'" . $this->sql->escapeString( $str ) . "'";
    }

    public function query( $sql ) {
        return $this->sql->query( $sql );
    }

    public function getLastRowID() {
        return $this->sql->lastInsertRowID();
    }
}