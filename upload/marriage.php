<?php
/*
	File: marriage.php
	Created: 5/11/2017 at 3:42PM Eastern Time.
	Author: TheMasterGeneral
	Website: https://github.com/MasterGeneral156/
	Info: Allows players to propose and marry others.
	Couples can send love letters to each other, and move
	into another's house if their happiness is high enough.
	To increase happiness, couples may "sleep" together.
	(I PROMISE ITS JUST SLEEP. YOU KNOW... SLEEP)
	Copyright: Copyright (C) 2017 TheMasterGeneral
	License: http://www.dbad-license.org/
*/
require('globals.php');
echo "<h3><i class='game-icon game-icon-lovers'></i> Marriage Center</h3><hr />";
if (!isset($_GET['action']))
{
    $_GET['action'] = '';
}
$mi=$db->query("SELECT * FROM `marriage_tmg` WHERE (`proposer_id` = {$userid} OR `proposed_id` = {$userid}) AND `together` = 1");
$po=$db->query("SELECT * FROM `marriage_tmg` WHERE (`proposer_id` = {$userid} OR `proposed_id` = {$userid}) AND `together` = 0");
if ($db->num_rows($mi) == 0)
{
	switch ($_GET['action'])
	{
		default:
			home_unwed();
			break;
	}
}
else
{
	switch ($_GET['action'])
	{
		case "argue":
			argue();
			break;
		case "sleep":
			slept();
			break;
		case "sendlove":
			letter();
			break;
		case "divorce":
			divorce();
			break;
		default:
			home_wed();
			break;
	}
}
function home_unwed()
{
	global $db,$ir,$h,$mi,$userid,$po,$api;
	$proposed=$db->query("SELECT * FROM `marriage_tmg` WHERE `proposed_id` = {$userid} AND `together` = 0");
	$p=$db->fetch_row($po);
	//If player is unwed, and has no proposals inbound.
	if ($db->num_rows($po) == 0)
	{
		if (isset($_POST['user']))
		{
			if (!isset($_POST['verf']) || !verify_csrf_code('marriage_propose', stripslashes($_POST['verf'])))
			{
				alert('danger',"Uh Oh!","Your request has been blocked for your security. Please propose quicker next time.");
				die($h->endpage());
			}
			$user=(isset($_POST['user']) && is_numeric($_POST['user'])) ? abs(intval($_POST['user'])) : 0;
			if (empty($user))
			{
				alert('danger',"Uh Oh!","Invalid input.");
				die($h->endpage());
			}
			if ($user == $userid)
			{
				alert('danger',"Uh Oh!","You cannot be so lonely that you would want to marry yourself, right?");
				die($h->endpage());
			}
			$q=$db->query("SELECT `user_level` FROM `users` WHERE `userid` = {$user}");
			if ($db->num_rows($q) == 0)
			{
				alert('danger',"Uh Oh!","User is invalid or does not exist.");
				die($h->endpage());
			}
			if ($db->num_rows($mi) > 0)
			{
				alert('danger',"Uh Oh!","You cannot marry more than one person at a time.");
				die($h->endpage());
			}
			$my=$db->query("SELECT * FROM `marriage_tmg` WHERE (`proposer_id` = {$user} OR `proposed_id` = {$user}) AND `together` = true");
			if ($db->num_rows($my) > 0)
			{
				alert('danger',"Uh Oh!","You cannot propose to another player while they're married.");
				die($h->endpage());
			}
			$api->GameAddNotification($user,"{$ir['username']} has proposed to marry you. You may accept or decline by clicking <a href='marriage.php'>here</a>.");
			$db->query("INSERT INTO `marriage_tmg` (`proposer_id`, `proposed_id`, `together`, `happiness`) VALUES ('{$userid}', '{$user}', '0', '0')");
			alert('success',"Success","Your proposal has been sent. Best of luck.",true,'explore.php');
		}
		else
		{
			$csrf=request_csrf_html('marriage_propose');
			echo "Welcome to the marriage center {$ir['username']}. Do you wish to propose to someone today?<br />
			<form method='post'>
				" . user_dropdown('user',$userid) . "
				{$csrf}
				<input type='submit' class='btn btn-primary' value='Propose!'>
			</form>";
		}
	}
	//If player has a proposal inbound.
	elseif ($db->num_rows($proposed) > 0)
	{
		$un=$db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$p['proposer_id']}"));
		if (isset($_POST['action']))
		{
			if (!isset($_POST['verf']) || !verify_csrf_code('marriage_proposed', stripslashes($_POST['verf'])))
			{
				alert('danger',"Uh Oh!","Your action has been blocked for your security. Try filling out the form quicker next time.");
				die($h->endpage());
			}
			$proid=$db->query("SELECT `marriage_id` FROM `marriage_tmg` WHERE (`proposer_id` = {$p['proposer_id']} AND `proposed_id` = {$userid}) AND `together` = 0");
			$proidq=$db->fetch_single($proid);
			if ($db->num_rows($proid) == 0)
			{
				alert('danger',"Uh Oh!","Invalid or non-existent marriage proposal.");
				die($h->endpage());
			}
			if ($_POST['action'] == 'decline')
			{
				alert('success',"Success!","You have successfully declined this marriage proposal.",true,'explore.php');
				$api->GameAddNotification($p['proposer_id'],"{$ir['username']} has declined your marriage proposal.");
				$db->query("DELETE FROM `marriage_tmg` WHERE `marriage_id` = {$proidq}");
				die($h->endpage());
			}
			elseif ($_POST['action'] == 'accept')
			{
				$already_married=$db->query("SELECT `marriage_id` FROM `marriage_tmg` WHERE (`proposer_id` = {$p['proposer_id']} AND `proposed_id` = {$p['proposer_id']}) AND `together` = 1");
				if ($db->num_rows($already_married) > 0)
				{
					alert('danger',"Uh Oh!","You cannot accept this proposal as the sender has already gotten married. We're going to remove this proposal for you.");
					$db->query("DELETE FROM `marriage_tmg` WHERE `marriage_id` = {$proidq}");
					die($h->endpage());
				}
				alert('success',"Success!","You have successfully accepted this marriage proposal.",true,'explore.php');
				$api->GameAddNotification($p['proposer_id'],"{$ir['username']} has accepted your marriage proposal! Congratulations!");
				$db->query("UPDATE `marriage_tmg` SET `together` = 1 WHERE `marriage_id` = {$proidq}");
				die($h->endpage());
			}
			else
			{
				alert('danger',"Uh Oh!","Invalid action specified. Check your source and try again.");
				die($h->endpage());
			}
		}
		else
		{
			$csrf=request_csrf_html('marriage_proposed');
			echo "You currently have a proposal from {$un}. Do you wish to accept or decline this?<br />
			<form method='post'>
				{$csrf}
				<input type='hidden' name='action' value='decline'>
				<input type='submit' class='btn btn-danger' value='Decline'>
			</form>
			<form method='post'>
				{$csrf}
				<input type='hidden' name='action' value='accept'>
				<input type='submit' class='btn btn-success' value='Accept'>
			</form>";
		}
	}
	//If player has proposal outbound.
	else
	{
		$un=$db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$p['proposed_id']}"));
		if (isset($_POST['divorce']))
		{
			if (!isset($_POST['verf']) || !verify_csrf_code('marriage_cancel', stripslashes($_POST['verf'])))
			{
				alert('danger',"Uh Oh!","Your request has been blocked for your security. Try to be quicker next time.");
				die($h->endpage());
			}
			alert('success',"Success!","You have successfully withdrawn your proposal. Better luck next time.");
			$api->GameAddNotification($p['proposed_id'],"{$ir['username']} has withdrawn their proposal.");
			$db->query("DELETE FROM `marriage_tmg` WHERE `marriage_id` = {$p['marriage_id']}");
			die($h->endpage());
		}
		else
		{
			$csrf=request_csrf_html('marriage_cancel');
			echo "You currently have a proposal sent out to {$un}. Do you wish to cancel it?<br />
			<form method='post'>
				{$csrf}
				<input type='hidden' name='divorce' value='do'>
				<input type='submit' class='btn btn-danger' value='Withdraw Proposal'>
			</form>";
		}
	}
}
function home_wed()
{
	global $db,$ir,$userid,$h,$mi,$api;
	$mt=$db->fetch_row($mi);
	if ($mt['proposer_id'] == $userid)
	{
		$un=$db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$mt['proposed_id']}"));
		$title1=$ir['username'];
		$title2=$un;
		$p1=$ir;
		$p2=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposed_id']}"));
	}
	else
	{
		$un=$db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$mt['proposer_id']}"));
		$title1=$un;
		$title2=$ir['username'];
		$p1=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposer_id']}"));
		$p2=$ir;
	}
	$p1['estate'] = $db->fetch_single($db->query("SELECT `house_name` FROM `estates` WHERE `house_will` = {$p1['maxwill']}"));
	$p2['estate'] = $db->fetch_single($db->query("SELECT `house_name` FROM `estates` WHERE `house_will` = {$p2['maxwill']}"));
	$p1['bank'] = ($p1['bank'] == -1) ? 'Unpurchased account' : number_format($p1['bank']);
	$p2['bank'] = ($p2['bank'] == -1) ? 'Unpurchased account' : number_format($p2['bank']);
	if ($mt['happiness'] == 0)
		$mt['happiness']=$mt['happiness'];
	if ($mt['happiness'] < 0)
		$mt['happiness']="<span class='text-danger'>{$mt['happiness']}</span>";
	if ($mt['happiness'] > 0)
		$mt['happiness']="<span class='text-success'>{$mt['happiness']}</span>";
	echo "
	<table class='table table-bordered'>
	<tr>
		<th>
		</th>
		<th colspan='1.5'>
			{$title1}'s Info
		</th>
		<th colspan='1.5'>
			{$title2}'s Info
		</th>
	</tr>
	<tr>
		<td>
			Copper Coins Account
		</td>
		<td align='center'>
			{$p1['bank']}
		</td>
		<td align='center'>
			{$p2['bank']}
		</td>
	</tr>
	<tr>
		<td>
			Estates
		</td>
		<td align='center'>
			{$p1['estate']}
		</td>
		<td align='center'>
			{$p2['estate']}
		</td>
	</tr>
	<tr>
		<td>
			Marriage Happiness
		</td>
		<td align='center' colspan='2'>
			{$mt['happiness']}
		</td>
	</tr>
	<tr>
		<td>
			Marriage Actions
		</td>
		<td align='center' colspan='2'>
			<a href='?action=argue'>Argue With Spouse</a> |
			<a href='?action=sleep'>Sleep With Spouse</a> |
			<a href='?action=sendlove'>Send Love Letter</a> |
			<a href='?action=divorce'>Divorce</a>
		</td>
	</tr>
	</table>";
	
}
function argue()
{
	global $db,$ir,$userid,$h,$mi,$api;
	$mt=$db->fetch_row($mi);
	if ($mt['proposer_id'] == $userid)
	{
		$p1=$ir;
		$p2=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposed_id']}"));
		$event=$p2['userid'];
	}
	else
	{
		$p1=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposer_id']}"));
		$p2=$ir;
		$event=$p1['userid'];
	}
	if ($api->UserStatus($userid, 'infirmary') || $api->UserStatus($userid, 'dungeon'))
	{
		alert('danger',"Uh Oh!","There's no point in arguing from the dungeon or infirmary.",true,'marriage.php');
		die($h->endpage());
	}
	if ($ir['energy'] < $ir['maxenergy'])
	{
		alert('danger',"Uh Oh!","You do not have the energy to argue with your spouse.",true,'marriage.php');
		die($h->endpage());
	}
	$outcome=Random(1,4);
	if ($outcome == 1)
	{
		alert('danger',"Uh Oh!","You begin to argue with your spouse. After a little while, they humiliate you. You've lost your will to continue for now. Your marriage loses one happiness point.",true,'marriage.php');
		$db->query("UPDATE `users` SET `will` = 0 WHERE `userid` = {$userid}");
		$db->query("UPDATE `marriage_tmg` SET `happiness` = `happiness` - 1 WHERE `marriage_id` = {$mt['marriage_id']}");
		$api->GameAddNotification($event,"Your spouse, {$ir['username']}, started an arguement with you and you humilated them. Your marriage lost one happiness point.");
		$api->SystemLogsAdd($userid,'marriage',"Argued with their spouse and got humilated.");
		$api->SystemLogsAdd($event,'marriage',"Argued with their spouse and humilated their spouse.");
	}
	if ($outcome == 2)
	{
		alert('success',"Success!","You begin to argue with your spouse. After a little while, you humiliate Them. They've lost their will to continue the day. Your marriage loses one happiness point.",true,'marriage.php');
		$db->query("UPDATE `users` SET `will` = 0 WHERE `userid` = {$p2['userid']}");
		$db->query("UPDATE `marriage_tmg` SET `happiness` = `happiness` - 1 WHERE `marriage_id` = {$mt['marriage_id']}");
		$api->GameAddNotification($event,"Your spouse, {$ir['username']}, started an arguement with you and you were humilated. You've lost all your will, and your marriage lost one happiness point.");
		$api->SystemLogsAdd($event,'marriage',"Argued with their spouse and got humilated.");
		$api->SystemLogsAdd($userid,'marriage',"Argued with their spouse and humilated their spouse.");
	}
	if ($outcome == 3)
	{
		$infirm=Random(10,50);
		$dung=$infirm*2;
		alert('success',"Success!","You begin to argue with your spouse. After a little while, you lose your temper and punch them in the eye. They end up having to go to the infirmary, and you need to spend some time in dungeon. Your marriage loses 5 happiness points.",true,'marriage.php');
		$api->UserStatusSet($event, 'infirmary', $infirm, "Spousal Abuse");
		$api->UserStatusSet($userid, 'dungeon', $dung, "Spousal Abuse");
		$db->query("UPDATE `marriage_tmg` SET `happiness` = `happiness` - 5 WHERE `marriage_id` = {$mt['marriage_id']}");
		$api->GameAddNotification($event,"Your spouse, {$ir['username']}, started an arguement with you and you punched you in the eye. You are resting in the infirmary, and they're resting in the dungeon. Your marriage lost 5 happiness points.");
		$api->SystemLogsAdd($event,'marriage',"Argued with their spouse and got punched.");
		$api->SystemLogsAdd($userid,'marriage',"Argued with their spouse and punched their spouse.");
	}
	if ($outcome == 4)
	{
		$infirm=Random(10,50);
		$dung=$infirm*2;
		alert('success',"Success!","You begin to argue with your spouse. After a little while, they lose their temper and punch you in the eye. You end up having to go to the infirmary, and they need to spend some time in the dungeon. Your marriage loses 5 happiness points.",true,'marriage.php');
		$api->UserStatusSet($userid, 'infirmary', $infirm, "Spousal Abuse");
		$api->UserStatusSet($event, 'dungeon', $dung, "Spousal Abuse");
		$db->query("UPDATE `marriage_tmg` SET `happiness` = `happiness` - 5 WHERE `marriage_id` = {$mt['marriage_id']}");
		$api->GameAddNotification($event,"Your spouse, {$ir['username']}, started an arguement with you and you punched them in the eye. You are resting in the dungeon, and they're resting in the infirmary. Your marriage lost one happiness point.");
		$api->SystemLogsAdd($userid,'marriage',"Argued with their spouse and got punched.");
		$api->SystemLogsAdd($event,'marriage',"Argued with their spouse and punched their spouse.");
	}
	$db->query("UPDATE `users` SET `energy` = 0 WHERE `userid` = {$userid}");
}
function slept()
{
	global $db,$ir,$userid,$h,$mi,$api;
	$mt=$db->fetch_row($mi);
	if ($mt['proposer_id'] == $userid)
	{
		$p1=$ir;
		$p2=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposed_id']}"));
		$event=$p2['userid'];
	}
	else
	{
		$p1=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposer_id']}"));
		$p2=$ir;
		$event=$p1['userid'];
	}
	if ($api->UserStatus($userid, 'infirmary') || $api->UserStatus($userid, 'dungeon'))
	{
		alert('danger',"Uh Oh!","You can only sleep with one person while in the infirmary and/or dungeon, and trust me when I say its not your spouse.",true,'marriage.php');
		die($h->endpage());
	}
	if ($ir['brave'] < $ir['maxbrave'])
	{
		alert('danger',"Uh Oh!","You must have 100% bravery to even attempt to sleep with your spouse.",true,'marriage.php');
		die($h->endpage());
	}
	$outcome=Random(1,3);
	if ($outcome == 1)
	{
		alert('success',"Success!","You and your spouse enjoy snuggling each other in bed. You both wake up feeling well rested. This increases your marriage's happiness by one point.",true,'marriage.php');
		$db->query("UPDATE `marriage_tmg` SET `happiness` = `happiness` + 1 WHERE `marriage_id` = {$mt['marriage_id']}");
		$api->GameAddNotification($event,"Your spouse, {$ir['username']}, slept with you. You both snuggled and woke up refreshed. This increases your marriage's happiness by one point.");
		$api->SystemLogsAdd($userid,'marriage',"Slept with their spouse.");
		$api->SystemLogsAdd($event,'marriage',"Slept with their spouse.");
	}
	if ($outcome == 2)
	{
		alert('success',"Success!","You and your spouse attempt to fall asleep on your crappy mattress. It takes you bother forever to fall asleep, but once you do, its only for an hour. Time to get up. You both lose all your energy.",true,'marriage.php');
		$db->query("UPDATE `users` SET `energy` = 0 WHERE `userid` = {$userid} AND {$mt['marriage_id']}");
		$api->GameAddNotification($event,"Your spouse, {$ir['username']}, slept with you. You both had a hard time falling asleep. You have no energy for this upcoming day.");
		$api->SystemLogsAdd($userid,'marriage',"Slept with their spouse and got no sleep.");
		$api->SystemLogsAdd($event,'marriage',"Slept with their spouse and got no sleep.");
	}
	if ($outcome == 3)
	{
		alert('success',"Success!","You and your spouse attempt to fall asleep. Except, there's very little sleeping! ;) Time flies, and you're drained, and they're super happy. Morning comes, and you still question what happened, but you both are full of energy! This increases your marriage happiness by two points!",true,'marriage.php');
		$db->query("UPDATE `users` SET `energy` = 0 WHERE `userid` = {$userid} AND {$mt['marriage_id']}");
		$db->query("UPDATE `marriage_tmg` SET `happiness` = `happiness` + 2 WHERE `marriage_id` = {$mt['marriage_id']}");
		$api->GameAddNotification($event,"Your spouse, {$ir['username']}, slept with you. You both had a very fun evening. Your marriage happiness increases by two!");
		$api->SystemLogsAdd($userid,'marriage',"Slept with their spouse and stayed up all night.");
		$api->SystemLogsAdd($event,'marriage',"Slept with their spouse and stayed up all night.");
	}
	$api->UserInfoSet($userid,'brave',-50,true);
}
function letter()
{
	global $db,$ir,$userid,$h,$mi,$api;
	$mt=$db->fetch_row($mi);
	if ($mt['proposer_id'] == $userid)
	{
		$p1=$ir;
		$p2=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposed_id']}"));
		$event=$p2['userid'];
	}
	else
	{
		$p1=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposer_id']}"));
		$p2=$ir;
		$event=$p1['userid'];
	}
	if (isset($_POST['letter']))
	{
		if (!isset($_POST['verf']) || !verify_csrf_code('marriage_loveletter', stripslashes($_POST['verf'])))
		{
			alert('danger',"Uh Oh!","Your request has been blocked for your security. Try to be quicker next time.",true,'marriage.php');
			die($h->endpage());
		}
		$msg = $db->escape(str_replace("\n", "<br />", strip_tags(stripslashes($_POST['letter']))));
		if (empty($msg))
		{
			alert('danger',"Uh Oh!","Please fill in a message to be sent.",true,'marriage.php');
			die($h->endpage());
		}
		if (strlen($msg) > 250)
		{
			alert('danger',"Uh Oh!","Love letters may only be 250 characters in length, at maximum.",true,'marriage.php');
			die($h->endpage());
		}
		$api->GameAddMail($event,"Spouse Love Letter", $msg, $userid);
		alert('success',"Success!","You have successfully sent your love letter. May their knees tremble at what you have said.",true,'marriage.php');
	}
	else
	{
		$csrf=request_csrf_html('marriage_loveletter');
		echo "Write a lover letter for your spouse! I'm sure they'll enjoy it. Remember, staff may read what you write.. so uh, you know... keep it clean.<br />
		<form method='post'>
			<input type='text' class='form-control' name='letter' required='1'>
			<input type='submit' class='btn btn-primary' value='Send Letter'>
			{$csrf}
		</form>";
	}
}
function divorce()
{
	global $db,$ir,$userid,$h,$mi,$api;
	$mt=$db->fetch_row($mi);
	if ($mt['proposer_id'] == $userid)
	{
		$p1=$ir;
		$p2=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposed_id']}"));
		$event=$p2['userid'];
	}
	else
	{
		$p1=$db->fetch_row($db->query("SELECT * FROM `users` WHERE `userid` = {$mt['proposer_id']}"));
		$p2=$ir;
		$event=$p1['userid'];
	}
	if (isset($_POST['divorce']))
	{
		if (!isset($_POST['verf']) || !verify_csrf_code('marriage_divorce', stripslashes($_POST['verf'])))
		{
			alert('danger',"Uh Oh!","Your request has been blocked for your security. Try to be quicker next time.",true,'marriage.php');
			die($h->endpage());
		}
		alert('success',"Success!","You have successfully divorced your spouse.",true,'index.php');
		$api->GameAddNotification($event,"Your spouse, {$ir['username']}, has divorced you.");
		$db->query("UPDATE `users` SET `will` = 0 WHERE `userid` = {$userid} AND `userid` = {$event}");
		$db->query("DELETE FROM `marriage_tmg` WHERE `marriage_id` = {$mt['marriage_id']}");
	}
	else
	{
		$csrf=request_csrf_html('marriage_divorce');
		echo "Are you sure you wish to divorce your spouse? There is no confirmation after this point, so be sure!
		<form method='post'>
			<input type='hidden' name='divorce' value='fuckingdoit'>
			<input type='submit' class='btn btn-danger' value='Divorce'>
			{$csrf}
		</form>";
	}
}
$h->endpage();