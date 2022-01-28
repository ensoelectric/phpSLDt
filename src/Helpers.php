<?php

namespace ru\ensoelectic\phpSLDt;

class Helpers {
	
    public static function getProtocol(): string
    {
        //HTTPS or HTTP
		return !empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']) ? "https://" : "http://" ;
	}
    
    public static function getRequestMethod(): string
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
            switch($_SERVER['HTTP_X_HTTP_METHOD']){
                case 'DELETE':
                    return 'DELETE';
                    break;
                case 'PATCH':
                    return 'PATCH';
                    break;
                default:
                    $this->_response(null, 405);
                    break; 
            }

        return $_SERVER['REQUEST_METHOD'];
    }
  
    public function rfc5988Link($uri, $records, $current, $per_page): string
    {
    
        if(filter_var($current, FILTER_VALIDATE_INT, array("options" => array("min_range"=>1))) === FALSE) 
          throw new \Exception('The current page is not a positive integer. Unable to form link header.');

        if(filter_var($records, FILTER_VALIDATE_INT, array("options" => array("min_range"=>1))) === FALSE) 
          throw new \Exception('The number of records is not a positive integer. Unable to form link header.');
        
        if(filter_var($per_page, FILTER_VALIDATE_INT, array("options" => array("min_range"=>1))) === FALSE) 
          throw new \Exception('The number of items per page is not a positive integer. Unable to form link header.');
        
        if(filter_var($uri, FILTER_VALIDATE_URL) === FALSE){
            $uri=self::getProtocol().$uri;
            if(filter_var($uri, FILTER_VALIDATE_URL) === FALSE) throw new \Exception('The URI is not valid. Unable to form link header.');
        }

        $last_page = ceil(intval($records)/intval($per_page));

        $uri = empty($_REQUEST) ? $uri : $uri."?";
        
        unset($_REQUEST['endpoint']);

        $_REQUEST['page'] = $current;
        $links[] = "<".$uri."".http_build_query($_REQUEST).">; rel=\"current\"";

        if($current < $last_page){
            $_REQUEST['page'] = $current+1;
            $links[] = "<".$uri."".http_build_query($_REQUEST).">; rel=\"next\"";
        }

        if($current < $last_page && $current > 1){
            $_REQUEST['page'] = $current-1;
            $links[] = "<".$uri."".http_build_query($_REQUEST).">; rel=\"prev\"";
        }

            $_REQUEST['page'] = $last_page;
            $links[] = "<".$uri."".http_build_query($_REQUEST).">; rel=\"last\"";
        
        return implode(",", $links);

	}
    
    public static function json_decode($json, $assoc = false, $depth = 512)
    {
		$data = json_decode($json, $assoc, $depth);

		if (JSON_ERROR_NONE !== json_last_error()) {
			throw new JsonException('Failed to parse JSON: '. json_last_error_msg(), json_last_error());
		}
        
		return $data;
	}
}

