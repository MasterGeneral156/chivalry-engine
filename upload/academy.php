<?php
/*
	File:		academy.php
	Created: 	4/4/2016 at 11:49PM Eastern Time
	Info: 		The academy, which players can use to take courses and
				increase their stats for currency and waiting.
	Author:		ImJustIsabella
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
*/
require("globals.php");
if ($api->UserStatus($userid,'dungeon') || $api->UserStatus($userid,'infirmary'))
{
	alert('danger',"Uh Oh!","You cannot visit the academy when you're in the infirmary or dungeon.",true,'explore.php');
	die($h->endpage());
}
echo "<h4><i class='game-icon game-icon-diploma'></i> Local Academy</h4><hr>";
if ($ir['course'] > 0)  //User is enrolled in a course, so lets tell them and stop them
    //And stop them from taking another.
{
	$cd =
        $db->query(
            "/*qc=on*/SELECT `ac_name`, `ac_days`, `ac_cost`
    				 FROM `academy`
    				 WHERE `ac_id` = {$ir['course']}");
    $coud = $db->fetch_row($cd);
    $db->free_result($cd);
	$daystoseconds=$coud['ac_days']*86400;
	$starttime=time()-($ir['course_complete']-$daystoseconds);
	$percentcomplete=round(($starttime/$daystoseconds)*100);
	if (isset($_GET['dropout']))
	{
		if ($percentcomplete <= 5)
		{
			addToEconomyLog('Academy', 'copper', $coud['ac_cost']);
			$db->query("UPDATE `users` 
						SET `primary_currency` = `primary_currency` + {$coud['ac_cost']}, 
						`course` = 0, 
						`course_complete` = 0 
						WHERE `userid` = {$userid}");
			alert("success","Success!","You have successfully dropped out of your course. You have been refunded {$coud['ac_cost']} Copper Coins.",true,'academy.php');
			die($h->endpage());
		}
		elseif (($percentcomplete > 5) && ($percentcomplete <= 60))
		{
			$db->query("UPDATE `users` 
						SET `course` = 0, 
						`course_complete` = 0 
						WHERE `userid` = {$userid}");
			alert("success","Success!","You have successfully dropped out of your course.",true,'academy.php');
			die($h->endpage());
		}
		else
		{
			alert("danger","Uh Oh!","You are too far into your course to dropout now.",true,'academy.php');
		}
	}
    echo "You are currently enrolled in the {$coud['ac_name']} course. You will be finished in " . TimeUntil_Parse($ir['course_complete']) . ".<br />
	You may dropout of this course. Please note that you can only dropout if you've completed less than 60% of the course. If you dropout before 5% 
	completion, you will be refunded all your cash. Otherwise, you will lose all the cash you used to enroll. <br /><b>You have currently completed {$percentcomplete}% of 
	this course.</b><br />
	Do you wish to <a href='?dropout=yes'>dropout</a>?";
    die($h->endpage());
}
if (!isset($_GET['action'])) {
    $_GET['action'] = '';
}
switch ($_GET['action']) {
    case "menu":
        menu();
        break;
    case "start":
        start();
        break;
    default:
        header("Location: ?action=menu");
        break;
}

function menu()
{
    global $db, $userid;
    echo "<table class='table table-bordered table-hover'>
		<thead>
			<tr>
				<th width='25%'>
					Course
				</th>
				<th width='40%'>
					Description
				</th>
				<th width='25%'>
					Cost
				</th>
				<th width='5%'>
					Graduates
				</th>
				<th width='5%'>
                    Action
				</th>
			</tr>
		</thead>
		<tbody>
	   ";
    //Select the courses from in-game.
    $acadq = $db->query("/*qc=on*/SELECT * FROM `academy` ORDER BY `ac_level` ASC, `ac_id` ASC");
    while ($academy = $db->fetch_row($acadq)) {
        $cdo = $db->query("/*qc=on*/SELECT COUNT(`userid`)
                             FROM `academy_done`
                             WHERE `userid` = {$userid}
                             AND `course` = {$academy['ac_id']}");
		$graduates = $db->fetch_single($db->query("/*qc=on*/SELECT COUNT(`userid`)
                             FROM `academy_done`
                             WHERE `course` = {$academy['ac_id']}"));
        //If user has already completed the course.
        if ($db->fetch_single($cdo) > 0) {
            $do = "<i>Graduated</i>";
        } else {
            $do = "<a href='?action=start&id={$academy['ac_id']}'>Attend</a>";
        }
        echo "<tr>
		<td>
			{$academy['ac_name']}<br />";
        //Hide academy level requirement if there is no requirement.
        if (!empty($academy['ac_level'])) {
            echo "Level: {$academy['ac_level']}";
        }
        echo "
		</td>
		<td>
			{$academy['ac_desc']}
		</td>
		<td>
			" . number_format($academy['ac_cost']) . " Copper Coins
		</td>
		<td>
			" . number_format($graduates) . "
		</td>
		<td>
			{$do}
		</td>";
    }
    echo "</tbody></table>";
}

function start()
{
    global $db, $userid, $ir, $h, $api;
    $_GET['id'] = (isset($_GET['id']) && is_numeric($_GET['id'])) ? abs(intval($_GET['id'])) : 0;
    //If the user doesn't specific a course to take.
    if (empty($_GET['id'])) {
        alert('danger', "Uh Oh!", "You didn't select a valid course to take.", true, 'academy.php');
        die($h->endpage());
    }
    $courq = $db->query("/*qc=on*/SELECT * FROM `academy` WHERE `ac_id` = {$_GET['id']} LIMIT 1");
    //If the course specified does not exist.
    if ($db->num_rows($courq) == 0) {
        alert('danger', "Uh Oh!", "The course you chose does not exist. Check your source and try again.", true, 'academy.php');
        die($h->endpage());
    }
    $course = $db->fetch_row($courq);
    //If the user's level is lower than the course requirement.
    if ($course['ac_level'] > $ir['level']) {
        alert('danger', "Uh Oh!", "Your level is too low to take this course. Come back when you are level
		                        {$course['ac_level']} or above.", true, 'academy.php');
        die($h->endpage());
    }
    //If the user doesn't have enough Copper Coins for this course.
    if ($course['ac_cost'] > $ir['primary_currency']) {
        alert('danger', "Uh Oh!", "You do not have enough cash to take this course. You need {$course['ac_cost']},
                                yet you only have {$ir['primary_currency']}", true, 'academy.php');
        die($h->endpage());
    }
    $cdo = $db->query("/*qc=on*/SELECT COUNT(`userid`)
                             FROM `academy_done`
                             WHERE `userid` = {$userid}
                             AND `course` = {$_GET['id']}");
    //If the user has already taken this course.
    if ($db->fetch_single($cdo) > 0) {
        alert('danger', "Uh Oh!", "You have already graduated from this course. No need to enroll again.", true, 'academy.php');
        die($h->endpage());
    }
	$timestamp=$course['ac_days'] * 86400;
	if (getSkillLevel($userid,18))
	{
		$iq=round($ir['iq']/5000);
		if ($iq > 15)
			$iq=15;
		$iq=$iq/100;
		$timestamp=$timestamp*(1-$iq);
	}
    $completed = time() + ($timestamp); //Current Time + (Academy days * seconds in a day)
    $db->query("UPDATE `users` SET `course` = {$_GET['id']},
                `course_complete` = {$completed} 
                WHERE `userid` = {$userid}");
    //Update user's course, and course completion time.
    $api->UserTakeCurrency($userid, 'primary', $course['ac_cost']); //Take user's money.
	addToEconomyLog('Academy', 'copper', ($course['ac_cost'])*-1);
    alert('success', "Success!", "You have successfully enrolled yourself in the {$course['ac_name']} course. It will
	                            complete in {$course['ac_days']} days.", true, 'index.php');
}

$h->endpage();