<?php
require('globals.php');
echo "<h3>{$lang['CRIME_TITLE']}</h3>";
if (user_infirmary($ir['userid']) == true || user_dungeon($ir['userid']))
{
	alert('danger',"{$lang['ERROR_GENERIC']}","{$lang['CRIME_ERROR_JI']}");
	die($h->endpage());
}
if (!isset($_GET['action']))
{
    $_GET['action'] = '';
}
switch ($_GET['action'])
{
case 'crime':
    crime();
    break;
default:
    home();
    break;
}
function home()
{
	global $db,$h,$userid,$ir,$lang;
	$crimes = array();
	$q2 = $db->query("SELECT `crimeGROUP`, `crimeNAME`, `crimeBRAVE`, `crimeID` FROM `crimes` ORDER BY `crimeBRAVE` ASC");
	while ($r2 = $db->fetch_row($q2))
	{
		$crimes[] = $r2;
	}
	$db->free_result($q2);
	$q = $db->query("SELECT `cgID`, `cgNAME` FROM `crimegroups` ORDER BY `cgORDER` ASC");
	echo "
	<div class='table-resposive'>
	<table class='table table-bordered'>
		<tr>
			<th>
				{$lang['CRIME_TABLE_CRIME']}
			</th>
			<th>
				{$lang['CRIME_TABLE_COST']}
			</th>
			<th>
				{$lang['CRIME_TABLE_COMMIT']}
			</th>
		</tr>";
	while ($r = $db->fetch_row($q))
	{
		echo "<tr><td colspan='3' class='h'>{$r['cgNAME']} {$lang['CRIME_TABLE_CRIMES']}</td></tr>";
		foreach ($crimes as $v)
		{
			if ($v['crimeGROUP'] == $r['cgID'])
			{
				echo "<tr><td>{$v['crimeNAME']}</td><td>{$v['crimeBRAVE']} {$lang['INDEX_BRAVE']}</td><td><a href='?action=crime&c={$v['crimeID']}'>{$lang['CRIME_TABLE_COMMIT']}</a></td></tr>";
			}
		}
	}
	$db->free_result($q);
	echo "</table></div>";
	$h->endpage();
}
function crime()
{
	global $db,$lang,$userid,$ir,$h,$api;
	if (!isset($_GET['c']))
	{
		$_GET['c'] = 0;
	}
	$_GET['c'] = abs((int) $_GET['c']);
	if ($_GET['c'] <= 0)
	{
		alert('danger',"{$lang['ERROR_INVALID']}","{$lang['CRIME_COMMIT_INVALID']}");
	}
	else
	{
		$q =  $db->query("SELECT * FROM `crimes` WHERE `crimeID` = {$_GET['c']} LIMIT 1");
		if ($db->num_rows($q) == 0)
		{
			alert('danger',"{$lang['ERROR_INVALID']}","{$lang['CRIME_COMMIT_INVALID']}");
			die($h->endpage());
		}
		$r = $db->fetch_row($q);
		$db->free_result($q);
		if ($ir['brave'] < $r['crimeBRAVE'])
		{
			alert('danger',"{$lang['ERROR_GENERIC']}","{$lang['CRIME_COMMIT_BRAVEBAD']}");
			die($h->endpage());
		}
		else
		{
			$ec = "\$sucrate=" . str_replace(array("LEVEL", "EXP", "WILL", "IQ"), array($ir['level'], $ir['xp'], $ir['will'], $ir['iq']), $r['crimePERCFORM']) . ";";
			eval($ec);
			$ir['brave'] -= $r['crimeBRAVE'];
			$db->query("UPDATE `users` SET `brave` = {$ir['brave']}  WHERE `userid` = $userid");
			if (Random(1, 100) <= $sucrate)
			{
				if (!empty($r['crimePRICURMIN']))
				{
					$prim_currency=Random($r['crimePRICURMIN'],$r['crimePRICURMAX']);
					$db->query("UPDATE `users` SET `primary_currency` = `primary_currency` + {$prim_currency} WHERE `userid` = {$userid}");
				}
				if (!empty($r['crimeSECURMIN']))
				{
					$sec_currency=Random($r['crimeSECURMIN'],$r['crimeSECCURMAX']);
					$db->query("UPDATE `users` SET `secondary_currency` = `secondary_currency` + {$sec_currency} WHERE `userid` = {$userid}");
				}
				if (!empty($r['crimeSUCCESSITEM']))
				{
					item_add($userid, $r['crimeSUCCESSITEM'], 1);
				}
				$text = str_replace("{money}", $prim_currency, $r['crimeSTEXT']);
				$title=$lang['ERROR_SUCCESS'];
				$type='success';
				$db->query("UPDATE `users` SET `xp` = `xp` + {$r['crimeXP']} WHERE `userid` = $userid");
				$api->SystemLogsAdd($userid,'crime',"Successfully commited the {$r['crimeNAME']} crime.");
			}
			else
			{
					$text=$r['crimeFTEXT'];
					$title=$lang['ERROR_GENERIC'];;
					$type='danger';
					$dtime=Random($r['crimeDUNGMIN'],$r['crimeDUNGMAX']);
					put_dungeon($userid,$dtime,$r['crimeDUNGREAS']);
					$api->SystemLogsAdd($userid,'crime',"Failed to commit the {$r['crimeNAME']} crime.");
			}
			alert("{$type}","{$title}","{$r['crimeITEXT']} {$text}");
			die($h->endpage());
		}
	}
}