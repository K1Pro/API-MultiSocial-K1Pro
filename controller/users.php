<?php

require_once('../../../../login/v001/public/controller/components/logindb.php');
require_once('db.php');
require_once('../../../../login/v001/public/model/ValidLogin.php');
require_once('../model/User.php');
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

if($_SERVER['REQUEST_METHOD'] !== 'OPTIONS' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'DELETE'){
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

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Creating users will be done in the centralized login API
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if(empty($_GET)){
        try{
            $query = $readDB->prepare('SELECT id, FirstName, Username, Email, AppUserActive, AppAccountType, Organization, Website, Tag1, Tag2, Tag3, Pexels, SMParams, SMPosts, GeneratedText, SearchedPhotos 
                                        from tblusers');
            $query->execute();

            $rowCount = $query->rowCount();

            $userArray = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $user = new User($row['id'], $row['FirstName'], $row['Username'], $row['Email'], $row['AppUserActive'], $row['AppAccountType'], $row['Organization'], $row['Website'], $row['Tag1'], $row['Tag2'], $row['Tag3'], $row['Pexels'], $row['SMParams'], $row['SMPosts'], $row['GeneratedText'], $row['SearchedPhotos']);
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

        if (empty($userid) || $userid == $loggedin_userid) {
            try{
                // vvvvvvvvv  Verifying the user between the login DB and app DB  vvvvvvvvvvvvv
                $query = $loginDB->prepare('SELECT id, FirstName, Username, Email, Organization, AppPermissions 
                                            FROM tblusers 
                                            WHERE id = :userid');
                $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
                $query->execute();

                $rowCount = $query->rowCount();                

                if($rowCount === 0) {
                    $response = new Response();
                    $response->setHttpStatusCode(404);
                    $response->setSuccess(false);
                    $response->addMessage('Logged in user not found');
                    $response->send();
                    exit;
                }

                while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    $validlogin = new ValidLogin($row['id'], $row['FirstName'], $row['Username'], $row['Email'], $row['Organization'], $row['AppPermissions']);
                    $validloginArray[] = $validlogin->returnValidLoginAsArray();
                }

                $query = $readDB->prepare('SELECT id, FirstName, Username, Email, AppUserActive, AppAccountType, Organization, Website, Tag1, Tag2, Tag3, Pexels, SMParams, SMPosts, GeneratedText, SearchedPhotos
                                            FROM tblusers 
                                            WHERE id = :userid AND FirstName = :firstname AND Username = :username AND Email = :email AND Organization = :organization');
                $query->bindParam(':userid', $validloginArray[0]['id'], PDO::PARAM_INT);
                $query->bindParam(':firstname', $validloginArray[0]['FirstName'], PDO::PARAM_STR);
                $query->bindParam(':firstname', $validloginArray[0]['FirstName'], PDO::PARAM_STR);
                $query->bindParam(':username', $validloginArray[0]['Username'], PDO::PARAM_STR);
                $query->bindParam(':email', $validloginArray[0]['Email'], PDO::PARAM_STR);
                $query->bindParam(':organization', $validloginArray[0]['Organization'], PDO::PARAM_STR);
                $query->execute();

                // ^^^^^^^^^^^  Verifying the user between the login DB and app DB  ^^^^^^^^^^^^^^^^^^

                $rowCount = $query->rowCount();

                if($rowCount === 0) {
                    $response = new Response();
                    $response->setHttpStatusCode(404);
                    $response->setSuccess(false);
                    $response->addMessage('App user not found');
                    $response->send();
                    exit;
                }

                while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    $user = new User($row['id'], $row['FirstName'], $row['Username'], $row['Email'], $row['AppUserActive'], $row['AppAccountType'], $row['Organization'], $row['Website'], $row['Tag1'], $row['Tag2'], $row['Tag3'], $row['Pexels'], $row['SMParams'], $row['SMPosts'], $row['GeneratedText'], $row['SearchedPhotos']);
                    $userArray[] = $user->returnUserAsArray();
                }

                if (!in_array("RapidMarketingAI", $validloginArray[0]['AppPermissions'])) {
                    $response = new Response();
                    $response->setHttpStatusCode(404);
                    $response->setSuccess(false);
                    $response->addMessage('User is not authorized to use this app');
                    $response->send();
                    exit;
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

            } catch(ValidLoginException $ex){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage($ex->getMessage());
                $response->send();
                exit;

            } catch(UserException $ex){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage($ex->getMessage());
                $response->send();
                exit;
                
            } catch(PDOException $ex){
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