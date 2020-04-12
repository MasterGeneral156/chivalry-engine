<?php
/*
	File:		itemuse.php
	Created: 	4/5/2016 at 12:10AM Eastern Time
	Info: 		Allows players to use an item.
	Author:		TheMasterGeneral
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
*/
require('globals.php');
$Time = time();
$_GET['item'] = (isset($_GET['item']) && is_numeric($_GET['item'])) ? abs($_GET['item']) : '';
if (empty($_GET['item'])) {
    alert('danger', "Uh Oh!", "Please specify an item to use.", true, 'inventory.php');
} else {
    $i = $db->query("/*qc=on*/SELECT `effect1`, `effect2`, `effect3`,  `effect1_on`, `effect2_on`, `effect3_on`,
                     `itmname`, `inv_itemid`, `weapon`, `armor` FROM `inventory` AS `iv` INNER JOIN `items` AS `i`
                     ON `iv`.`inv_itemid` = `i`.`itmid` WHERE `iv`.`inv_id` = {$_GET['item']}
                     AND `iv`.`inv_userid` = $userid");
    if ($db->num_rows($i) == 0) {
        $db->free_result($i);
        alert('danger', "Uh Oh!", "You are trying to use an item that doesn't exist.", true, 'inventory.php');
    } else {
        $r = $db->fetch_row($i);
        $db->free_result($i);
        if (!$r['effect1_on'] && !$r['effect2_on'] && !$r['effect3_on']) {
            alert('danger', "Uh Oh!", "This item cannot be used as it has no effects.", true, 'inventory.php');
            die($h->endpage());
        }
		if (($r['armor'] > 0) || ($r['weapon'] > 0))
		{
			alert('danger', "Uh Oh!", "You cannot use weapons and armor in this way.", true, 'inventory.php');
            die($h->endpage());
		}
        for ($enum = 1; $enum <= 3; $enum++) {
            if ($r["effect{$enum}_on"] == 'true') {
                $einfo = unserialize($r["effect{$enum}"]);
                if ($einfo['inc_type'] == "percent") {
                    if (in_array($einfo['stat'], array('energy', 'will', 'brave', 'hp'))) {
                        $inc = round($ir['max' . $einfo['stat']] / 100 * $einfo['inc_amount']);
                        //Item Potency
                        $specialnumber=((getSkillLevel($userid,25)*3)/100);
                        $inc=$inc+($inc*$specialnumber);
                    } elseif (in_array($einfo['stat'], array('dungeon', 'infirmary'))) {
                        $EndTime = $db->fetch_single($db->query("/*qc=on*/SELECT `{$einfo['stat']}_out` FROM `{$einfo['stat']}` WHERE `{$einfo['stat']}_user` = {$userid}"));
                        $inc = round((($EndTime - $Time) / 100 * $einfo['inc_amount']) / 60);
                        //Item Potency
                        $specialnumber=((getSkillLevel($userid,25)*3)/100);
                        $inc=$inc+($inc*$specialnumber);
                    } else {
                        //Item Potency
                        $specialnumber=((getSkillLevel($userid,25)*3)/100);
                        $inc = round($ir[$einfo['stat']] / 100 * $einfo['inc_amount']);
						$inc=$inc+($inc*$specialnumber);
                    }
                } else {
                    $inc = $einfo['inc_amount'];
                    //Item Potency
                    $specialnumber=((getSkillLevel($userid,25)*3)/100);
                    $inc=$inc+($inc*$specialnumber);
                }
                if ($einfo['dir'] == "pos") {
                    if (in_array($einfo['stat'], array('energy', 'will', 'brave', 'hp'))) {
                        $ir[$einfo['stat']] = min($ir[$einfo['stat']] + $inc, $ir['max' . $einfo['stat']]);
                    } elseif ($einfo['stat'] == 'infirmary') {
                        put_infirmary($userid, $inc, 'Item Misuse');
                    } elseif ($einfo['stat'] == 'dungeon') {
                        put_dungeon($userid, $inc, 'Item Misuse');
                    } else {
                        $ir[$einfo['stat']] += $inc;
                    }
                } else {
                    if ($einfo['stat'] == 'infirmary') {
                        if (user_infirmary($userid) == true) {
                            remove_infirmary($userid, $inc);
                        }
                    } elseif ($einfo['stat'] == 'dungeon') {
                        if (user_dungeon($userid) == true) {
                            remove_dungeon($userid, $inc);
                        }
                    } else {
                        $ir[$einfo['stat']] = max($ir[$einfo['stat']] - $inc, 0);
                    }
                }
                if (!(in_array($einfo['stat'], array('dungeon', 'infirmary')))) {
                    $upd = $ir[$einfo['stat']];
                }
                if (in_array($einfo['stat'], array('strength', 'agility', 'guard', 'labor', 'iq', 'luck'))) {
                    $db->query("UPDATE `userstats` SET `{$einfo['stat']}` = '{$upd}' WHERE `userid` = {$userid}");
                } elseif (!(in_array($einfo['stat'], array('dungeon', 'infirmary')))) {
                    $db->query("UPDATE `users` SET `{$einfo['stat']}` = '{$upd}' WHERE `userid` = {$userid}");
                }
            }
        }
        if (getSkillLevel($userid,28) != 0)
        {
            if (Random(1,20) == 1)
            {
                $api->UserInfoSet($userid, 'energy', Random(1,5), true);
            }
        }
        alert('success', "Success!", "You have successfully used your {$r['itmname']}!", true, "itemuse.php?item={$_GET['item']}", "Use Another");
      $api->UserTakeItem($userid, $r['inv_itemid'], 1);
      $api->SystemLogsAdd($userid, 'itemuse', "Used {$r['itmname']}.");
    }
    }
$h->endpage();