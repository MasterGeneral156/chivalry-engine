<?php
/*
	File:		bottent.php
	Created: 	4/4/2016 at 11:54PM Eastern Time
	Info: 		A list of the setup bots in game. Players can attack them
				for an item drop once every pre-defined period.
	Author:		TheMasterGeneral
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
*/
$macropage = ('bottent.php');
require('globals.php');
if ($api->UserStatus($userid,'dungeon') || $api->UserStatus($userid,'infirmary'))
{
	alert('danger',"Uh Oh!","You cannot visit the NPC Battle List while in the infirmary or dungeon.",true,'explore.php');
	die($h->endpage());
}
echo "<h3><i class='game-icon game-icon-guards'></i> NPC Battle List</h3><hr />Welcome to the Bot Tent. Here you may challenge NPCs to battle. If you win, you'll receive
    an item. These items may or may not be useful in your adventures. To deter players getting massive amounts of items,
    you can only attack these NPCs every so often. Their cooldown is listed here as well. To receive the item, you must
    mug the bot.<hr />";
$query = $db->query("/*qc=on*/SELECT * FROM `botlist`");
//List all the bots.
while ($result = $db->fetch_row($query)) 
{
    //Grab the last time the user attacked this bot.
    $timequery = $db->query("/*qc=on*/SELECT `lasthit` FROM `botlist_hits` WHERE `userid` = {$userid} && `botid` = {$result['botuser']}");
    $r2 = $db->fetch_single($timequery);
    //Grab bot's stats.
    $r3 = $db->fetch_row($db->query("/*qc=on*/SELECT `strength`,`agility`,`guard` FROM `userstats` WHERE `userid` = {$result['botuser']}"));
    $ustats = $ir['strength'] + $ir['agility'] + $ir['guard'];
    $themstats = $r3['strength'] + $r3['agility'] + $r3['guard'];
    //Chance the user can beat the bot.
    $chance = round((($ustats / $themstats) * 100) / 2, 1);
    $chance = ($chance < 100) ? $chance : 100;
    //Assign bot name to variable to cut down on queries.
    $botname = $api->SystemUserIDtoName($result['botuser']);
    //Player cannot attack the bot.
    if ((time() <= ($r2 + $result['botcooldown'])) && ($r2 > 0)) {
        $cooldown = ($r2 + $result['botcooldown']) - time();
        $attack = "Cooldown Remaining: " . ParseTimestamp($cooldown);
    } //Player CAN attack the bot.
    else {
        $attack = "<form action='attack.php'>
					<input type='hidden' name='user' value='{$result['botuser']}'>
					<input type='hidden' name='ref' value='bottent'>
					<input type='submit' class='btn btn-danger' value='Attack {$botname}'>
					</form>
					(Odds of Victory {$chance}%)";
    }
	echo "
	<div class='row'>
		<div class='col-sm'>
			<a href='profile.php?user={$result['botuser']}'>{$botname}</a> [{$result['botuser']}]<br />
			<small>
			Level: " . $api->UserInfoGet($result['botuser'], 'level') . "<br />
			Cooldown: " . ParseTimestamp($result['botcooldown']) . "<br />
			Drop: " . $api->SystemItemIDtoName($result['botitem']) . "
			</small>
		</div>
		<div class='col-sm'>
			{$attack}
		</div>
	</div>
	<hr />";
}
echo "</table>";
$h->endpage();