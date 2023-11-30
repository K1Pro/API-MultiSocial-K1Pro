<?php 

class UserException extends Exception { }

class User {

    private $_id;
    private $_firstname;
    private $_username;
    private $_email;
    private $_appuseractive;
    private $_appaccounttype;
    private $_organization;
    private $_posttitle;
    private $_postbody;
    private $_website;
    private $_websitedesc;
    private $_tags;
    private $_mostrecentsearch;
    private $_mostrecentphoto;
    private $_pexels;
    private $_smparams;
    private $_smposts;
    private $_generatedtext;
    private $_searchedphotos;
    private $_searchedphotosamount;


    public function __construct($id, $firstname, $username, $email, $appuseractive, $appaccounttype, $organization, $posttitle, $postbody, $website, $websitedesc, $tags, $mostrecentsearch, $mostrecentphoto, $pexels, $smparams, $smposts, $generatedtext, $searchedphotos, $searchedphotosamount) {
        $this->setID($id);
        $this->setFirstname($firstname);
        $this->setUsername($username);
        $this->setEmail($email);
        $this->setAppuseractive($appuseractive);
        $this->setAppaccounttype($appaccounttype);
        $this->setOrganization($organization);
        $this->setPosttitle($posttitle);
        $this->setPostbody($postbody);
        $this->setWebsite($website);
        $this->setWebsitedesc($websitedesc);
        $this->setTags($tags);
        $this->setMostrecentsearch($mostrecentsearch);
        $this->setMostrecentphoto($mostrecentphoto);
        $this->setPexels($pexels);
        $this->setSMParams($smparams);
        $this->setSMPosts($smposts);
        $this->setGeneratedtext($generatedtext);
        $this->setSearchedphotos($searchedphotos);
        $this->setSearchedphotosamount($searchedphotosamount);
    }

    public function getID() {
        return $this->_id;
    }

    public function getFirstname() {
        return $this->_firstname;
    }

    public function getUsername() {
        return $this->_username;
    }

    public function getEmail() {
        return $this->_email;
    }

    public function getAppuseractive() {
        return $this->_appuseractive;
    }

    public function getAppaccounttype() {
        return $this->_appaccounttype;
    }

    public function getOrganization() {
        return $this->_organization;
    }

    public function getPosttitle() {
        return $this->_posttitle;
    }

    public function getPostbody() {
        return $this->_postbody;
    }

    public function getWebsite() {
        return $this->_website;
    }

    public function getWebsitedesc() {
        return $this->_websitedesc;
    }

    public function getTags() {
        return $this->_tags;
    }

    public function getMostrecentsearch() {
        return $this->_mostrecentsearch;
    }

    public function getMostrecentphoto() {
        return $this->_mostrecentphoto;
    }

    public function getPexels() {
        return $this->_pexels;
    }

    public function getSMParams() {
        return $this->_smparams;
    }

    public function getSMPosts() {
        return $this->_smposts;
    }

    public function getGeneratedtext() {
        return $this->_generatedtext;
    }

    public function getSearchedphotos() {
        return $this->_searchedphotos;
    }

    public function getSearchedphotosamount() {
        return $this->_searchedphotosamount;
    }

    public function setID($id) {
        if(($id !== null ) && (!is_numeric($id) || $id <= 0 || $id > 9223372036854775807 || $this->_id !== null)){
            throw new UserException('User ID error');
        }
        $this->_id = $id;
    }

    public function setFirstname($firstname) {
        if (strlen($firstname) < 0 || strlen($firstname) > 255) {
            throw new UserException('User firstname error');
        }
        $this->_firstname = $firstname;
    }

    public function setUsername($username) {
        if(($username !== null) && (strlen($username) > 255)) {
            throw new UserException('User username error');
        }
        $this->_username = $username;
    }

    public function setEmail($email) {
        if(($email !== null) && (strlen($email) > 255)) {
            throw new UserException('User email error');
        }
        $this->_email = $email;
    }

    public function setAppuseractive($appuseractive) {
        if(strtoupper($appuseractive) !== 'Y' && strtoupper($appuseractive) !== 'N') {
            throw new UserException('App useractive error');
        }
        $this->_appuseractive = $appuseractive;
    }

    public function setAppaccounttype($appaccounttype) {
        if(strtolower($appaccounttype) !== 'administrator' && strtolower($appaccounttype) !== 'user') {
            throw new UserException('User account type error');
        }
        $this->_appaccounttype = $appaccounttype;
    }

    public function setOrganization($organization) {
        if (strlen($organization) < 0 || strlen($organization) > 255) {
            throw new UserException('User organization error');
        }
        $this->_organization = $organization;
    }

