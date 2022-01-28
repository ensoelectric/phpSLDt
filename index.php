<?php

namespace ru\ensoelectic\phpSLDt;
use PDO;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Handler.php';

set_exception_handler('ru\ensoelectic\phpSLDt\exception_handler');
set_error_handler('ru\ensoelectic\phpSLDt\exception_error_handler');

define('DBHOST', '127.0.0.1');
define('DBNAME', 'phpSLDt_development');
define('DBCHARSET', 'utf8');

$http_accept = ["application/json", "*/*", "application/pdf"];

list($resource, $id) = array_pad(explode('/', rtrim($_REQUEST["endpoint"], '/')), 2, NULL);

list($user, $pass) = [$_SERVER['PHP_AUTH_USER'] ?? NULL, $_SERVER['PHP_AUTH_PW'] ?? NULL];

$resource = 'ru\ensoelectic\phpSLDt\\'.ucfirst($resource);

if (!class_exists($resource, true)) throw new \Exception("I'm a Teapot", 418);

if (!in_array($_SERVER['HTTP_ACCEPT'] ?? NULL, $http_accept)) throw new \Exception("Not Acceptable", 406);
  
$dsn = 'mysql:host='.DBHOST.';dbname='.DBNAME.';charset='.DBCHARSET;
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
    ];

$headers = Headers::getInstance();

try{

    $pdo = new PDO($dsn, $user, $pass, $opt);

    $pdo->beginTransaction();

    $resource = new $resource($pdo);
    
    if(!in_array(Helpers::getRequestMethod(), $resource->options($id ?? NULL))) throw new \Exception("Method Not Allowed", 405); 
    
    switch(Helpers::getRequestMethod()) {
		case 'DELETE':
			$resource->delete($id);
            
            $headers->add("HTTP/1.1 204 No content.");
            $headers->add("Content-Type: application/json");
			
            break;
		case 'POST':
			$insert_id = $resource->create(file_get_contents("php://input"));
            
            $headers->add("HTTP/1.1 201 Created.");
            $headers->add("Content-Type: application/json");
            
            if(empty($insert_id)) break;
            
            $headers->add("Location: ".Helpers::getProtocol()."".$_SERVER['HTTP_HOST']."".strtok($_SERVER['REQUEST_URI'], "?")."/".$insert_id);
               
            $response = $resource->find($insert_id);
            
			break;
		case 'GET':
            $response = !empty($id) ? $resource->find($id, $_SERVER['HTTP_ACCEPT'] ?? NULL) : $resource->findAll(intval($_GET['page'] ?? 1), intval($_GET['per_page'] ?? 150), $_GET['search'] ?? NULL);
            
            $headers->add("HTTP/1.1 200 OK");
            $headers->add("Content-Type: application/json");
         
			break;
		case 'PUT':
            
			$resource->update($id, file_get_contents("php://input"));
            
            $headers->add("HTTP/1.1 204 No content.");
            $headers->add("Content-Type: application/json");
            
			break;	
        case 'OPTIONS':
       
            $headers->add("HTTP/1.1 200 OK");
            $headers->add("Content-Type: application/json");
            $headers->add("Access-Control-Allow-Methods: ".implode(",", $resource->options($id ?? NULL)));
            
			break;							
		default:
			throw new \Exception("", 500);
		}

    $pdo->commit();

} catch(JsonException $e) {
    $pdo->rollback(); 
    throw new \Exception($e->getMessage(), 412);
} catch(\PDOException $e) {
    
    $errorInfo = $e->errorInfo;
    
    //Dirty hack because "errorInfo" method returns NULL on DB connection error (Access denied for user)
    if(empty($errorInfo[2]) && strstr($e->getMessage(), 'SQLSTATE['))
        preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
        
    switch($errorInfo[1] ?? $matches[2]){ 
            case '1045': //Access denied for user
                throw new \Exception($e->getMessage(), 401);
			case '1048': //Cannot be null
			case '1062': //Duplicate entry for key 'SERIAL NUMBER UNIQUE
            case '1264': //Numeric value out of range
			case '1366': //Incorrect double value
			case '1406': //Data too long
			case '1452': //Cannot add or update a child row
                $pdo->rollback();
				throw new \Exception($e->getMessage(), 422);
			case '1451': //Cannot delete or update a parent row
                $pdo->rollback();
				throw new \Exception($e->getMessage(), 409);
			case '1142': //command denied to user
                $pdo->rollback();
				throw new \Exception($e->getMessage(), 403);
			default:
				throw $e;
		}
    
} catch(\Exception $e) {
    $pdo->rollback();     
    throw $e;
}

$headers->add('Access-Control-Allow-Headers: Content-Type, Origin, Authorization'); 
$headers->add('Access-Control-Allow-Origin: *');

$headers->send();

echo $response ?? NULL;