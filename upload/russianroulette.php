<?php
/*
	File: 		russianroulette.php
	Created: 	5/2/2017 at 12:38PM Eastern Time
	Info: 		Allows players to play a round of russian roulette.
	Author: 	TheMasterGeneral, Original by ImJustIsabella
	Website:	https://github.com/MasterGeneral156/chivalry-engine
*/
require("globals.php");
echo "<h3>Russian Roulette</h3><hr />";
//Do not allow the user to play Russian Roulette if they're in the dungeon/infirmary.
if ($api->UserStatus($userid,'dungeon'))
{
    alert('danger', "Uh Oh!", "You cannot play Russian Roulette while in the dungeon.");
    die($h->endpage());
}
if ($api->UserStatus($userid,'infirmary'))
{
    alert('danger', "Uh Oh!", "You cannot play Russian Roulette while in the infirmary.");
    die($h->endpage());
}
//Action switch to get the user's action. If not set, redirect to home function.
if (!isset($_GET['action'])) {
    $_GET['action'] = '';
}
switch ($_GET['action']) {
    case 'do':
        dorr();
        break;
    case 'dont':
        dontrr();
        break;
    case 'withdraw':
        wdrr();
        break;
    case 'bet':
        bet();
        break;
    default:
        home();
        break;
}
function home()
{
    global $db,$api,$h,$userid;
    echo "
    <form method='post' action='?action=bet'>
    <table class='table table-bordered'>
        <tr>
            <th colspan='2'>
                Welcome to Russian Roulette, good sir! Who would you like to challenge?
            </th>
        </tr>
        <tr>
            <th>
                 User
            </th>
            <td>
                " . user_dropdown('user',$userid) . "
            </td>
        </tr>
        <tr>
             <th>
                 Bet
            </th>
            <td>
                <input type='number' name='bet' min='0' value='0' required='1' class='form-control'>
            </td>
        </tr>
        <tr>
            <td colspan='2'>
                <input type='submit' class='btn btn-primary' value='Send Challenge'>
            </td>
        </tr>
    </table>
    </form>
    <h4>Challenges Involving You</h4>";
    echo "<table class='table'>
        <tr>
            <th>
                Challenger
            </th>
            <th>
                Receiver
            </th>
            <th>
                Bet
            </th>
            <th>
                Actions
            </th>
        </tr>";
    //List the challenges the player has sent/received.
   $q=$db->query("SELECT * FROM `russian_roulette` WHERE `challengee` = {$userid} OR `challenger` = {$userid}");
    while ($r=$db->fetch_row($q))
    {
        //Player is the challenger in this challenge. Give them option to withdraw bet.
        if ($userid == $r['challenger'])
            $link = "<a href='?action=withdraw&id={$r['challengee']}'>Withdraw</a>";
        //Player is the receiver, give them option to decline or accept.
        else
            $link = "<a href='?action=do&id={$r['challenger']}'>Accept</a><br />
                        <a href='?action=dont&id={$r['challenger']}'>Decline</a>";
        echo "
        <tr>
            <td>
                {$api->SystemUserIDtoName($r['challenger'])} [{$r['challenger']}]
            </td>
            <td>
                {$api->SystemUserIDtoName($r['challengee'])} [{$r['challengee']}]
            </td>
            <td>
                " . number_format($r['reward']) . " Primary Currency
            </td>
            <td>
                {$link}
            </td>
        </tr>";
    }
    echo"</table>";
    $h->endpage();
}
function bet()
{
    global $db,$api,$h,$userid,$ir;
    $_POST['user'] = (isset($_POST['user']) && is_numeric($_POST['user'])) ? abs($_POST['user']) : '';
    $_POST['bet'] = (isset($_POST['bet']) && is_numeric($_POST['bet'])) ? abs($_POST['bet']) : '';
    //User to bet is empty.
    if (empty($_POST['user'])) {
        alert('danger', "Uh Oh!", "Please select a valid user to play against.");
        die($h->endpage());
    }
    $q=$db->query("SELECT `userid`
                    FROM `users`
                    WHERE `userid` = {$_POST['user']}");
    //User to challenge does not exist.
    if ($db->num_rows($q) == 0)
    {
        alert('danger', "Uh Oh!", "The user you are trying to bet against does not exist.",true,'russianroulette.php');
        die($h->endpage());
    }
    //User is trying to challenge themselves
    if ($_POST['user'] == $userid)
    {
        alert('danger', "Uh Oh!", "You are not allowed to challenge yourself to Russian Roulette.",true,'russianroulette.php');
        die($h->endpage());
    }
    //Current player does not have enough Primary Currency for their bet.
    if (!$api->UserHasCurrency($userid,'primary',$_POST['bet']))
    {
        alert('danger', "Uh Oh!", "You do not have enough Primary Currency to bet {$_POST['bet']}. You only have {$ir['primary_currency']}.",true,'russianroulette.php');
        die($h->endpage());
    }
    //User has already been challenge by the current player.
    $q2=$db->query("SELECT `challengee`
                    FROM `russian_roulette`
                    WHERE `challengee` = {$_POST['user']}
                    AND `challenger` = {$userid}");
    if ($db->num_rows($q2) > 0)
    {
        alert('danger', "Uh Oh!", "You already have a bet sent to this user. Remind them to accept the challenge, or withdraw it.",true,'russianroulette.php');
        die($h->endpage());
    }
    //All checks pass, so lets add it to the database, logs, and whatnot...
    $db->query("INSERT INTO `russian_roulette`
                (`challengee`, `challenger`, `reward`)
                VALUES
                ('{$_POST['user']}', '{$userid}', '{$_POST['bet']}');");
    //Alert the receiver
    $NotifText="<a href='profile.php?user={$userid}'>{$api->SystemUserIDtoName($userid)}</a> has challenged you to a round of Russian Roulette. View the challenge <a href='russianroulette.php'>here</a>.";
    $api->GameAddNotification($_POST['user'],$NotifText);
    $api->UserTakeCurrency($userid,'primary',$_POST['bet']);
    alert('success', "Success!", "You have successfully challenged {$api->SystemUserIDtoName($_POST['user'])} to a round of Russian Roulette.",true,'russianroulette.php');
    die($h->endpage());
}
function wdrr()
{
    global $db,$api,$h,$userid;
    $_GET['id'] = (isset($_GET['id']) && is_numeric($_GET['id'])) ? abs($_GET['id']) : '';
    //GET is empty.
    if (empty($_GET['id']))
    {
        alert('danger', "Uh Oh!", "Please select a valid user to withdraw your challenge from.");
        die($h->endpage());
    }
    $q=$db->query("SELECT `userid`
                    FROM `users`
                    WHERE `userid` = {$_GET['id']}");
    //Person to withdraw against does not exist.
    if ($db->num_rows($q) == 0)
    {
        alert('danger', "Uh Oh!", "The user you are trying to withdraw your challenge from does not exist.",true,'russianroulette.php');
        die($h->endpage());
    }
    $q2=$db->query("SELECT `challengee`
                    FROM `russian_roulette`
                    WHERE `challengee` = {$_GET['id']}
                    AND `challenger` = {$userid}");
    //User does not have any challenges from the current player.
    if ($db->num_rows($q2) == 0)
    {
        alert('danger', "Uh Oh!", "You do not have any challenges open with this user.",true,'russianroulette.php');
        die($h->endpage());
    }
    //Checks passed, delete it all.
    $r=$db->fetch_single($db->query("SELECT `reward`
                                    FROM `russian_roulette`
                                    WHERE `challengee` = {$_GET['id']}
                                    AND `challenger` = {$userid}"));
    $api->UserGiveCurrency($userid,'primary',$r);
    $NotifText="<a href='profile.php?user={$userid}'>{$api->SystemUserIDtoName($userid)}</a> has withdrawn their Russian Roulette challenge.";
    $api->GameAddNotification($_GET['id'],$NotifText);
    $db->query("DELETE FROM `russian_roulette`
                WHERE `challengee` = {$_GET['id']}
                AND `challenger` = {$userid}");
    alert('success', "Success!", "You have successfully withdrawn your Russian Roulette challenge against {$api->SystemUserIDtoName($_GET['id'])}.",true,'russianroulette.php');
    die($h->endpage());
}