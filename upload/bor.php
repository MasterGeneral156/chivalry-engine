<?php
/*
	File:		bor.php
	Created: 	10/18/2017 at 4:02PM Eastern Time
	Info: 		Random items and whatnot!
	Author:		TheMasterGeneral
	Website: 	http://chivalryisdead.x10.mx/
*/
require('globals.php');
$tresder = Random(100, 999);
echo "<h3>Box of Random</h3><hr />";
$_GET['tresde'] = (isset($_GET['tresde']) && is_numeric($_GET['tresde'])) ? abs($_GET['tresde']) : 0;
if (!isset($_SESSION['tresde'])) {
    $_SESSION['tresde'] = 0;
}
if (($_SESSION['tresde'] == $_GET['tresde']) || $_GET['tresde'] < 100)
{
    alert("danger", "Uh Oh!", "Please do not refresh while opening Boxes of Random. Thank you!", true, "?tresde={$tresder}");
    die($h->endpage());
}
$_SESSION['tresde'] = $_GET['tresde'];
$left=$ir['bor']-1;
if ($ir['bor'] == 0)
{
    alert('danger',"Uh Oh!","You cannot open anymore Boxes of Random today.",true,'explore.php');
    die($h->endpage());
}
if ($api->UserStatus($userid,'dungeon'))
{
    alert('danger',"Uh Oh!","You cannot open Boxes of Random while in the dungeon.",true,'explore.php');
    die($h->endpage());
}
if ($api->UserStatus($userid,'infirmary'))
{
    alert('danger',"Uh Oh!","You cannot open Boxes of Random while in the infirmary.",true,'explore.php');
    die($h->endpage());
}
if (!$api->UserHasItem($userid,33,1))
{
    alert('danger',"Uh Oh!","You need at least one Box of Random to open Boxes of Random.",true,'explore.php');
    die($h->endpage());
}
$db->query("UPDATE `users` SET `bor` = `bor` - 1 WHERE `userid` = {$userid}");
$api->UserTakeItem($userid,33,1);
$chance=Random(1,87);
if ($chance <= 30)
{
    $cash=Random(750,2750);
	$cash=round($cash+($cash*levelMultiplier($ir['level'])));
    echo "You open this Box of Random and pull out " . number_format($cash) . " Copper Coins. Cool!";
    $api->UserGiveCurrency($userid,'primary',$cash);
    $api->SystemLogsAdd($userid,"bor","Received {$cash} Copper Coins.");
}
elseif (($chance > 30) && ($chance <= 40))
{
    $cash=Random(5,20);
	$specialnumber=((getSkillLevel($userid,11)*5)/100);
	$cash=round($cash+($cash*$specialnumber));
	$cash=round($cash+($cash*levelMultiplier($ir['level'])));
    echo "You quickly open this Box of Random and pull out {$cash} Chivalry Tokens. Neat!";
	$api->UserGiveCurrency($userid,'secondary',$cash);
    $api->SystemLogsAdd($userid,"bor","Received {$cash} Chivalry Tokens.");
}
elseif (($chance > 40) && ($chance <= 50))
{
    $cash=Random(5,10);
	$cash=round($cash+($cash*levelMultiplier($ir['level'])));
    echo "Tick, tock. Ka-boom!";
    $api->UserStatusSet($userid,'infirmary',$cash,"Ticking Box");
    $api->SystemLogsAdd($userid,"bor","Received {$cash} Infirmary minutes.");
}
elseif (($chance > 50) && ($chance <= 55))
{
    echo "You open this Box of Random and pull out a piece of bread. Yum!";
    $api->UserGiveItem($userid,19,1);
    $api->SystemLogsAdd($userid,"bor","Received Bread.");
}
elseif (($chance > 55) && ($chance <= 60))
{
    echo "You open this Box of Random and pull out a piece of venison. Yum!";
    $api->UserGiveItem($userid,20,1);
    $api->SystemLogsAdd($userid,"bor","Received Venison.");
}
elseif (($chance > 60) && ($chance <= 65))
{
    echo "You open this Box of Random and get a Small Health Potion.";
    $api->UserGiveItem($userid,7,1);
    $api->SystemLogsAdd($userid,"bor","Received Small Health Potion.");
}
elseif (($chance > 65) && ($chance <= 70))
{
	$rng=Random(2,4);
	$rng=round($rng+($rng*levelMultiplier($ir['level'])));
    echo "You open this Box of Random and find yourself {$rng} Linen Wraps! Nifty!";
    $api->UserGiveItem($userid,6,$rng);
    $api->SystemLogsAdd($userid,"bor","Received {$rng} Linen Wraps.");
}
elseif (($chance > 70) && ($chance <= 75))
{
	$rng=Random(2,4);
	$rng=round($rng+($rng*levelMultiplier($ir['level'])));
    echo "You open this Box of Random and find yourself {$rng} Dungeon Keys! Nifty!";
    $api->UserGiveItem($userid,30,$rng);
    $api->SystemLogsAdd($userid,"bor","Received {$rng} Dungeon Keys.");
}
elseif (($chance > 75) && ($chance <= 78))
{
	$rng=Random(1,2);
	$rng=round($rng+($rng*levelMultiplier($ir['level'])));
    echo "You open this Box of Random and find yourself {$rng} Explosives.";
    $api->UserGiveItem($userid,28,$rng);
    $api->SystemLogsAdd($userid,"bor","Received {$rng} Small Explosives.");
}
elseif (($chance > 78) && ($chance <= 80))
{
    echo "You open this Box of Random and find a Chivalry Gym Pass.";
    $api->UserGiveItem($userid,18,1);
    $api->SystemLogsAdd($userid,"bor","Received Chivalry Gym Pass.");
}
elseif (($chance > 80) && ($chance <= 81))
{
	echo "You open this Box of Random and find a Distant Attack Scroll.";
    $api->UserGiveItem($userid,90,1);
    $api->SystemLogsAdd($userid,"bor","Received Distant Attack Scroll.");
}
elseif (($chance > 81) && ($chance <= 82))
{
	echo "You open this Box of Random and find a Mysterious Potion.";
    $api->UserGiveItem($userid,123,1);
    $api->SystemLogsAdd($userid,"bor","Received Mysterious Potion.");
}
elseif (($chance > 82) && ($chance <= 83))
{
	echo "You open this Box of Random and find an Acupuncture Needle.";
    $api->UserGiveItem($userid,100,1);
    $api->SystemLogsAdd($userid,"bor","Received Acupuncture Needle.");
}
elseif ($chance == 84)
{
	if (Random(1,10) == 9)
	{
		echo "You open this Box of Random and find something that will <i>never</i> give you up...";
		$api->UserGiveItem($userid,149,1);
		$api->SystemLogsAdd($userid,"bor","Received Rickity Bomb.");
	}
	else
	{
		echo "You open this Box of Random and get a bunch of junk.";
		$api->SystemLogsAdd($userid,"bor","Received nothing.");
	}
}
elseif ($chance == 85)
{
	if (Random(1,10) == 9)
	{
		$extras=Random(1,3);
		echo "You open this Box of Random and find a voucher for {$extras} extra Hexbags!";
		$db->query("UPDATE `users` SET `hexbags` = `hexbags` + {$extras} WHERE `userid` = {$userid}");
		$api->SystemLogsAdd($userid,"bor","Received {$extras} Hexbags.");
	}
	else
	{
		echo "You open this Box of Random and get a bunch of junk.";
		$api->SystemLogsAdd($userid,"bor","Received nothing.");
	}
}
elseif ($chance == 86)
{
	echo "You open this Box of Random and find an Herb of the Enlightened Miner.";
    $api->UserGiveItem($userid,177,1);
    $api->SystemLogsAdd($userid,"bor","Received Herb of the Enlightened Miner.");
}
else
{
    echo "You open this Box of Random and get a bunch of junk.";
    $api->SystemLogsAdd($userid,"bor","Received nothing.");
}
echo " You can open another {$left} Boxes of Random today.<hr />
<a href='?tresde={$tresder}'>Open Another</a><br />
<a href='explore.php'>Back to Town</a>";
$api->SystemLogsAdd($userid, 'itemuse', "Used Box of Random.");
$h->endpage();