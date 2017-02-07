<?php
require_once __DIR__ . '/../Helpers/Access.php';
require_once __DIR__ . '/../Helpers/Cxn.php';

abstract class BaseController {

    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';

	/**
     * Property: key
     * API Key to connect
     */
    protected $key = '';

    /**
     * Property: args
     * Any additional URI components after the endpoint and key have been removed, in our
     * eg: /<endpoint>/<verb>/<arg0>/<arg1> or /<endpoint>/<arg0>
     */
    protected $args = Array();

    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

	public function __construct($args, $endpoint, $domain) {
        $this->args = $args;
        $this->endpoint = $endpoint;

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->key = array_shift($this->args);
        }

		$verification = new Access(new Cxn("shirley"));

        if($verification->verifyDomain($domain)) {
            header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
            header("Access-Control-Allow-Methods: GET, POST");
            header("Content-Type: application/json charset=utf-8");    
        }
		
		if (!$this->key) {
            throw new Exception('No API Key provided');
        } else if (!$verification->verifyKey($this->key, $domain)) {
            throw new Exception('Invalid API Key');
        }

        $this->method = $_SERVER['REQUEST_METHOD'];

        if($this->method !== 'POST' || $this->method !== 'GET') {
            $this->_response('Invalid Method', 405);
        }

	}

	public function executeAction() {

        if (method_exists($this, $this->endpoint)) {
            return $this->_response($this->{$this->endpoint}($this->args));
        }
        return $this->_response("Error: No Endpoint: $this->endpoint", 404);

    }

    protected function locationVerification($locationType,
        $args) {

        print_r($args);
        print_r($this->args);

        if (strtolower($locationType) === 'zipcode') {
            if (count($this->locationSettings) !== 2 || 
                !is_numeric($this->args[0]) || 
                !is_numeric($this->args[1])) {
                    throw new Exception('Incorrect URI structure for this endpoint');
            } else {
                $zip = new ZIP(new Cxn("shirley"),$this->args);
                return $zip->radius();
            }
        } else if (strtolower($locationType) === 'cityState') {
            if (count($this->locationSettings) !== 2 || !is_numeric($this->args[2])) {
                throw new Exception('Incorrect URI structure for this endpoint');
            } else {
                $zip = new ZIP(new Cxn("shirley"),$this->args);
                retrun $zip->cityzips();       
            }

        }
    
    }

    private function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }

    private function _requestStatus($code) {
        $status = array(  
            200 => 'OK',
            404 => 'Not Found',   
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ); 
        return ($status[$code])?$status[$code]:$status[500]; 
    }
	
}

?>