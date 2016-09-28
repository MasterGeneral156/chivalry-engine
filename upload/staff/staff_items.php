<?php
/*
	File: staff/staff_items.php
	Created: 6/1/2016 at 6:06PM Eastern Time
	Info: Allows admins to interact with the items in the game.
	Author: TheMasterGeneral
	Website: http://mastergeneral156.pcriot.com/
*/
require('sglobals.php');
echo "<h3>Items</h3><hr />";
if (!isset($_GET['action']))
{
    $_GET['action'] = '';
}
switch ($_GET['action'])
{
case "create":
    create();
    break;
case "createitmgroup":
    createitmgroup();
    break;
case "delete":
    deleteitem();
    break;
case "edit":
    edititem();
    break;
default:
    home();
    break;
}
function home()
{
	global $h,$lang;
	echo"
	<table class='table table-bordered'>
		<tr>
			<td>
				<a href='?action=create'>Create an Item</a>
			</td>
		</tr>
		<tr>
			<td>
				<a href='?action=delete'>Delete an Item</a>
			</td>
		</tr>
		<tr>
			<td>
				<a href='?action=edit'>Edit an Item</a>
			</td>
		</tr>
		<tr>
			<td>
				<a href='?action=createitmgroup'>Create an Item Group</a>
			</td>
		</tr>
	</table>";
	$h->endpage();
}
function create()
{
	global $db,$ir,$h,$lang;
	if ($ir['user_level'] != 'Admin')
    {
        alert('danger','No Permission!','You have no permission to be here. If this is false, please contact an admin for help!');
        die($h->endpage());
    }
	if (!isset($_POST['itemname']))
	{
		$csrf = request_csrf_html('staff_newitem');
		echo "<form method='post'>
		<table class='table table-bordered'>
			<tr>
				<th width='33%'>
					Item Name
				</th>
				<td>
					<input type='text' required='1' name='itemname' class='form-control'>
				</td>
			</tr>
			<tr>
				<th width='33%'>
					Item Description
				</th>
				<td>
					<input type='text' required='1' name='itemdesc' class='form-control'>
				</td>
			</tr>
			<tr>
				<th width='33%'>
					Item Type
				</th>
				<td>
					" . itemtype_dropdown('itmtype') . "
				</td>
			</tr>
			<tr>
				<th width='33%'>
					Item Buyable?
				</th>
				<td>
					<input type='checkbox' class='form-control' checked='checked' name='itembuyable'>
				</td>
			</tr>
			<tr>
				<th width='33%'>
					Item Buy Price
				</th>
				<td>
					<input type='number' required='1' name='itembuy' min='0' class='form-control'>
				</td>
			</tr>
			<tr>
				<th width='33%'>
					Item Sell Price
				</th>
				<td>
					<input type='number' required='1' name='itemsell' min='0' class='form-control'>
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<h4>Item Usage</h4>
				</td>
			</tr>";
			for ($i = 1; $i <= 3; $i++)
			{
				echo "
				<tr>
					<th>
						<b><u>Effect {$i}</u></b>
					</th>
					<td>
					<b>On?</b>
						<input type='radio' class='form-control' name='effect{$i}on' value='1' /> Yes
						<input type='radio' class='form-control' name='effect{$i}on' value='0' checked='checked' /> No
					<br />
					<b>Stat:</b> <select name='effect{$i}stat' type='dropdown' class='form-control'>
						<option value='energy'>Energy</option>
						<option value='will'>Will</option>
						<option value='brave'>Brave</option>
						<option value='hp'>Health</option>
						<option value='strength'>Strength</option>
						<option value='agility'>Agility</option>
						<option value='guard'>Guard</option>
						<option value='labor'>Labor</option>
						<option value='IQ'>IQ</option>
						<option value='infirmary'>Infirmary Time</option>
						<option value='dungeon'>Dungeon Time</option>
						<option value='primary_currency'>Primary Currency</option>
						<option value='secondary_currency'>Secondary Currency</option>
						<option value='cdays'>Education Days Left</option>
						<option value='crimexp'>Crime XP</option>
					</select>
					<br />
					<b>Direction:</b> <select name='effect{$i}dir' class='form-control' type='dropdown'>
						<option value='pos'>Increase</option>
						<option value='neg'>Decrease</option>
					</select>
					<br />
					<b>Amount:</b> <input type='number' min='0' class='form-control' name='effect{$i}amount' value='0' />
					<select name='effect{$i}type' class='form-control' type='dropdown'>
						<option value='figure'>Value</option>
						<option value='percent'>Percent</option>
					</select>
					</td>
				</tr>";
			}
			
			echo"
			<tr>
				<td colspan='2'>
					<h4>Combat Usage</h4>
				</td>
			</tr>
			<tr>
				<th>
					Weapon Power
				</th>
				<td>
					<input type='number' required='1' class='form-control' name='weapon' min='0' value='0' />
				</td>
			</tr>
			<tr>
				<th>
					Armor Power
				</th>
				<td>
					<input type='number' required='1' class='form-control' name='armor' min='0' value='0' />
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<input type='submit' value='Create Item' class='btn btn-default'>
				</td>
			</tr>
		</table>
		{$csrf}
		</form>";
	}
	else
	{
		if (!isset($_POST['verf']) || !verify_csrf_code('staff_newitem', stripslashes($_POST['verf'])))
		{
			csrf_error('create');
		}
		$itmname = (isset($_POST['itemname']) && preg_match("/^[a-z0-9_]+([\\s]{1}[a-z0-9_]|[a-z0-9_])+$/i", $_POST['itemname'])) ? $db->escape(strip_tags(stripslashes($_POST['itemname']))) : '';
		$itmdesc = (isset($_POST['itemdesc'])) ? $db->escape(strip_tags(stripslashes($_POST['itemdesc']))) : '';
		$weapon = (isset($_POST['weapon']) && is_numeric($_POST['weapon'])) ? abs(intval($_POST['weapon'])) : 0;
		$armor = (isset($_POST['armor']) && is_numeric($_POST['armor'])) ? abs(intval($_POST['armor'])) : 0;
		$itmtype = (isset($_POST['itmtype']) && is_numeric($_POST['itmtype'])) ? abs(intval($_POST['itmtype'])) : '';
		$itmbuyprice = (isset($_POST['itembuy']) && is_numeric($_POST['itembuy'])) ? abs(intval($_POST['itembuy'])) : 0;
		$itmsellprice = (isset($_POST['itemsell']) && is_numeric($_POST['itemsell'])) ? abs(intval($_POST['itemsell'])) : 0;
		if (!isset($itmname) || !isset($itmdesc) || !isset($itmtype) || !isset($itmbuyprice) || !isset($itmsellprice))
		{
			alert('danger',"Missing Inputs!","You are missing one of the required fields. Please go back and try again.");
			die($h->endpage());
		}
		$inq=$db->query("SELECT `itmid` FROM `items` WHERE `itmname` = '{$itmname}'");
		if ($db->num_rows($inq) > 0)
		{
			$db->free_result($inq);
			alert("danger","Item Already Exists!","An item with that name already exists. Go back and choose a different name.");
			die($h->endpage());
		}
		$q=$db->query("SELECT `itmtypeid` FROM `itemtypes` WHERE `itmtypeid` = '{$itmtype}'");
		if ($db->num_rows($q) == 0)
		{
			$db->free_result($q);
			alert("danger","Uh oh!","The item group you specified does not exist. Go back and try again, please.");
			die($h->endpage());
		}
		$itmbuy = ($_POST['itembuyable'] == 'on') ? 1 : 0;
		for ($i = 1; $i <= 3; $i++)
		{
			$efxkey = "effect{$i}";
			$_POST[$efxkey . 'stat'] =
					(isset($_POST[$efxkey . 'stat'])
							&& in_array($_POST[$efxkey . 'stat'],
									array('energy', 'will', 'brave', 'hp',
											'strength', 'agility', 'guard',
											'labor', 'IQ', 'infirmary', 'dungeon',
											'primary_currency', 'secondary_currency', 'cdays', 'crimexp')))
							? $_POST[$efxkey . 'stat'] : 'energy';
			$_POST[$efxkey . 'dir'] =
					(isset($_POST[$efxkey . 'dir'])
							&& in_array($_POST[$efxkey . 'dir'],
									array('pos', 'neg'))) ? $_POST[$efxkey . 'dir']
							: 'pos';
			$_POST[$efxkey . 'type'] =
					(isset($_POST[$efxkey . 'type'])
							&& in_array($_POST[$efxkey . 'type'],
									array('figure', 'percent')))
							? $_POST[$efxkey . 'type'] : 'figure';
			$_POST[$efxkey . 'amount'] =
					(isset($_POST[$efxkey . 'amount'])
							&& is_numeric($_POST[$efxkey . 'amount']))
							? abs(intval($_POST[$efxkey . 'amount'])) : 0;
			$_POST[$efxkey . 'on'] =
					(isset($_POST[$efxkey . 'on'])
							&& in_array($_POST[$efxkey . 'on'], array('1', '0')))
							? $_POST[$efxkey . 'on'] : 0;
			$effects[$i] =
					$db->escape(
							serialize(
									array("stat" => $_POST[$efxkey . 'stat'],
											"dir" => $_POST[$efxkey . 'dir'],
											"inc_type" => $_POST[$efxkey . 'type'],
											"inc_amount" => abs(
													(int) $_POST[$efxkey
															. 'amount']))));
		}
		$m =
            $db->query(
                    "INSERT INTO `items`
						VALUES(NULL, '{$itmtype}', '{$itmname}', '{$itmdesc}',
                     {$itmbuyprice}, {$itmsellprice}, '{$itmbuy}', 
					 '{$_POST['effect1on']}', '{$effects[1]}',
                     '{$_POST['effect2on']}', '{$effects[2]}',
                     '{$_POST['effect3on']}', '{$effects[3]}', 
					 {$weapon}, {$armor})");
		stafflog_add("Created item {$itmname}.");
		alert('success',"Success!","You have successfully created an item called {$itmname}.");
	}
	$h->endpage();
}
function createitmgroup()
{
	global $db,$lang,$h,$ir;
	if ($ir['user_level'] != 'Admin')
    {
        alert('danger','No Permission!','You have no permission to be here. If this is false, please contact an admin for help!');
        die($h->endpage());
    }
	if (!isset($_POST['name']))
	{
		$csrf = request_csrf_html('staff_newitemtype');
		echo "
        <h4>Adding an item type...</h4>
		<form method='post'>
			<table class='table table-bordered'>
			<tr>
				<th width='33%'>
					Item Group Name
				</th>
				<td>
					<input type='text' class='form-control' required='1' name='name' />
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<input type='submit' class='btn btn-default' value='Add Item Group' />
				</td>
			</tr>
        	{$csrf}
			</table>
		</form>
           ";
	}
	else
	{
		$name = (isset($_POST['name']) && preg_match("/^[a-z0-9_]+([\\s]{1}[a-z0-9_]|[a-z0-9_])+$/i", $_POST['name'])) ? $db->escape(strip_tags(stripslashes($_POST['name']))) : '';
		if (!isset($_POST['verf']) || !verify_csrf_code('staff_newitemtype', stripslashes($_POST['verf'])))
		{
			alert('danger',"{$lang["CSRF_ERROR_TITLE"]}","{$lang["CSRF_ERROR_TEXT"]}");
			die($h->endpage());
		}
		$q=$db->query("SELECT `itmtypeid` FROM `itemtypes` WHERE `itmtypename` = '{$name}'");
		if ($db->num_rows($q) > 0)
		{
			$db->free_result($q);
			alert("danger","Already Exists!","An item group with that name already exists. Please go back and enter a new name.");
			die($h->endpage());
		}
		stafflog_add("Added item type {$name}.");
		alert('success',"Success!","You have successfully created an item group called {$name}.");
		$db->query("INSERT INTO `itemtypes` VALUES(NULL, '{$_POST['name']}')");
		
	}
	$h->endpage();
}
function deleteitem()
{
	global $db,$ir,$h,$lang;
	if ($ir['user_level'] != 'Admin')
    {
        alert('danger','No Permission!','You have no permission to be here. If this is false, please contact an admin for help!');
        die($h->endpage());
    }
	if (!isset($_POST['item']))
	{
		$csrf = request_csrf_html('staff_killitem');
		echo "<h4>Deleting an Item</h4>
		The item you select will be deleted permanently. There isn't a confirmation prompt, so be 100% sure.
		<form method='post'>
			<table class='table table-bordered'>
				<tr>
					<th width='33%'>
						Item
					</th>
					<td>
						" . item_dropdown('item') . "
					</td>
				</tr>
				<tr>
					<td colspan='2'>
						<input type='submit' class='btn btn-default' value='Delete Item'>
					</td>
				</tr>
			</table>
			{$csrf}
		</form>";
	}
	else
	{
		if (!isset($_POST['verf']) || !verify_csrf_code('staff_killitem', stripslashes($_POST['verf'])))
		{
			alert('danger',"{$lang["CSRF_ERROR_TITLE"]}","{$lang["CSRF_ERROR_TEXT"]}");
			die($h->endpage());
		}
		$_POST['item'] =(isset($_POST['item']) && is_numeric($_POST['item'])) ? abs(intval($_POST['item'])) : '';
		if (empty($_POST['item']))
		{
			alert('warning','Empty Input!','You did not specify an item to delete. Go back and try again.');
			die($h->endpage());
		}
		$d =
            $db->query(
                    "SELECT `itmname`
                     FROM `items`
                     WHERE `itmid` = {$_POST['item']}");
		if ($db->num_rows($d) == 0)
		{
			$db->free_result($d);
			alert('danger',"Uh oh!","The item you chose to delete does not exist!");
			die($h->endpage());
		}
		$itemname = $db->fetch_single($d);
		$db->free_result($d);
		$db->query("DELETE FROM `items` WHERE `itmid` = {$_POST['item']}");
		$db->query("DELETE FROM `inventory` WHERE `inv_itemid` = {$_POST['item']}");
		stafflog_add("Deleted item {$itemname}");
		alert("success","Success!","The Item ({$itemname}) has been deleted from the game successfully.");
		die($h->endpage());
	}
}