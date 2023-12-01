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

    $query = $readDB->prepare('SELECT JSON_UNQUOTE(JSON_EXTRACT(SearchedPhotos, "$.'.$keyword.'")) FROM tblusers WHERE id = :userid');
    $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
    $query->execute();

    $rowCount = $query->fetch(PDO::FETCH_NUM)[0];

    $randomPage = 1;

    if ($rowCount){
        $prevImgSrchResults = json_decode(htmlspecialchars_decode(str_replace('u0026', '&', $rowCount)));
        $prevImgSrchPage = $prevImgSrchResults->page;
        $prevImgSrchTotalResults = $prevImgSrchResults->total_results;
        $prevImgSrchTotalPages = floor($prevImgSrchTotalResults / 80) != 0 ? floor($prevImgSrchTotalResults / 80) : 1;
        if ($prevImgSrchTotalPages > 1){
            do {   
                $randomPage = $page = rand(1,$prevImgSrchTotalPages);
            } while(in_array($randomPage, array($prevImgSrchPage, null)));  
        } 
    }

    $ch = curl_init();
    $URLRequest = "https://api.pexels.com/v1/search?query=" . $searchKeyword . "&page=" . $randomPage . "&per_page=80";
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.$pexelsKey.''));
    curl_setopt($ch, CURLOPT_URL,$URLRequest);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $CURLresponse = curl_exec($ch);
    curl_close ($ch);


    $CURLResponseNoQuotes = str_replace(['\"', '\'', '\\'], '', $CURLresponse); // any " (double quotes) or (') single quotes in the response can cause the data to be improperly saved to the database
    $CURLResponseNoEmojisQuotes = preg_replace('/[[:^print:]]/', '',$CURLResponseNoQuotes); // this cleans from emojis
    $PexelsResponse = json_decode($CURLResponseNoEmojisQuotes);

    $query = $readDB->prepare('UPDATE tblusers SET MostRecentSearch = "'.$keyword.'" WHERE id = :userid');
    $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
    $query->execute();

    $query = $readDB->prepare('SELECT JSON_CONTAINS_PATH(SearchedPhotos, "all", "$.'.$keyword.'") FROM tblusers WHERE id = :userid');
    $query->bindParam(':userid', $loggedin_userid, PDO::PARAM_INT);
    $query->execute();

    $rowCount = $query->fetch(PDO::FETCH_NUM)[0];

        if ($PexelsResponse->total_results > 0) {                
            $searchedPhotos = htmlspecialchars(trim($CURLResponseNoEmojisQuotes));
    
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


    $returnData = array();
    $returnData['keyword'] = $keyword;
    $returnData['search_keyword'] = $searchKeyword;
    $returnData['pexelsKey'] = $pexelsKey;
    $returnData['URL_Request'] = $URLRequest;
    $returnData['Pexels_Response'] = json_decode($CURLresponse);
    // $returnData['row_count'] = $rowCount;
    $returnData['prev_image_search_total_results'] = $prevImgSrchTotalResults;
    $returnData['prev_image_search_total_pages'] = $prevImgSrchTotalPages;
    $returnData['previous_page'] = $prevImgSrchPage;
    $returnData['random_Page'] = $randomPage;


    $response = new Response();
    $response->setHttpStatusCode(201);
    $response->setSuccess(true);
    $response->addMessage('Found photos');
    $response->setData($returnData);
    $response->send();
    exit;


}

?>