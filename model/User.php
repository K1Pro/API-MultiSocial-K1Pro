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
    private $_website;
    private $_tag1;
    private $_tag2;
    private $_tag3;
    private $_pexels;
    private $_smparams;
    private $_smposts;


    public function __construct($id, $firstname, $username, $email, $appuseractive, $appaccounttype, $organization, $website, $tag1, $tag2, $tag3, $pexels, $smparams, $smposts) {
        $this->setID($id);
        $this->setFirstname($firstname);
        $this->setUsername($username);
        $this->setEmail($email);
        $this->setAppuseractive($appuseractive);
        $this->setAppaccounttype($appaccounttype);
        $this->setOrganization($organization);
        $this->setWebsite($website);
        $this->setTag1($tag1);
        $this->setTag2($tag2);
        $this->setTag3($tag3);
        $this->setPexels($pexels);
        $this->setSMParams($smparams);
        $this->setSMPosts($smposts);
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

    public function getWebsite() {
        return $this->_website;
    }

    public function getTag1() {
        return $this->_tag1;
    }

    public function getTag2() {
        return $this->_tag2;
    }

    public function getTag3() {
        return $this->_tag3;
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

    public function setWebsite($website) {
        if (strlen($website) < 0 || strlen($website) > 255) {
            throw new UserException('User website error');
        }
        $this->_website = $website;
    }

    public function setTag1($tag1) {
        if (strlen($tag1) < 0 || strlen($tag1) > 255) {
            throw new UserException('User tag1 error');
        }
        $this->_tag1 = $tag1;
    }

    public function setTag2($tag2) {
        if (strlen($tag2) < 0 || strlen($tag2) > 255) {
            throw new UserException('User tag2 error');
        }
        $this->_tag2 = $tag2;
    }

    public function setTag3($tag3) {
        if (strlen($tag3) < 0 || strlen($tag3) > 255) {
            throw new UserException('User tag3 error');
        }
        $this->_tag3 = $tag3;
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

    public function returnUserAsArray() {
        $user = array();
        $user['id'] = $this->getID();
        $user['FirstName'] = $this->getFirstname();
        $user['Username'] = $this->getUsername();
        $user['Email'] = $this->getEmail();
        $user['AppUserActive'] = $this->getAppuseractive();
        $user['AppAccountType'] = $this->getAppaccounttype();
        $user['Organization'] = $this->getOrganization();
        $user['Website'] = $this->getWebsite();
        $user['Tag1'] = $this->getTag1();
        $user['Tag2'] = $this->getTag2();
        $user['Tag3'] = $this->getTag3();
        $user['Pexels'] = $this->getPexels();
        $user['SMParams'] = $this->getSMParams();
        $user['SMPosts'] = $this->getSMPosts();

        return $user;
    }

}


?>