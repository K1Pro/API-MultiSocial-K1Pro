<?php

require_once('db.php');
require_once('../model/Response.php');

try{
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

if($_SERVER['REQUEST_METHOD'] !== 'OPTIONS' && $_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'PATCH'){
    $response = new Response();
    $response->setHttpStatusCode(405);
    $response->setSuccess(false);
    $response->addMessage('Request method not allowed');
    $response->send();
    exit;
}

// handle options request method for authentication VVVVVV
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    $response = new Response();
    $response->setHttpStatusCode(200);
    $response->setSuccess(true);
    $response->send();
    exit;
}
// handle options request method for authentication ^^^^^^
    
// begin authentication script
if(!isset($_SERVER[$CUSTOM_AUTHORIZATION]) || strlen($_SERVER[$CUSTOM_AUTHORIZATION]) < 1) {
    $response = new Response();
    $response->setHttpStatusCode(401);
    $response->setSuccess(false);
    (!isset($_SERVER[$CUSTOM_AUTHORIZATION]) ? $response->addMessage('Access token is missing from the header') : false);
    (strlen($_SERVER[$CUSTOM_AUTHORIZATION]) < 1 ? $response->addMessage('Access token cannot be blank') : false);
    $response->send();
    exit();
}

$accesstoken = $_SERVER[$CUSTOM_AUTHORIZATION];

try{

    $query = $writeDB->prepare('select userid, accesstokenexpiry, UserActive, LoginAttempts from tblsessions, tblusers where tblsessions.userid = tblusers.id and accesstoken = :accesstoken');
    $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
    $query->execute();
    
    $rowCount = $query->rowCount();

    if ($rowCount === 0) {
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage('Invalid access token');
        $response->send();
        exit();
    }

    $row = $query->fetch(PDO::FETCH_ASSOC);

    $returned_userid = $row['userid'];
    $returned_accesstokenexpiry = $row['accesstokenexpiry'];
    $returned_useractive = $row['UserActive'];
    $returned_loginattempts = $row['LoginAttempts'];

    if ($returned_useractive !== 'Y') {
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage('User account not active');
        $response->send();
        exit();
    }

    if($returned_loginattempts >= 3) {
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage('User account is currently locked out');
        $response->send();
        exit();
    }

    if (strtotime($returned_accesstokenexpiry) < time()){
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage('Access token expired');
        $response->send();
        exit();
    }
}
catch(PDOException $ex) {
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage('There was an issue authenticating - please try again');
    $response->send();
    exit();
}
// end authentication script

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if(array_key_exists('smwebsite',$_GET)) {
        $smwebsite = $_GET['smwebsite'];
        try{
            $query = $readDB->prepare('select * from tblsocialmedia where website = :smwebsite and userid = :userid');
            $query->bindParam(':smwebsite', $smwebsite, PDO::PARAM_INT);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Social media group not found');
                $response->send();
                exit;
            }

            // while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            //     $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
            //     $taskArray[] = $task->returnTaskAsArray();
            // }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['sm_group'] = $query->fetch(PDO::FETCH_ASSOC);

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage('Social media group found');
            // $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;
        }
        catch(PDOException $ex){
            error_log('Database query error - '.$ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get social media group');
            $response->send();
            exit();
        }

    } elseif (array_key_exists('active', $_GET)){
        $active = $_GET['active'];

        if($active !== 'true' && $active !== 'false'){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Active filter must be true or false');
            $response->send();
            exit;
        }

        try{
            $query = $readDB->prepare('select * from tblsocialmedia where active = :active and userid = :userid');
            $query->bindParam(':active', $active, PDO::PARAM_STR);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('No active social media groups found');
                $response->send();
                exit;
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['sm_group'] = $query->fetchAll(PDO::FETCH_ASSOC);

            $response = new Response;
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage('Active social media groups found');
            // $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;
        }
        catch (PDOException $ex){
            error_log('Database query error -'.$ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get active social media groups');
            $response->send();
            exit;
        }

    } elseif (empty($_GET)) {
        try{
            $query = $readDB->prepare('select * from tblsocialmedia where userid = :userid');
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Social media groups not found');
                $response->send();
                exit;
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['sm_groups'] = $query->fetchAll(PDO::FETCH_ASSOC);

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage('Social media groups found');
            // $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;
        }
        catch(PDOException $ex){
            error_log('Database query error - '.$ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get social media groups');
            $response->send();
            exit();
        }

    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
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
        $query = $writeDB->prepare("show columns from tblsocialmedia like '$jsonDataKeyClean'");
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Incorrect social media parameter provided');
            $response->send();
            exit;
        }

        $query = $writeDB->prepare('select id from tblsocialmedia where website = :website and userid = :userid');
        $query->bindParam(':website', $website, PDO::PARAM_STR);
        $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            $query = $writeDB->prepare("insert into tblsocialmedia (website, ".$jsonDataKeyClean.", userid) values (:website, :jsondatavalue, :userid)");
            $query->bindParam(':website', $website, PDO::PARAM_STR);
            $query->bindParam(':jsondatavalue', $jsonDataValue, PDO::PARAM_STR);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();
            // there is a bug here on the local testing environment, works fine though in live

            $rowCount = $query->rowCount();

            if($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('Failed to create social media group');
                $response->send();
                exit;
            }

            $lastSocialMediaGroupID = $writeDB->lastInsertId();

            $query = $writeDB->prepare("select id, website, ".$jsonDataKeyClean.", userid from tblsocialmedia where id = :socialmediaid and userid = :userid");
            $query->bindParam(':socialmediaid', $lastSocialMediaGroupID, PDO::PARAM_INT);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('Failed to retrieve social media group after creation');
                $response->send();
                exit;
            }

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

        $query = $writeDB->prepare("update tblsocialmedia set ".$jsonDataKeyClean." = :jsondatavalue where website = :website and userid = :userid");
        $query->bindParam(':jsondatavalue', $jsonDataValue, PDO::PARAM_STR);
        $query->bindParam(':website', $website, PDO::PARAM_STR);
        $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Social media group not updated');
            $response->send();
            exit;
        }

        $query = $writeDB->prepare('UPDATE tblusers SET SMParams = JSON_REPLACE(SMParams, "$.'.$website.'.'.$jsonDataKeyClean.'", "'.$jsonDataValueClean.'") WHERE id = :userid');
        $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
        $query->execute();

        $query = $writeDB->prepare("select id, website, ".$jsonDataKeyClean.", userid from tblsocialmedia where website = :website and userid = :userid and ".$jsonDataKeyClean." = :jsondatavalue");
        $query->bindParam(':website', $website, PDO::PARAM_STR);
        $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
        $query->bindParam(':jsondatavalue', $jsonDataValue, PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage('No social media group found after update');
            $response->send();
            exit;
        }

        $returnData = array();
        $returnData['rows_returned'] = $rowCount;
        $returnData['sm_group'] = $query->fetch(PDO::FETCH_ASSOC);

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        // $response->addMessage($textcustom);
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