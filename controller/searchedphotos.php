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

    if(strlen($jsonData->PhotoSearch) < 1){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (strlen($jsonData->PhotoSearch) < 1 ? $response->addMessage('Keyword cannot be blank') : false);
        $response->send();
        exit;
    }

    $query = $readDB->prepare('SELECT Pexels 
                                FROM tblusers 
                                WHERE id = :userid');
    $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
    $query->execute();

    $pexelsKey = $query->fetch(PDO::FETCH_ASSOC)['Pexels'];

    if ($pexelsKey == ''){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('API key for searching images cannot be blank');
        $response->send();
        exit;
    }

    $keyword = str_replace(' ', '_', htmlspecialchars(strtolower(trim($jsonData->PhotoSearch))));
    $searchKeyword = urlencode(strtolower(trim($jsonData->PhotoSearch)));
    
    $ch = curl_init();
    $URLRequest = "https://api.pexels.com/v1/search?query=" . $searchKeyword . "&page=0&per_page=80";
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.$pexelsKey.''));
    curl_setopt($ch, CURLOPT_URL,$URLRequest);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $CURLresponse = curl_exec($ch);
    curl_close ($ch);

    $CURLResponseNoQuotes = str_replace(['\"', '\''], '', $CURLresponse);
    $PexelsResponse = json_decode($CURLResponseNoQuotes);

    $query = $readDB->prepare('UPDATE tblusers SET MostRecentSearch = "'.$keyword.'" WHERE id = :userid');
    $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
    $query->execute();
    // $keywordArray = array_unique(preg_split("/[-_ ]+|(?=[A-Z])/", $keyword));

    // $DictionaryResponses = array();

    // foreach ($keywordArray as $uniqueKeyword) {
    //     if ($uniqueKeyword != "" && !in_array(strtolower($uniqueKeyword), $artsPreps)){
    //         $uniqueLowerKeyword = strtolower($uniqueKeyword);
            $query = $readDB->prepare('SELECT JSON_CONTAINS_PATH(SearchedPhotos, "all", "$.'.$keyword.'") FROM tblusers WHERE id = :userid');
            $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
            $query->execute();
        
            $rowCount = $query->fetch(PDO::FETCH_NUM)[0];
    
            if($rowCount === 0 || $rowCount === null) {
                // $ch = curl_init();
                // $URLRequest = "https://api.dictionaryapi.dev/api/v2/entries/en/".$uniqueLowerKeyword;
                // curl_setopt($ch, CURLOPT_URL,$URLRequest);
                // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                // $response = curl_exec($ch);
                // curl_close ($ch);
                // $DictionaryResponse = json_decode($response);

                if ($PexelsResponse->total_results > 0) {                
                    $searchedPhotos = htmlspecialchars(trim($CURLResponseNoQuotes));
                    // array_push($DictionaryResponses, $DictionaryResponse[0]);   
            
                    $query = $writeDB->prepare('UPDATE tblusers SET SearchedPhotos = JSON_SET(COALESCE(SearchedPhotos, "{}"), "$.'.$keyword.'", "'.$searchedPhotos.'") WHERE id = :userid');
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
            }
    //     }
    // }

    $returnData = array();
    $returnData['keyword'] = $keyword;
    $returnData['search_keyword'] = $searchKeyword;
    $returnData['pexelsKey'] = $pexelsKey;
    $returnData['URL_Request'] = $URLRequest;
    $returnData['Pexels_Response'] = $PexelsResponse;
    $returnData['row_count'] = $rowCount;
    // $returnData['generated_text_amount'] = count($DictionaryResponses);

    $response = new Response();
    $response->setHttpStatusCode(201);
    $response->setSuccess(true);
    $response->addMessage('Found photos');
    $response->setData($returnData);
    $response->send();
    exit;


}

?>