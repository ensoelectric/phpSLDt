<?php

namespace ru\ensoelectic\phpSLDt;

class Headers{
    
    private $headers = [];
    
    private static $instance = null;
    
    protected function __construct() { }

    protected function __clone() { }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    static public function getInstance(): Headers 
    {
        if(is_null(self::$instance)) self::$instance = new self();
        
        return self::$instance;
    }
    
    public function add(String $header): void
    {
        $this->headers[]=$header;
    }
    
    public function clear(): void
    {
        $this->headers = [];
    }
    
    public function send():void
    {
        foreach($this->headers as $header)
            header($header);
    }
    
}