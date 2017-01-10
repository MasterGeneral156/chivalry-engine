<?php
require("globals.php");
$tresder = (Random(100, 999));
if (user_infirmary($ir['userid']) == true)
{
	alert('danger',"{$lang["GEN_INFIRM"]}","{$lang['ERRDE_EXPLORE']}");
	die($h->endpage());
}
if (user_dungeon($ir['userid']) == true)
{
	alert('danger',"{$lang["GEN_DUNG"]}","{$lang['ERRDE_EXPLORE2']}");
	die($h->endpage());
}
echo"<h4>{$lang['EXPLORE_INTRO']}</h4>
<div class='col-md-8'>
	<ul class='nav nav-pills nav-tabs'>
		<li class='nav-item'><a data-toggle='tab' class='nav-link' href='#SHOPS'>{$lang['EXPLORE_SHOP']}</a></li>
		<li class='nav-item'><a data-toggle='tab' class='nav-link' href='#FD'>{$lang['EXPLORE_FD']}</a></li>
		<li class='nav-item'><a data-toggle='tab' class='nav-link' href='#HL'>{$lang['EXPLORE_HL']}</a></li>
		<li class='nav-item'><a data-toggle='tab' class='nav-link' href='#ADMIN'>{$lang['EXPLORE_ADMIN']}</a></li>
		<li class='nav-item'><a data-toggle='tab' class='nav-link' href='#GAMES'>{$lang['EXPLORE_GAMES']}</a></li>
		<li class='nav-item'><a data-toggle='tab' class='nav-link' href='#GUILDS'>{$lang['EXPLORE_GUILDS']}</a></li>
		<li class='nav-item'><a data-toggle='tab' class='nav-link' href='#ACT'>{$lang['EXPLORE_ACT']}</a></li>
		<li class='nav-item'><a data-toggle='tab' class='nav-link' href='#PINTER'>{$lang['EXPLORE_PINTER']}</a></li>
	</ul>
