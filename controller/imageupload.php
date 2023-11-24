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
    try{
        // $active = 'true';
        // $query = $readDB->prepare('select * from tblposts where organization = :organization ORDER BY date DESC');
        // $query->bindParam(':organization', $returned_organization, PDO::PARAM_STR);
        // $query->execute();

        // $rowCount = $query->rowCount();

        // if($rowCount === 0) {
        //     $response = new Response();
        //     $response->setHttpStatusCode(404);
        //     $response->setSuccess(false);
        //     $response->addMessage('No posts found');
        //     $response->send();
        //     exit;
        // }

        // $posted = $query->fetchAll(PDO::FETCH_ASSOC);

        // $returnData = array();
        // $returnData['rows_returned'] = $rowCount;
        // $returnData['posted'] = $posted;
        if(isset($_FILES['sample_image'])){
            $extension = pathinfo($_FILES['sample_image']['name'], PATHINFO_EXTENSION);
            // $new_name = time() . '.' . $extension;
            $new_name = 'upload.' . $extension;
            move_uploaded_file($_FILES['sample_image']['tmp_name'], '../images/' . $new_name);
            $data = array(
                'image_source'		=>	'images/' . $new_name
            );
            // echo json_encode($data);
        }

        // $myfile = fopen("testfile.txt", "w");

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->addMessage($_FILES['sample_image']['tmp_name']);
        $response->addMessage($data);
        $response->setData($returnData);
        $response->send();
        exit;

    }
    catch (PDOException $ex){
        error_log('Database query error -'.$ex, 0);
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage('There was an issue uploading the image');
        $response->send();
        exit;
    }

}

?>