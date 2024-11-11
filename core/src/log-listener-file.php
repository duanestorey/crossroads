<?php

namespace CR;

class LogListenerFile extends LogListener {
    protected $currentLevel = LOG::INFO;
    protected $startTime = 0;

    private $fileName = null;
    private $fileHandle = null;

    public function __construct( $fileName ) {
        $this->fileName = $fileName;
        $this->startTime = microtime( true );
    }

    public function setLevel( $level ) {
        $this->currentLevel = $level;
    }    

    public function log( $message, $tabs, $level ) {
        if ( $level < $this->currentLevel ) {
            return;
        }
            
        if ( !$this->fileHandle ) {
            $this->fileHandle = fopen( $this->fileName, "a+" );
            fprintf( $this->fileHandle, "\n" );
        }

        $message = $this->getTabsAsSpaces( $tabs ) . $message;

        $elapsed = microtime( true ) - $this->startTime;

        switch( $level ) {
            case Log::DEBUG:
                fprintf( $this->fileHandle, "%0.4fs - [DEBUG] %s\n", $elapsed, $message );
                break;
            case Log::INFO:
                fprintf( $this->fileHandle, "%0.4fs - [INFO ] %s\n", $elapsed, $message );
                break;
            case Log::WARNING:
                fprintf( $this->fileHandle, "%0.4fs - [WARN ] %s\n", $elapsed, $message ); 
                break;
            case Log::ERROR:
                fprintf( $this->fileHandle, "%0.4fs - [ERROR] %s\n", $elapsed, $message ); 
                break;    
        }

        fflush( $this->fileHandle );
    }

    private function getTabsAsSpaces( $tabs ) {
        $spaces = '';
        for( $i = 0; $i < $tabs; $i++ ) {
            $spaces = $spaces . '  ';
        }

        return $spaces;
    }
}