    public function setPosttitle($posttitle) {
        if (strlen($posttitle) < 0 || strlen($posttitle) > 510) {
            throw new UserException('User post title error');
        }
        $this->_posttitle = $posttitle;
    }

    public function setPostbody($postbody) {
        if (strlen($postbody) < 0 || strlen($postbody) > 510) {
            throw new UserException('User post body error');
        }
        $this->_postbody = $postbody;
    }

    public function setWebsite($website) {
        if (strlen($website) < 0 || strlen($website) > 510) {
            throw new UserException('User website error');
        }
        $this->_website = $website;
    }

    public function setWebsitedesc($websitedesc) {
        if (strlen($websitedesc) < 0 || strlen($websitedesc) > 510) {
            throw new UserException('User website description error');
        }
        $this->_websitedesc = $websitedesc;
    }

    public function setTags($tags) {
        if (strlen($tags) < 0 || strlen($tags) > 510) {
            throw new UserException('User tags error');
        }
        $this->_tags = $tags;
    }

    public function setMostrecentsearch($mostrecentsearch) {
        if (strlen($mostrecentsearch) < 0 || strlen($mostrecentsearch) > 510) {
            throw new UserException('User most recent search error');
        }
        $this->_mostrecentsearch = $mostrecentsearch;
    }

    public function setMostrecentphoto($mostrecentphoto) {
        if (strlen($mostrecentphoto) < 0 || strlen($mostrecentphoto) > 1020) {
            throw new UserException('Pexels most recent photo error');
        }
        $this->_mostrecentphoto = $mostrecentphoto;
    }

    public function setPexels($pexels) {
        if (strlen($pexels) < 0 || strlen($pexels) > 1000) {
            throw new UserException('Pexels API Key error');
        }
        $this->_pexels = $pexels;
    }

    public function setSMParams($smparams) {
        $this->_smparams = json_decode($smparams);
    }

    public function setSMPosts($smposts) {
        $this->_smposts = json_decode($smposts);
    }
    
    public function setGeneratedtext($generatedtext) {
        $decodedGeneratedtext = json_decode($generatedtext);
        $decodedGeneratedtext_noSpecialChars = [];
        foreach ($decodedGeneratedtext as $key => $value) {
            // str_replace of u0026 is needed because it appears that & (ampersand) is mutated and not properly returned
            $decodedGeneratedtext_noSpecialChars[$key] = json_decode(htmlspecialchars_decode(str_replace('u0026', '&', $value)));
        }
        $this->_generatedtext = $decodedGeneratedtext_noSpecialChars;
    }

    public function setSearchedphotos($searchedphotos) {
        $decodedSearchedphotos = json_decode($searchedphotos);
        $decodedSearchedphotos_noSpecialChars = [];
        foreach ($decodedSearchedphotos as $key => $value) {
            // str_replace of u0026 is needed because it appears that & (ampersand) is mutated and not properly returned
            $decodedSearchedphotos_noSpecialChars[$key] = json_decode(htmlspecialchars_decode(str_replace('u0026', '&', $value)));
        }
        $this->_searchedphotos = $decodedSearchedphotos_noSpecialChars;
    }

    public function setSearchedphotosamount($searchedphotosamount) {
        $this->_searchedphotosamount = json_decode($searchedphotosamount);
    }

    public function returnUserAsArray() {
        $user = array();
        $user['id'] = $this->getID();
        $user['FirstName'] = $this->getFirstname();
        $user['Username'] = $this->getUsername();
        $user['Email'] = $this->getEmail();
        $user['AppUserActive'] = $this->getAppuseractive();
        $user['AppAccountType'] = $this->getAppaccounttype();
        $user['Organization'] = $this->getOrganization();
        $user['PostTitle'] = $this->getPosttitle();
        $user['PostBody'] = $this->getPostbody();
        $user['Website'] = $this->getWebsite();
        $user['WebsiteDesc'] = $this->getWebsitedesc();
        $user['Tags'] = $this->getTags();
        $user['MostRecentSearch'] = $this->getMostrecentsearch();
        $user['MostRecentPhoto'] = $this->getMostrecentphoto();
        $user['Pexels'] = $this->getPexels();
        $user['SMParams'] = $this->getSMParams();
        $user['SMPosts'] = $this->getSMPosts();
        $user['GeneratedText'] = $this->getGeneratedtext();
        $user['SearchedPhotos'] = $this->getSearchedphotos();
        $user['SearchedPhotosAmount'] = $this->getSearchedphotosamount();

        return $user;
    }

}


?>