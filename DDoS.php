<?php

require 'classes/Session.class.php';
$session = new Session();

$error = '';

if(!$session->issetLogin()){
    
    header("Location:index.php");
    exit();
    
}

if($_SERVER['REQUEST_METHOD'] != 'POST'){
    
    $error = 'Invalid request type.';
    
}

if(!isset($_POST['ip']) || empty($_POST['ip'])){
    $error = 'Invalid IP address.';
}

require 'classes/System.class.php';
$system = new System();

if(!$system->validate($_POST['ip'], 'ip')){
    $error = 'Invalid IP address.';
}


if($error == ''){
    
    require 'classes/Player.class.php';
    require 'classes/PC.class.php';
    require 'classes/List.class.php';
    
    $virus = new Virus();
    $player = new Player();
    $list = new Lists();
    
    $ip = ip2long($_POST['ip']);

    $playerInfo = $player->getIDByIP($ip, '');
    
    if($playerInfo['0']['existe'] == 1){

        if($list->isListed($_SESSION['id'], $ip)){

            if($virus->DDoS_count() >= 3){
                            
                $process = new Process();

                if($playerInfo['0']['pctype'] == 'VPC'){
                    $isNPC = 0;
                } else {
                    $isNPC = 1;
                }

                if($process->newProcess($_SESSION['id'], 'DDOS', $playerInfo['0']['id'], 'remote', '', '', '', $isNPC)){

                    $session->addMsg(sprintf(_('DDoS attack against <strong>%s</strong> launched.'), $_POST['ip']), 'notice');
                    header("Location:list.php?action=ddos");

                } else {

                    if (!$session->issetMsg()) {

                        $pid = $process->getPID($_SESSION['id'], 'DDOS', $playerInfo['0']['id'], 'remote', '', '', '', $isNPC);
                        header("Location:processes.php?id=".$pid);

                    } else {

                        header("Location:list.php?action=ddos");

                    }            

                }
            
            } else {
                $session->addMsg('You need to have at least 3 working DDoS viruses.', 'error');
                header("Location:list.php?action=ddos");
            }

        } else {

            $session->addMsg('This IP is not on your Hacked Database.', 'error');
            header("Location:list.php?action=ddos");

        }
    
    } else {

        $session->addMsg('This IP doesnt exists.', 'error');
        header("Location:list.php?action=ddos");
        
    }
    
} else {
    
    $session->addMsg($error, 'error');
    header("Location:list.php?action=ddos");    
    
}


?>
