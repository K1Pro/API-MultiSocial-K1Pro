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

if($_SERVER['REQUEST_METHOD'] !== 'OPTIONS' && $_SERVER['REQUEST_METHOD'] !== 'GET'){
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

    $query = $writeDB->prepare('select userid, accesstokenexpiry, UserActive, LoginAttempts, Organization from tblsessions, tblusers where tblsessions.userid = tblusers.id and accesstoken = :accesstoken');
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
    $returned_organization = $row['Organization'];

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
    try{
        $active = 'true';
        $query = $readDB->prepare('select * from tblposts where organization = :organization');
        $query->bindParam(':organization', $returned_organization, PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage('No posts found');
            $response->send();
            exit;
        }

        $posted = $query->fetchAll(PDO::FETCH_ASSOC);

        $returnData = array();
        $returnData['rows_returned'] = $rowCount;
        $returnData['posted'] = $posted;

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->addMessage('Successfully retrieved posts');
        $response->setData($returnData);
        $response->send();
        exit;

    }
    catch (PDOException $ex){
        error_log('Database query error -'.$ex, 0);
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage('There was an issue getting posts');
        $response->send();
        exit;
    }

}

?>