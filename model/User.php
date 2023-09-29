<?php 

class UserException extends Exception { }

class User {

    private $_id;
    private $_firstname;
    private $_username;
    private $_email;
    private $_useractive;
    private $_accounttype;
    private $_loginattempts;

    public function __construct($id, $firstname, $username, $email, $useractive, $accounttype, $loginattempts) {
        $this->setID($id);
        $this->setFirstname($firstname);
        $this->setUsername($username);
        $this->setEmail($email);
        $this->setUseractive($useractive);
        $this->setAccounttype($accounttype);
        $this->setLoginattempts($loginattempts);
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

    public function getUseractive() {
        return $this->_useractive;
    }

    public function getAccounttype() {
        return $this->_accounttype;
    }

    public function getLoginattempts() {
        return $this->_loginattempts;
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

    public function setUseractive($useractive) {
        if(strtoupper($useractive) !== 'Y' && strtoupper($useractive) !== 'N') {
            throw new UserException('User useractive date time error');
        }
        $this->_useractive = $useractive;
    }

    public function setAccounttype($accounttype) {
        if(strtolower($accounttype) !== 'administrator' && strtolower($accounttype) !== 'user') {
            throw new UserException('User account type error');
        }
        $this->_accounttype = $accounttype;
    }

    public function setLoginattempts($loginattempts) {
        if(($loginattempts !== null ) && (!is_numeric($loginattempts) || $loginattempts <= -1 || $loginattempts > 100)){
            throw new UserException('User loginattempts error');
        }
        $this->_loginattempts = $loginattempts;
    }

    public function returnUserAsArray() {
        $user = array();
        $user['id'] = $this->getID();
        $user['FirstName'] = $this->getFirstname();
        $user['Username'] = $this->getUsername();
        $user['Email'] = $this->getEmail();
        $user['UserActive'] = $this->getUseractive();
        $user['AccountType'] = $this->getAccounttype();
        $user['LoginAttempts'] = $this->getLoginattempts();
        return $user;
    }

}


?>