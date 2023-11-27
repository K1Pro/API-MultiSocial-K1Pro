<?php

require_once('../../../../login/v001/public/controller/components/logindb.php');
require_once('db.php');
require_once('../../../../login/v001/public/model/ValidLogin.php');
require_once('../model/Response.php');

try{
    $loginDB = DBlogin::connectLoginDB();
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();
}
catch(PDOException $ex) {
    error_log('Connection error: '.$ex, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage('Database connection error');
    $response->send();
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'OPTIONS' && $_SERVER['REQUEST_METHOD'] !== 'POST'){
    $response = new Response();
    $response->setHttpStatusCode(405);
    $response->setSuccess(false);
    $response->addMessage('Request method not allowed');
    $response->send();
    exit;
}

// loads the authentication module
require_once('../../../../login/v001/public/controller/components/authentication.php');
// returns $loggedin_userid

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Content Type header not set to JSON');
        $response->send();
        exit;
    }

    $rawPatchData = file_get_contents('php://input');

    if(!$jsonData = json_decode($rawPatchData)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Request body is not valid JSON');
        $response->send();
        exit;
    }

    if(strlen($jsonData->Keyword) < 1){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (strlen($jsonData->Keyword) < 1 ? $response->addMessage('Keyword cannot be blank') : false);
        $response->send();
        exit;
    }

    $keyword = htmlspecialchars(trim($jsonData->Keyword));

    $ch = curl_init();
    $URLRequest = "https://api.dictionaryapi.dev/api/v2/entries/en/".$keyword;
    curl_setopt($ch, CURLOPT_URL,$URLRequest);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close ($ch);
    $DictionaryResponse = json_decode($response);

    if (!$DictionaryResponse->title) {
        $generatedText = htmlspecialchars(trim(json_encode($DictionaryResponse[0])));

        $query = $writeDB->prepare('UPDATE tblusers SET GeneratedText = JSON_SET(COALESCE(GeneratedText, "{}"), "$.'.$keyword.'", "'.$generatedText.'") WHERE id = :userid');
        $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Generated text not updated');
            $response->send();
            exit;
        }
    }

    $returnData = array();
    $returnData['keyword'] = $keyword;
    $returnData['dictionary_api'] = !$DictionaryResponse->title ? $DictionaryResponse[0] : "No entries found";

    $response = new Response();
    $response->setHttpStatusCode(201);
    $response->setSuccess(true);
    $response->addMessage('Generated text saved');
    $response->setData($returnData);
    $response->send();
    exit;


}

?>