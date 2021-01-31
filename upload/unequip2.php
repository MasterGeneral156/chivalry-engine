<?php
/*
	File:		unequip.php
	Created: 	4/5/2016 at 12:30AM Eastern Time
	Info: 		Allows players to unequip armor and weapons.
	Author:		TheMasterGeneral
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
*/
require('globals.php');
//Make sure user is trying to unequip a valid slot.
if (!isset($_GET['type']) || !in_array($_GET['type'], array("equip_ring_primary", "equip_ring_secondary", "equip_necklace", "equip_pendant"), true)) {
    alert('danger', "Uh Oh!", "You are trying to unequip from an invalid slot.", true, 'inventory.php');
    die($h->endpage());
}
//User doesn't have anything equipped in this slot.
$eir=$db->fetch_row($db->query("SELECT * FROM `user_equips` WHERE `userid` = {$userid} AND `equip_slot` = '{$_GET['type']}'"));
if ($eir['itemid'] == 0) {
    alert('danger', "Uh Oh!", "You do not have an item equipped in this slot.", true, 'inventory.php');
    die($h->endpage());
}
item_add($userid, $eir['itemid'], 1);
$sbq=$db->query("/*qc=on*/SELECT * FROM `equip_gains` WHERE `userid` = {$userid} and `slot` = '{$_GET['type']}'");
$statloss='';
if ($db->num_rows($sbq) > 0)
{
	while ($sbr=$db->fetch_row($sbq))
	{
        $stats =
					array("maxenergy" => "Maximum Energy", "maxwill" => "Maximum Will",
						"maxbrave" => "Maximum Bravery", "level" => "Level",
						"maxhp" => "Maximum Health", "strength" => "Strength",
						"agility" => "Agility", "guard" => "Guard",
						"labor" => "Labor", "iq" => "IQ",
						"infirmary" => "Infirmary Time", "dungeon" => "Dungeon Time",
						"primary_currency" => "Copper Coins", "secondary_currency"
					=> "Chivalry Tokens", "crimexp" => "Experience", "vip_days" =>
						"VIP Days");
		if ($sbr['direction'] == 'pos')
		{
			if (in_array($sbr['stat'], array('strength', 'agility', 'guard', 'labor', 'iq'))) {
				$db->query("UPDATE `userstats` SET `{$sbr['stat']}` = `{$sbr['stat']}` - {$sbr['number']} WHERE `userid` = {$userid}");
			} elseif (!(in_array($sbr['stat'], array('dungeon', 'infirmary')))) {
				$db->query("UPDATE `users` SET `{$sbr['stat']}` = `{$sbr['stat']}` - {$sbr['number']} WHERE `userid` = {$userid}");
			}
            $mod='lost';
            $ir[$sbr['stat']] = $ir[$sbr['stat']]-$sbr['number'];
		}
		else
		{
			if (in_array($sbr['stat'], array('strength', 'agility', 'guard', 'labor', 'iq'))) {
				$db->query("UPDATE `userstats` SET `{$sbr['stat']}` = `{$sbr['stat']}` + {$sbr['number']} WHERE `userid` = {$userid}");
			} elseif (!(in_array($sbr['stat'], array('dungeon', 'infirmary')))) {
				$db->query("UPDATE `users` SET `{$sbr['stat']}` = `{$sbr['stat']}` + {$sbr['number']} WHERE `userid` = {$userid}");
			}
            $ir[$sbr['stat']] = $ir[$sbr['stat']]+$sbr['number'];
            $mod='gained';
		}
		if (empty($statloss))
            $statloss .= "{$mod} " . number_format($sbr['number']) . " {$stats[$sbr['stat']]}";
        else
            $statloss .= ", {$mod} " . number_format($sbr['number']) . " {$stats[$sbr['stat']]}";
		$db->query("DELETE FROM `equip_gains` WHERE `userid` = {$userid} AND `stat` = '{$sbr['stat']}' AND `slot` = '{$_GET['type']}'");
	}
	$statloss .= " when you unequipped this item.";
}
$db->query("DELETE FROM `user_equips` WHERE `userid` = {$userid} AND `equip_slot` = '{$_GET['type']}'");
$names = array('equip_ring_primary' => "Primary Ring",
    'equip_ring_secondary' => "Secondary Ring",
    'equip_necklace' => "Necklace",
    'equip_pendant' => "Pendant");
//Tell user their slot is now empty
$weapname = $db->fetch_single($db->query("/*qc=on*/SELECT `itmname` FROM `items` WHERE `itmid` = {$eir['itemid']}"));
$api->SystemLogsAdd($userid, 'equip', "Unequipped {$weapname} from their {$_GET['type']} slot.");
alert('success', "Success!", "You have successfully unequipped your {$weapname} from your {$names[$_GET['type']]} slot. You have {$statloss}", true, 'inventory.php');
$h->endpage();