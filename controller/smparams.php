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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_GET)) {
        try{
            $query = $readDB->prepare('select * from tblsmparams');
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Social media params not found');
                $response->send();
                exit;
            }

            $smParams = $query->fetchAll(PDO::FETCH_ASSOC);
            
            $trueParams = array();
            
            foreach ($smParams as $smParamsArray) {
                $i = 0;
                foreach ($smParamsArray as $key => $value) {
                        if ($value == 'false') {
                            unset($smParamsArray[$key]);
                        } elseif ($value == 'true') {
                            $i++;
                            $smParamsArray['param' . $i] = $key;
                            unset($smParamsArray[$key]);
                        }
                }
                unset($smParamsArray['id']);
                if ($smParamsArray['active'] == 'yes'){
                    unset($smParamsArray['active']);
                    array_push($trueParams, $smParamsArray);
                }
            }

            // $trueArray = array_column($trueParams, null, 'website'); This creates an associative array

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['sm_params'] = $trueParams;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage('Social media params found');
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
            $response->addMessage('Failed to get social media params');
            $response->send();
            exit();
        }
    }
}