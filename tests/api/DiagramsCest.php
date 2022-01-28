<?php

class DiagramsCest
{
    public function _before(ApiTester $I)
    {
        $I->amHttpAuthenticated('phpsldt_tester', 'pass');
    }

    public function getAllowMethodsTest(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', '*/*');
        
        foreach(['/diagrams'=>'GET,POST,OPTIONS', '/diagrams/1'=>'GET,PUT,DELETE,OPTIONS'] as $endpoint=>$methods){
            $I->sendOptions($endpoint);
            $I->seeResponseCodeIs(200);
            $I->seeHttpHeader('Access-Control-Allow-Methods', $methods);
        }
        
        $I->sendOptions('/diagrams/null');
        $I->seeResponseCodeIs(404);
        $I->dontSeeHttpHeader('Access-Control-Allow-Methods');
    }
    
    public function setAcceptHeaderTest(ApiTester $I)
    {
        foreach(['application/json'=>200, '*/*'=>200, ''=>406] as $header_accept=>$code){
            $I->haveHttpHeader('Accept', $header_accept);
        
            $I->sendOptions("/diagrams");
            $I->seeResponseCodeIs($code);
        }
        
    }
    
    public function getAllDiagramsTest(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', '*/*');
        $I->sendGET('/diagrams');
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('X-Total-Count');
        $I->seeHttpHeader('Link');
        $I->seeResponseIsValidOnJsonSchemaString('
            {
                "type": "array",
                "items": {
                    "type": "object"
                }
            }
        ');
    }
    
    public function getTheDiagramTest(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', '*/*');
        $I->sendGET('/diagrams/1');
        $I->seeResponseCodeIs(200);
        
        $schema='
            {
                "label": "string",
                "location": "string",
                "phases": "string",
                "ground": "string",
                "enclosure": {
                    "type": "object",
                    "properties": {
                        "model": {"type": "string"},
                        "article": {"type": "string"},
                        "construction": {"type": "string"}
                    },
                    "additionalProperties": false
                }
            }
        ';
        
        $I->seeResponseIsValidOnJsonSchemaString($schema);
    }
    
    public function notFoundDiagramTest(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', '*/*');
        $I->sendGET('/diagrams/null');
        $I->seeResponseCodeIs(404);
        
        $I->haveHttpHeader('Accept', '*/*');
        $I->sendDELETE('/diagrams/null');
        $I->seeResponseCodeIs(404);
    }
    
    public function createNewDiagramPreconditionFailedTest(ApiTester $I)
    {
        //Empty data
        $I->haveHttpHeader('Accept', '*/*');
        $I->sendPOST('/diagrams');
        $I->seeResponseCodeIs(412);

        //Parse error 
        $I->haveHttpHeader('Accept', '*/*');
        $I->sendPOST('/diagrams', '{');
        $I->seeResponseCodeIs(412);

    }
    
    public function createNewDiagramUnprocessableTest(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', '*/*');
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/diagrams', "{}");
        $I->seeResponseCodeIs(422);
    }
    
    public function createNewDiagramTest(ApiTester $I)
    {
        $json = '{
          "label": "New testing single line diagram",
          "applications": [
            {
              "position": 0,
              "label": "string",
              "desc": "string",
              "cable": {
                "label": "string",
                "model": "string",
                "length": "string"
              },
              "pipe": {
                "label": "string",
                "length": "string"
              },
              "load": {
                "installed": {
                  "capacity": "string",
                  "current": "string",
                  "current_a": "string",
                  "current_b": "string",
                  "current_c": "string",
                  "power_factor": "string"
                }
              }
            }
          ]
        }';
        
        $I->haveHttpHeader('Accept', '*/*');
        $I->sendPOST('/diagrams', $json);
        $I->seeResponseCodeIs(201);
        $I->seeHttpHeader('Location');
    }
    
    public function updateDiagramTest(ApiTester $I){
        $json ='
            {
              "label": "string",
              "location": "string",
              "phases": "string",
              "ground": "string",
              "enclosure": {
                "model": "string",
                "article": "string",
                "construction": "string",
                "protection": "string",
                "modules": "string"
              },
              "load": {
                "demand_factor": "string",
                "installed": {
                  "capacity": "string",
                  "current": "string",
                  "current_a": "string",
                  "current_b": "string",
                  "current_c": "string"
                }
              },
              "estimated": {
                "power": "string",
                "current": "string"
              },
              "supplier": {
                "label": "string",
                "cable": "string",
                "device": {
                  "label": "string",
                  "device": "string",
                  "rating": "string",
                  "trip_settings": "string",
                  "interrupting_rating": "string",
                  "type": "string",
                  "poles": "string",
                  "leakage_current_settings": "string"
                }
              },
              "applications": [
                {
                  "position": 0,
                  "label": "string",
                  "desc": "string",
                  "cable": {
                    "label": "string",
                    "model": "string",
                    "length": "string"
                  },
                  "pipe": {
                    "label": "string",
                    "length": "string"
                  },
                  "load": {
                    "installed": {
                      "capacity": "string",
                      "current": "string",
                      "current_a": "string",
                      "current_b": "string",
                      "current_c": "string",
                      "power_factor": "string"
                    }
                  }
                }
              ]
            }
        ';
        
        $I->haveHttpHeader('Accept', '*/*');
        $I->sendPUT('/diagrams/1', $json);
        $I->seeResponseCodeIs(204);
    }
}
