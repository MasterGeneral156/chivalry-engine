<?php
/*
	File:		smelt.php
	Created: 	4/5/2016 at 12:26AM Eastern Time
	Info: 		Allows players to view their possible crafting recipes,
				requirements for those recipes, and create those items.
	Author:		TheMasterGeneral
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
*/
require('globals.php');
echo "<h3>{$lang['SMELT_HOME']}</h3><hr />";
if (!isset($_GET['action']))
{
    $_GET['action'] = '';
}
switch ($_GET['action'])
{
case 'smelt':
    smelt();
    break;
default:
    home();
    break;
}
function home()
{
	global $db,$userid,$api,$lang,$h;
	$q=$db->query("SELECT * FROM `smelt_recipes` ORDER BY `smelt_id` ASC");
	echo "<table class='table table-bordered table-striped'>
	<tr>
		<th>
			{$lang['SMELT_TH']}
		</th>
		<th>
			{$lang['SMELT_TH1']}
		</th>
		<th>
			{$lang['SMELT_TH2']}
		</th>
	</tr>";
	while ($r=$db->fetch_row($q))
	{
		$output_item=$api->SystemItemIDtoName($r['smelt_output']);
		$items_needed = '';
		$can_craft = TRUE;
		$ex = explode(",", $r['smelt_items']);
		$qty = explode(",", $r['smelt_quantity']);
		$n = 0;
		echo "
		<tr>
			<td>
				{$output_item} x {$r['smelt_qty_output']}
			</td>
			<td>";
				$n = 0;
				 foreach($ex as $i) 
				 {
					$get_items_needed = $db->query("SELECT `itmname` FROM `items` WHERE `itmid`={$i}");
					$t = $db->fetch_row($get_items_needed);
					 
					$do_they_have = $db->query("SELECT `inv_itemid` FROM `inventory` WHERE `inv_userid`={$userid} AND `inv_itemid`={$i} AND `inv_qty`>={$qty[$n]}");
					if($db->num_rows($do_they_have) == 0)
					{
						$t['itmname'] = "<span style='color:red;'>".$t['itmname']."</span>";
						$can_craft = FALSE;
					}
					$items_needed .= $t['itmname'] ." x ". $qty[$n] ." ({$lang['GEN_HAVE']} " . number_format($api->UserCountItem($userid,$i)) . ")<br />";
					$n++;
				  }
				unset($n);
				echo "{$items_needed}
			</td>
			<td>";
				if($can_craft == TRUE) 
					{ 
						echo "<a href='?action=smelt&id={$r['smelt_id']}'>{$lang['SMELT_DO']}</a>"; 
					}
					else
					{ 
						echo "<span style='color:red;'>{$lang['SMELT_DONT']}</span>"; 
					}
				echo"
			</td>
		</tr>";
	}
	echo "</table>";
}
function smelt()
{
	global $db,$userid,$api,$lang,$h;
	$_GET['id'] = (isset($_GET['id']) && is_numeric($_GET['id']))  ? abs($_GET['id']) : 0;
	$q=$db->query("SELECT * FROM `smelt_recipes` WHERE `smelt_id` = {$_GET['id']}");
	if($db->num_rows($q) == 0) 
	{
        alert('danger',$lang['ERROR_GENERIC'],$lang['SMELT_ERR'],true,"smelt.php");
        die($h->endpage());
    }
	$r = $db->fetch_row($q);
	$can_craft = TRUE;
	$needs='';
	$items_needed='';
	$ex = explode(",", $r['smelt_items']);
	$qty = explode(",", $r['smelt_quantity']);
	$n = 0;
	foreach($ex as $i) 
	{
		$get_items_needed = $db->query("SELECT `itmname` FROM `items` WHERE `itmid`={$i}");
		$t = $db->fetch_row($get_items_needed);
		$do_they_have = $db->query("SELECT `inv_itemid` FROM `inventory` WHERE `inv_userid`={$userid} AND `inv_itemid`={$i} AND `inv_qty`>={$qty[$n]}");
		if($db->num_rows($do_they_have) == 0) 
		{
			$needs = $t['itmname']." x ". $qty[$n];
			$can_craft = FALSE;
		}
		$items_needed .= $needs .",";
		$n++;
	}
	if(isset($item_needed)) 
	{
		alert('danger',$lang['ERROR_GENERIC'],$lang['SMELT_ERR1'],true,"smelt.php");
		die($h->endpage());
	}
	unset($n);
	if ($can_craft)
	{
		if ($r['smelt_time'] > 0)
		{
			$rcomplete=time() + $r['smelt_time'];
			$db->query("INSERT INTO `smelt_inprogress` (
				`sip_user`, `sip_recipe`, `sip_time`) 
				VALUES ('{$userid}', '{$_GET['id']}', '{$rcomplete}');");
			alert('success',$lang['ERROR_SUCCESS'],$lang['SMELT_SUCC'],true,"smelt.php");
		}
		else
		{
			alert('success',$lang['ERROR_SUCCESS'],$lang['SMELT_SUCC1'],true,"smelt.php");
			$api->UserGiveItem($userid,$r['smelt_output'],$r['smelt_qty_output']);
		}
		$ex = explode(",", $r['smelt_items']);
		$qty = explode(",", $r['smelt_quantity']);
		$n = 0;
		foreach($ex as $i)
		{
             $api->UserTakeItem($userid, $i, $qty[$n]);
             $n++;
		} 
		unset($n);
		unset($ex);
		unset($qty);
		die($h->endpage());
	}
	else
	{
		alert('danger',$lang['ERROR_GENERIC'],$lang['SMELT_ERR1'],true,"smelt.php");
		die($h->endpage());
	}
}
$h->endpage();