<?php

require_once('db.php');
require_once('../model/User.php');
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

if($_SERVER['REQUEST_METHOD'] !== 'OPTIONS' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'DELETE'){
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

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // this route creates a user

    if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Content type header not set to JSON');
        $response->send();
        exit;
    }

    $rawPostData = file_get_contents('php://input');

    if(!$jsonData = json_decode($rawPostData)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Request body is not valid JSON');
        $response->send();
        exit;
    }

    if(!isset($jsonData->FirstName) || !isset($jsonData->Username) || !isset($jsonData->Password) || !isset($jsonData->Email)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (!isset($jsonData->FirstName) ? $response->addMessage('First name not supplied') : false);
        (!isset($jsonData->Username) ? $response->addMessage('Username not supplied') : false);
        (!isset($jsonData->Password) ? $response->addMessage('Password not supplied') : false);
        (!isset($jsonData->Email) ? $response->addMessage('Email not supplied') : false);
        $response->send();
        exit;
    }

    // you can insert more password checks here, such as one uppercase, lowercase and number....
    if(strlen($jsonData->FirstName) < 1 || strlen($jsonData->FirstName) > 255 || strlen($jsonData->Username) < 1 || strlen($jsonData->Username) > 255 || strlen($jsonData->Password) < 1 || strlen($jsonData->Password) > 255 || strlen($jsonData->Email) < 1 || strlen($jsonData->Email) > 255){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (strlen($jsonData->FirstName) < 1 ? $response->addMessage('First name cannot be blank') : false);
        (strlen($jsonData->FirstName) > 255 ? $response->addMessage('First name cannot be greater than 255 characters') : false);
        (strlen($jsonData->Username) < 1 ? $response->addMessage('Username cannot be blank') : false);
        (strlen($jsonData->Username) > 255 ? $response->addMessage('Username cannot be greater than 255 characters') : false);
        (strlen($jsonData->Password) < 1 ? $response->addMessage('Password cannot be blank') : false);
        (strlen($jsonData->Password) > 255 ? $response->addMessage('Password cannot be greater than 255 characters') : false);
        (strlen($jsonData->Email) < 1 ? $response->addMessage('Email cannot be blank') : false);
        (strlen($jsonData->Email) > 255 ? $response->addMessage('Email cannot be greater than 255 characters') : false);
        $response->send();
        exit;
    }

    $firstname = trim($jsonData->FirstName);
    $username = trim($jsonData->Username);
    $password = $jsonData->Password;
    $email = strtolower(trim($jsonData->Email));

    try {
        $query = $writeDB->prepare('select id from tblusers where Username = :username');
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();
        
        if($rowCount !== 0) {
            $response = new Response();
            $response->setHttpStatusCode(409);
            $response->setSuccess(false);
            $response->addMessage('Username already exists');
            $response->send();
            exit;
        }

        $query = $writeDB->prepare('select id from tblusers where Email = :email');
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();
        
        if($rowCount !== 0) {
            $response = new Response();
            $response->setHttpStatusCode(409);
            $response->setSuccess(false);
            $response->addMessage('Email already exists');
            $response->send();
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = $writeDB->prepare('insert into tblusers (FirstName, Username, Password, Email) values (:firstname, :username, :password, :email)');
        $query->bindParam(':firstname', $firstname, PDO::PARAM_STR);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue creating a user account - please try again');
            $response->send();
            exit;
        }
        
        $lastUserID = $writeDB->lastInsertId();

        $returnData = array();
        $returnData['user_id'] = $lastUserID;
        $returnData['FirstName'] = $firstname;
        $returnData['Username'] = $username;
        $returnData['Email'] = $email;

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->addMessage('User Created');
        $response->setData($returnData);
        $response->send();
        exit;


    }
    catch(PDOException $ex) {
        error_log('Database query error: '.$ex, 0);
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage('There was an issue creating a user account - please try again');
        $response->send();
        exit;
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if(empty($_GET)){
        try{
            $query = $readDB->prepare('select id, FirstName, Username, Email, UserActive, AccountType, LoginAttempts, Organization, Website, Tag1, Tag2, Tag3, Pexels, SMParams, SMPosts from tblusers');
            $query->execute();

            $rowCount = $query->rowCount();

            $userArray = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $user = new User($row['id'], $row['FirstName'], $row['Username'], $row['Email'], $row['UserActive'], $row['AccountType'], $row['LoginAttempts'], $row['Organization'], $row['Website'], $row['Tag1'], $row['Tag2'], $row['Tag3'], $row['Pexels'], $row['SMParams'], $row['SMPosts']);
                $userArray[] = $user->returnUserAsArray();
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['users'] = $userArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            // $response->toCache(true);
            $response->addMessage('Retrieved users');
            $response->setData($returnData);
            $response->send();
            exit;

        }
        catch(UserException $ex){
            
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit;
        }
        catch(PDOException $ex){
            error_log('Database query error - '.$ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get users');
            $response->send();
            exit;
        }
    } elseif(array_key_exists('userid',$_GET)) {
        // This route is if there is no userid
        $userid = $_GET['userid'];

        if (empty($userid) || $userid == $returned_userid) {
            try{
                $query = $readDB->prepare('select id, FirstName, Username, Email, UserActive, AccountType, LoginAttempts, Organization, Website, Tag1, Tag2, Tag3, Pexels, SMParams, SMPosts from tblusers where id = :userid');
                $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
                $query->execute();

                $rowCount = $query->rowCount();

                if($rowCount === 0) {
                    $response = new Response();
                    $response->setHttpStatusCode(404);
                    $response->setSuccess(false);
                    $response->addMessage('User not found');
                    $response->send();
                    exit;
                }

                while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    $user = new User($row['id'], $row['FirstName'], $row['Username'], $row['Email'], $row['UserActive'], $row['AccountType'], $row['LoginAttempts'], $row['Organization'], $row['Website'], $row['Tag1'], $row['Tag2'], $row['Tag3'], $row['Pexels'], $row['SMParams'], $row['SMPosts']);
                    $userArray[] = $user->returnUserAsArray();
                }

                $returnData = array();
                $returnData['user'] = $userArray[0];

                $response = new Response();
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->addMessage('User retrieved');
                // $response->toCache(true);
                $response->setData($returnData);
                $response->send();
                exit;
            }
            catch(UserException $ex){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage($ex->getMessage());
                $response->send();
                exit;
            }
            catch(PDOException $ex){
                error_log('Database query error - '.$ex, 0);
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('Failed to get User');
                $response->send();
                exit();
            }
        }

    }
}

?>