</div>
<div class='col-md-8'>
	<div class='tab-content'>
		<div id='SHOPS' class='tab-pane fade in'>
			<div class='card card-default'>
				<div class='card-block'>
					<a href='shops.php'>{$lang['EXPLORE_LSHOP']}</a><br />
					<a href='#'>{$lang['EXPLORE_POSHOP']}</a><br />
					<a href='itemmarket.php'>{$lang['EXPLORE_IMARKET']}</a><br />
					<a href='#'>{$lang['EXPLORE_IAUCTION']}</a><br />
					<a href='#'>{$lang['EXPLORE_TRADE']}</a><br />
					<a href='#'>{$lang['EXPLORE_SCMARKET']}</a><br />	
				</div>
			</div>
		</div>
		<div id='FD' class='tab-pane fade'>
			<div class='card card-default'>
				<div class='card-block'>
					<a href='bank.php'>{$lang['EXPLORE_BANK']}</a><br />
					<a href='estates.php'>{$lang['EXPLORE_ESTATES']}</a><br />
					<a href='travel.php'>{$lang['EXPLORE_TRAVEL']}</a><br />
				</div>
			</div>
		</div>
		<div id='HL' class='tab-pane fade'>
			<div class='card card-default'>
				<div class='card-block'>
					<a href='#'>{$lang['EXPLORE_MINE']}</a><br />
					<a href='#'>{$lang['EXPLORE_WC']}</a><br />
					<a href='#'>{$lang['EXPLORE_FARM']}</a><br />
					<a href='bottent.php'>{$lang['EXPLORE_BOTS']}</a><br />
				</div>
			</div>
		</div>
		<div id='ADMIN' class='tab-pane fade'>
			<div class='card card-default'>
				<div class='card-block'>
					<a href='users.php'>{$lang['EXPLORE_USERLIST']}</a><br />
					<a href='staff.php'>{$lang['EXPLORE_STAFFLIST']}</a><br />
					<a href='fedjail.php'>{$lang['EXPLORE_FED']}</a><br />
					<a href='stats.php'>{$lang['EXPLORE_STATS']}</a><br />
					<a href='playerreport.php'>{$lang['EXPLORE_REPORT']}</a><br />
					<a href='announcements.php'>{$lang['EXPLORE_ANNOUNCEMENTS']}</a><br />
				</div>
			</div>
		</div>
		<div id='GAMES' class='tab-pane fade'>
			<div class='card card-default'>
				<div class='card-block'>
					<a href='#'>{$lang['EXPLORE_RR']}</a><br />
					<a href='hilow.php?tresde={$tresder}'>{$lang['EXPLORE_HILO']}</a><br />
					<a href='roulette.php?tresde={$tresder}'>{$lang['EXPLORE_ROULETTE']}</a><br />
					<a href='slots.php?tresde={$tresder}'>{$lang['EXPLORE_SLOTS']}</a><br />
				</div>
			</div>
		</div>
		<div id='GUILDS' class='tab-pane fade'>
			<div class='card card-default'>
				<div class='card-block'>";
					if ($ir['guild'] > 0)
					{
						echo "<a href='viewguild.php'>{$lang['EXPLORE_YOURGUILD']}</a><br />";
					}
					echo "
					<a href='guilds.php'>{$lang['EXPLORE_GUILDLIST']}</a><br />
				</div>
			</div>
		</div>
		<div id='ACT' class='tab-pane fade'>
			<div class='card card-default'>
				<div class='card-block'>
					<a href='dungeon.php'>{$lang['EXPLORE_DUNG']}</a><br />
					<a href='infirmary.php'>{$lang['EXPLORE_INFIRM']}</a><br />
					<a href='gym.php'>{$lang['EXPLORE_GYM']}</a><br />
					<a href='#'>{$lang['EXPLORE_JOB']}</a><br />
					<a href='academy.php'>{$lang['EXPLORE_ACADEMY']}</a><br />
					<a href='criminal.php'>{$lang['EXPLORE_CRIMES']}</a><br />
				</div>
			</div>
		</div>
		<div id='PINTER' class='tab-pane fade'>
			<div class='card card-default'>
				<div class='card-block'>
					<a href='forums.php'>{$lang['EXPLORE_FORUMS']}</a><br />
					<a href='newspaper.php'>{$lang['EXPLORE_NEWSPAPER']}</a><br />
					<a href='polling.php'>{$lang['POLL_TITLE']}</a><br />
				</div>
			</div>
		</div>
	</div>
</div>
<div class='col-md-4'>
	<div class='card card-default'>
		<div class='card-header'>
			Top 10 Players
		</div>
		<div class='card-block'>";
			$Rank=0;
			$RankPlayerQuery = 
			$db->query("SELECT u.`userid`, `level`, `username`,
			`strength`, `agility`, `guard`, `labor`, `IQ`
			FROM `users` AS `u`
			INNER JOIN `userstats` AS `us`
			ON `u`.`userid` = `us`.`userid`
			WHERE `u`.`user_level` != 'Admin' AND `u`.`user_level` != 'NPC'
			ORDER BY (`strength` + `agility` + `guard` + `labor` + `IQ`) 
			DESC, `u`.`userid` ASC LIMIT 10");
			while ($pdata=$db->fetch_row($RankPlayerQuery))
			{
				$Rank=$Rank+1;
				echo "{$Rank}) <a href='profile.php?user={$pdata['userid']}'>{$pdata['username']}</a> [{$pdata['userid']}] (Level {$pdata['level']})<br />";
			}
			echo 
		"</div>
	</div>
</div>";
echo "<div class='row'><div class='col-sm-12'><br /><code>http://{$domain}/register.php?REF={$userid}</code><br />
	{$lang['EXPLORE_REF']}</div></div>";
$h->endpage();