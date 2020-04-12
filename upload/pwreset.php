<?php
/*
	File:		pwreset.php
	Created: 	4/5/2016 at 12:23AM Eastern Time
	Info: 		Allows players to reset their password if they have
				forgotten it. Please fill in the $from field below.
	Author:		TheMasterGeneral
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
*/
require('globals_nonauth.php');
$from = $set['sending_email'];
if (!isset($_GET['step'])) {
    $_GET['step'] = '';
}
switch ($_GET['step']) {
    case 'two':
        two();
        break;
    default:
        one();
        break;
}
function one()
{
    global $db, $from, $set, $api;
    if (isset($_POST['email'])) {
        if (!isset($_POST['email']) || !valid_email(stripslashes($_POST['email']))) {
            alert('danger', "Uh Oh!", "You input an invalid email address.", false);
            require("footer.php");
            exit;
        }
        $e_email = $db->escape(stripslashes($_POST['email']));
        $IP = $db->escape($_SERVER['REMOTE_ADDR']);
        $email = $db->fetch_single($db->query("/*qc=on*/SELECT COUNT(`userid`) FROM `users` WHERE `email` = '{$e_email}'"));
        $token = randomizer();
        if ($email > 0) {
			$username=$db->fetch_single($db->query("/*qc=on*/SELECT `username` FROM `users` WHERE `email` = '{$e_email}'"));
            $to = $e_email;
            $subject = "{$set['WebsiteName']} Password Recovery";
			$body = "Greetings {$username}!<br />
			It appears that at around " . date('l, F j, Y g:i:s a') . " Chivalry is Dead time, a 
			request was made to reset your in-game password. Before we do, we need to make sure its
			you who made the request. If it is, great, click 
			<a href='http://" . determine_game_urlbase() . "/pwreset.php?step=two&code={$token}'>here</a> to 
			get the password reset process started.<br />
			If it was not you, please log into the game and change your password immediately, as your 
			account may be compromised.<br />
			<br />
			If you cannot click the link: (http://" . determine_game_urlbase() . "/pwreset.php?step=two&code={$token})<br />
			<br />
			The password reset link will be valid for 30 minutes.";
            $api->SystemSendEmail($to, $body, $subject, $from);
            $expire = time() + 1800;
            $db->query("UPDATE `users` SET `force_logout` = 'true' WHERE `email` = '{$e_email}'");
            $db->query("INSERT INTO `pw_recovery` (`pwr_ip`, `pwr_email`, `pwr_code`, `pwr_expire`) VALUES ('{$IP}', '{$e_email}', '{$token}', '{$expire}')");
        }
        alert('success', "Success!", "If there is an account associated to the email address you input, you will be
		    emailed with steps on how to start the password reset process.", false);
    } else {
        alert('info', "Information!", "Please enter the email adress tied to your account so we can send information on how to reset your password. Please be sure to check your junk folder.", false);
        echo "
		<form method='post'>
			<input type='email' name='email' required='1' class='form-control'>
			<br />
			<input type='submit' class='btn btn-primary'>
		</form>";
    }
}

function two()
{
    global $db, $from, $set, $api;
    if (isset($_GET['code'])) {
        $token = $db->escape(stripslashes($_GET['code']));
        if ($db->num_rows($db->query("/*qc=on*/SELECT `pwr_id` FROM `pw_recovery` WHERE `pwr_code` = '{$token}'")) == 0) {
            alert('danger', "Uh Oh!", "Invalid token.", false);
        } else if ($db->fetch_single($db->query("/*qc=on*/SELECT `pwr_expire` FROM `pw_recovery` WHERE `pwr_code` = '{$token}'")) < time()) {
            alert('danger', "Uh Oh!", "Token has expired.", false);
        } else {
            $pwr = $db->fetch_row($db->query("/*qc=on*/SELECT * FROM `pw_recovery` WHERE `pwr_code` = '{$token}'"));
            $pw = substr(randomizer(), 0, 16);
            $to = $pwr['pwr_email'];
            $subject = "{$set['WebsiteName']} Password Recovery";
            $body = "Your password has been successfully updated to {$pw}
			<br /> Please use this to log in from now on. We highly recommend changing your password as soon as you log in.";
            $api->SystemSendEmail($to, $body, $subject, $from);
            $db->query("UPDATE `users` SET `force_logout` = 'true' WHERE `email` = '{$pwr['pwr_email']}'");
            $e_pw = encode_password($pw);
            $db->query("UPDATE `users` SET `password` = '{$e_pw}' WHERE `email` = '{$pwr['pwr_email']}'");
			$newuserid=$db->fetch_single($db->query("/*qc=on*/SELECT `userid` FROM `users` WHERE `email` = '{$to}'"));
			$randomizer=randomizer();
            $db->query("UPDATE `user_settings` SET `security_key` = '{$randomizer}' WHERE `userid` = {$newuserid}");
            $db->query("DELETE FROM `pw_recovery` WHERE `pwr_code` = '{$token}'");
            alert('success', "Success!", "Your new password has been emailed to you. If you were previously logged in,
			    your session has been terminated.", false);
        }
    } else {
        alert('danger', "Uh Oh!", "Please specify a recovery token.", false);
    }
}

$h->endpage();