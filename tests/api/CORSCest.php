<?php

class CORSCest
{

    public function getResponseToPreflightRequestCORS(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', '*/*');
        $I->haveHttpHeader('Access-Control-Request-Method', 'GET');
        $I->haveHttpHeader('Access-Control-Request-Headers', 'authorization');
        
        $I->sendOptions('/diagrams/');
        
        $I->seeResponseCodeIs(200);
        
        $I->seeHttpHeader('Access-Control-Allow-Origin', '*');
        $I->seeHttpHeader('Access-Control-Allow-Methods', '*');
        $I->seeHttpHeader('Access-Control-Allow-Headers', '*');
        
    }

}
