<?php

require 'config.php';
require 'classes/Session.class.php';
require 'classes/System.class.php';


$session = new Session();
$loadFacebook = FALSE;
$loadTwitter = FALSE;
$remembered = TRUE;

require 'classes/Facebook.class.php';

if(isset($_COOKIE['PHPSESSID'])){
    $session = new Session();
}

if(!isset($_SESSION['id'])){
    
    if(isset($_GET['nologin'])){
        $_SESSION = NULL;
        session_destroy();
        header("Location:index.php");
        exit();
    }
    
    if($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['code']) || isset($_SESSION['FBLOGIN'])){
                
        $fbServerURL = 'http://localhost:3000/';

        if(isset($_SERVER['HTTP_HOST'])){
            if($_SERVER['HTTP_HOST'] == 'localhost:3000'){
                $fbServerURL = 'http://localhost:3000/';
            } elseif($_SERVER['HTTP_HOST'] == 'hackanoob.net'){
                $fbServerURL = 'http://hackanoob.net/';
            }
        }
        
        // 2019: Update the links, the appId and the appSecret below in order to enable FB login
        switch($fbServerURL){
            case 'http://localhost:3000/':
                $appID = 0;
                $appSecret = 'REDACTED';
                break;
            case 'http://hackanoob.net/':
                $appID = 0;
                $appSecret = 'REDACTED';
                break;
            case 'http://hackanoob.net/':
                $appID = 0;
                $appSecret = 'REDACTED';
                break;
        }
                
        $facebook = new Facebook(array(
            'appId' => $appID,
            'secret' => $appSecret,
            'cookie' => true
        ));
        
        $user = $facebook->getUser();
                
        

        if($user){

            try {
                $userInfo = $facebook->api('/me');
            } catch (FacebookApiException $e) {
                echo $e;
                error_log($e);
                $user = NULL;
            }

        }

        $pdo = PDO_DB::factory();

        if($user){

            $sql = 'SELECT COUNT(*) AS total, users.login
                    FROM users_facebook 
                    LEFT JOIN users
                    ON users.id = users_facebook.gameID
                    WHERE userID = '.$user;
            $fbInfo = $pdo->query($sql)->fetch(PDO::FETCH_OBJ);
            
            if($fbInfo->total == 0){
                //first login. need to determine one username and then use the usercreate function

                $loadFacebook = TRUE;
                $_SESSION['SPECIAL_ID'] = 'fb';
            } else {

                require 'classes/Database.class.php';
                $database = new LRSys();
                
                $database->login($fbInfo->login, '0', 'facebook');

                header("Location:index.php");

            }

        }

    } elseif((isset($_GET['oauth_verifier']) && !isset($_SESSION['twitter_data'])) || isset($_SESSION['TTLOGIN'])){
                
        if(!isset($_SESSION['TTLOGIN'])){

            $error = FALSE;
            
            if(!isset($_SESSION['oauth_token']) || !isset($_SESSION['oauth_token_secret'])){
                $error = TRUE;
                $msg = 'Invalid authentication token. Please, try again.';
            }

            if($error){
                $_SESSION = NULL;
                session_destroy();
                session_start();
                $_SESSION['MSG'] = $msg;
                $_SESSION['MSG_TYPE'] = 'error';
                $_SESSION['TYP'] = 'home';
                header("Location:index.php");
                exit();
            }

            require 'twitter/twitteroauth.php';

            // Modify `REDACTED` to enable twitter login
            $twitteroauth = new TwitterOAuth('REDACTED', 'REDACTED', $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
            $twitteroauth->host = "https://api.twitter.com/1.1/";

            $access_token = $twitteroauth->getAccessToken($_GET['oauth_verifier']);
            $_SESSION['access_token'] = $access_token;
            $user_info = $twitteroauth->get('account/verify_credentials');
            
            if(array_key_exists('errors', $user_info)){
                $_SESSION = NULL;
                session_destroy();
                session_start();
                $_SESSION['MSG'] = $user_info->errors[0]->message;
                $_SESSION['MSG_TYPE'] = 'error';
                $_SESSION['TYP'] = 'home';
                header("Location:index.php");
                exit();
            }

            $_SESSION['twitter_data'] = $user_info;

        } else {
            $user_info = $_SESSION['twitter_data'];
            unset($_SESSION['twitter_data']);
        }
        
        $pdo = PDO_DB::factory();
        
        $_SESSION['QUERY_COUNT'] += 1;
        $sql = 'SELECT COUNT(*) AS total, users.login
                FROM users_twitter 
                LEFT JOIN users
                ON users.id = users_twitter.gameID
                WHERE userID = '.$user_info->id;
        $ttInfo = $pdo->query($sql)->fetch(PDO::FETCH_OBJ);
        
        if($ttInfo->total == 0){
            //first login. need to determine one username and then use the usercreate function

            $loadTwitter = TRUE;
            $_SESSION['SPECIAL_ID'] = 'tt';
        } else {
            
            require 'classes/Database.class.php';
            $database = new LRSys();

            $database->login($ttInfo->login, '0', 'twitter');

            header("Location:index.php");

        }
    
    } elseif(isset($_SESSION['twitter_data'])){
        $loadTwitter = TRUE;
    } else {
        
        // 2019: User is not doing any type of social login, so I'll check if it's logged in the database (remember me feature)
        require 'classes/RememberMe.class.php';
        
        //md5 hash = hackanoobrulez
        $key = pack("H*", '28b146835c923b91a78a03dd9bf16a77');
        
        $remember = new RememberMe($key, PDO_DB::factory());
        
        $remember->rememberlogin();
        
        $remembered = TRUE;

    }
    
}

if($loadFacebook){
        
    require 'template/fbtpl.php';
    
} elseif($loadTwitter){

    require 'template/tttpl.php';
    
} elseif (isset($_SESSION['id'])) {
    
    $session = new Session();
    
    if(!$remembered) require_once 'classes/Player.class.php';
    $player = new Player($_SESSION['id']);

    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST)){

        $player->handlePost();

    }    

    require 'template/contentStart.php';

    if($session->issetMsg()){
        $session->returnMsg();
    }
    
    if($_SESSION['ROUND_STATUS'] == 1){

        $player->showIndex();

    } else {

        $player->showGameOver();

    }

    require 'template/contentEnd.php';

} else {
    
    if(isset($_SESSION)) unset($_SESSION['GOING_ON']);
    
    if(isset($_SESSION['SPECIAL_ID'])){
        if($_SESSION['SPECIAL_ID'] == 'fb'){
            require 'template/fbtpl.php';
        } else {
            require 'template/tttpl.php';
        }
    } else {
        require 'template/default.php';
    }

}
