<?php

namespace ru\ensoelectic\phpSLDt;

function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
}


function exception_handler($exception) {
   
    Headers::getInstance()->clear();
   
    $status = array(  
		200 => array("title" => 'OK', "detail" => ''),
		201 => array("title" => 'Created', "detail" => ''),
		400 => array("title" => 'Bad Request', "detail" => 'Универсальный код ошибки, если серверу непонятен запрос от клиента.'),
        401 => array("title" => 'Unauthorized', "detail" => 'Указывает, что запрос не был применён, поскольку ему не хватает действительных учётных данных для целевого ресурса.'),
		403 => array("title" => 'Forbidden', "detail" => 'Возвращается, если операция запрещена для текущего пользователя. Если у оператора есть учётка с более высокими правами, он должен перелогиниться самостоятельно. См. также 419'),
		404 => array("title" => 'Not Found', "detail" => 'Возвращается, если в запросе был указан неизвестный entity или id несуществующего объекта. Списочные методы get не должны возвращать этот код при верном entity (см. выше). Если запрос вообще не удалось разобрать, следует возвращать 418.'),
		405 => array("title" => 'Method Not Allowed', "detail" => ''),
        406 => array("title" => 'Not Acceptable', "detail"=> 'The Accept header response type is not supported. The supported types is JSON.'),
		409	=> array("title" => 'Conflict', "detail" => 'Этот ответ отсылается, когда запрос конфликтует с текущим состоянием сервера. Например, если невозможно удалить запись из таблицы из-за имеющихся связей.'),
		412	=> array("title" => 'Precondition Failed', "detail" => 'Клиент указал в своих заголовках условия, которые сервер не может выполнить'),
		415 => array("title" => 'Unsupported Media Type', "detail" => 'Возвращается при загрузке файлов на сервер, если фактический формат переданного файла не поддерживается. Также может возвращаться, если не удалось распарсить JSON запроса, или сам запрос пришёл не в формате JSON.'),
		418 => array("title" => 'I\'m a Teapot', "detail" => 'Возвращается для неизвестных серверу запросов, которые не удалось даже разобрать. Обычно это указывает на ошибку в клиенте, типа ошибки при формировании URI, либо что версии протокола клиента и сервера не совпадают.'),
		419 => array("title" => 'Authentication Timeout', "detail" => 'Отправляется, если клиенту нужно пройти повторную авторизацию (например, протухли куки или CSRF токены).'),
		422 => array("title" => 'Unprocessable Entity', "detail" => 'Запрос корректно разобран, но содержание запроса не прошло серверную валидацию. Например, в теле запроса были указаны неизвестные серверу поля, или не были указаны обязательные, или с содержимым полей что-то не так.'),
		500 => array("title" => 'Internal Server Error', "detail" => 'Возвращается, если на сервере вылетело необработанное исключение или произошла другая необработанная ошибка времени исполнения.'),
		501 => array("title" => 'Not Implemented', "detail" => 'Возвращается, если текущий метод неприменим (не реализован) к объекту запроса.'),
		);
    
    $code = $exception->getCode();
    array_key_exists($code, $status) ? Headers::getInstance()->add("HTTP/1.0 $code ".$status[$code]['title']) : Headers::getInstance()->add("HTTP/1.0 500 ".$status[500]['title']);
    
    //header("Content-Type:application/problem+json");
    syslog($code >= 500 ? LOG_ERR : LOG_NOTICE, $exception);
    
    Headers::getInstance()->send();
    die;
  }