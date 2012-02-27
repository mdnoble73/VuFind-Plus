<?php
require_once 'Authentication.php';
require_once 'sys/Administration/Administrator.php';

class AdminDBAuthentication implements Authentication {

    public function authenticate() {
        $login = $_REQUEST['login'];
        $password = md5($_REQUEST['password']);
        if (($login == '') || ($password == '')) {
            $user = new PEAR_Error('authentication_error_blank');
        } else {
            $user = new Administrator();
            $user->whereAdd("login = '$login'");
            $user->whereAdd("password = '$password'");
            $user->find();
            if ($user->N != 1) {
                $user = new PEAR_Error('authentication_error_invalid');
            }else{
                $user->fetch();
            }
        }
        return $user;
    }
}