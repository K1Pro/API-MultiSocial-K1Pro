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

if($_SERVER['REQUEST_METHOD'] !== 'OPTIONS' && $_SERVER['REQUEST_METHOD'] !== 'PATCH'){
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

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
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

    if(!isset($jsonData->website)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Social Media website is not set');
        $response->send();
        exit;
    }

    $website = trim($jsonData->website);
    unset($jsonData->website);

    if(count(get_object_vars($jsonData)) !== 1) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Only one social media parameter allowed per website');
        $response->send();
        exit;
    }

    $jsonDataKey = trim(key((array)$jsonData));
    $jsonDataKeyClean = htmlspecialchars(trim(key((array)$jsonData)));
    $jsonDataValue = trim(current((array)$jsonData));
    $jsonDataValueClean = htmlspecialchars(trim(current((array)$jsonData)));

    if(strlen($jsonDataValueClean) > 1000){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Social media parameter too long');
        $response->send();
        exit;
    }

    try {
        $query = $writeDB->prepare("show columns from tblsmparams like '$jsonDataKeyClean'");
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Invalid social media param');
            $response->send();
            exit;
        }

        // Search using JSON_CONTAINS_PATH is working vvvvvvvvvvvvvvvvv
        // $querytwo = $writeDB->prepare('SELECT JSON_CONTAINS_PATH(SMParams, "all", "$.'.$website.'") FROM tblusers WHERE id = :userid');
        // $querytwo->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
        // $querytwo->execute();

        // Inserts JSON into field
        $query = $writeDB->prepare('UPDATE tblusers SET SMParams = JSON_SET(SMParams, "$.'.$website.'.'.$jsonDataKeyClean.'", "'.$jsonDataValueClean.'") WHERE id = :userid');
        $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            // Inserts JSON objects are not present from above query JSON objects will be inserted into field
            $query = $writeDB->prepare('UPDATE tblusers SET SMParams = JSON_SET(COALESCE(SMParams, "{}"), COALESCE("$.'.$website.'", "'.$website.'.'.$jsonDataKeyClean.'"), JSON_OBJECT("'.$jsonDataKeyClean.'", "'.$jsonDataValueClean.'"))  WHERE id = :userid');
            $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
            $query->execute();
            $rowCount = $query->rowCount();

            if($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage('Social media group not updated');
                $response->send();
                exit;
            } else {
                $returnData = array();
                $returnData['rows_returned'] = $rowCount;
                $returnData['sm_group'] = $query->fetch(PDO::FETCH_ASSOC);
        
                $response = new Response();
                $response->setHttpStatusCode(201);
                $response->setSuccess(true);
                $response->addMessage('Social media group created');
                $response->setData($returnData);
                $response->send();
                exit;
            }
        }

        // this was working before JSON conversion, it is used for validating inserted data, try to revive it using JSON_CONTAINS_PATH  vvvvvvvvvvvvvv
        // $query = $writeDB->prepare("select id, website, ".$jsonDataKeyClean.", userid from tblsocialmedia where website = :website and userid = :userid and ".$jsonDataKeyClean." = :jsondatavalue");
        // $query->bindParam(':website', $website, PDO::PARAM_STR);
        // $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
        // $query->bindParam(':jsondatavalue', $jsonDataValue, PDO::PARAM_STR);
        // $query->execute();

        // $rowCount = $query->rowCount();

        // if($rowCount === 0) {
        //     $response = new Response();
        //     $response->setHttpStatusCode(404);
        //     $response->setSuccess(false);
        //     $response->addMessage('No social media group found after update');
        //     $response->send();
        //     exit;
        // }
        // this was working before JSON conversion  ^^^^^^^^^^^^^^^^^^^^^^^^

        $returnData = array();
        $returnData['rows_returned'] = $rowCount;
        $returnData['sm_group'] = $query->fetch(PDO::FETCH_ASSOC);

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->addMessage('Social media group updated');
        $response->setData($returnData);
        $response->send();
        exit;

    }
    catch(PDOException $ex){
        error_log('Database query error - '.$ex, 0);
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage('Failed to update social media group - check your data for errors');
        $response->send();
        exit;
    }

}

?>