<?php
/*
	File: class/class_api.php
	Created: 11/10/2016 at 1:34PM Eastern Time
	Info: Creates a class file to use as an API for modders
	who don't wish to use the main game code!
	Author: TheMasterGeneral
	Website: https://github.com/MasterGeneral156/chivalry-engine
*/
if (!defined('MONO_ON')) {
    exit;
}

class user
{
	/*
        Tests to see if specified user has at least the specified amount of money.
        @param int user = User ID to test for.
        @param text type = Currency type. [Ex. primary or secondary]
        @param int money = Minimum money requied.
        Returns true if user has more cash than required.
        Returns false if user does not exist or does not have the minimum cash requred.
    */
    function hasCurrency($user, $type, $minimum)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $minimum = (isset($minimum) && is_numeric($minimum)) ? abs(intval($minimum)) : 0;
        $type = $db->escape(stripslashes(strtolower($type)));
        $userexist = $db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$user}"));
        if ($userexist) {
            if ($type == 'primary' || $type == 'secondary') {
                $UserMoney = $db->fetch_single($db->query("SELECT `{$type}_currency` FROM `users` WHERE `userid` = {$user}"));
                if ($UserMoney >= $minimum) {
                    return true;
                }
            }
        }
    }
	 /*
        Gives the user the specified item and quantity
        @param int user = User ID to test for.
        @param int item = Item ID to give to the user.
        @param int quantity = Quantity of item to give to the user.
        Returns true if item successfully given to the user.
        Returns false if item failed to be given to user.
    */
    function giveItem($user, $item, $quantity)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $item = (isset($item) && is_numeric($item)) ? abs(intval($item)) : 0;
        $quantity = (isset($quantity) && is_numeric($quantity)) ? abs(intval($quantity)) : 0;
        if (item_add($user, $item, $quantity)) {
            return true;
        }
    }
	/*
        Removes an item from the user specified
        @param int user = User ID to test for.
        @param int item = Item ID to take from the user.
        @param int quantity = Quantity of item to remove from the user.
        Returns true if item successfully taken from the user.
        Returns false if item failed to be taken from user.
    */
    function takeItem($user, $item, $quantity)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $item = (isset($item) && is_numeric($item)) ? abs(intval($item)) : 0;
        $quantity = (isset($quantity) && is_numeric($quantity)) ? abs(intval($quantity)) : 0;
        if (item_remove($user, $item, $quantity)) {
            return true;
        }
    }
    /*
        Gives user specified amount of currency type.
        @param int user = User ID to give currency to.
        @param int type = Currency type. [Ex. primary and secondary]
        @param int money = Currency given.
        Returns true if user has received currency.
        Returns false if user does not receive currency.
    */
    function giveCurrency($user, $type, $quantity)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $type = $db->escape(stripslashes(strtolower($type)));
        $quantity = (isset($quantity) && is_numeric($quantity)) ? abs(intval($quantity)) : 0;
        $userexist = $db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$user}"));
        if ($userexist) {
            if ($type == 'primary' || $type == 'secondary') {
                $db->query("UPDATE `users` SET `{$type}_currency` = `{$type}_currency` + {$quantity} WHERE `userid` = {$user}");
                return true;
            }
        }
    }
    /*
        Takes qunatity of currency type from the user specified.
        @param int user = User ID to give currency to.
        @param int type = Currency type. [Ex. primary and secondary]
        @param int money = Currency given.
        Returns true if user has lost currency.
        Returns false if user does not lose any currency.
    */
    function takeCurrency($user, $type, $quantity)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $type = $db->escape(stripslashes(strtolower($type)));
        $quantity = (isset($quantity) && is_numeric($quantity)) ? abs(intval($quantity)) : 0;
        $userexist = $db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$user}"));
        if ($userexist) {
            if ($type == 'primary' || $type == 'secondary') {
                $db->query("UPDATE `users` SET `{$type}_currency` = `{$type}_currency` - {$quantity} WHERE `userid` = {$user}");
                $db->query("UPDATE `users` SET `{$type}_currency` = 0 WHERE `{$type}_currency` < 0");
                return true;
            }
        }
    }
    /*
        Tests to see what the user has equipped.
        @param int user = User ID to test against.
        @param int slot = Equipment slot to test. [Ex. Primary, Secondary, Armor]
        @param int itemid = Item to test for. -1 = Any Item, 0 = No Item Equipped, >0 = Specific item
        Returns true if user has item equipped
        Returns false if user does not have item equipped.
    */
    function itemEquipped($user, $slot, $itemid = -1)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $slot = $db->escape(stripslashes(strtolower($slot)));
        if ($slot == 'primary' || $slot == 'secondary' || $slot == 'armor') {
            //Any item equipped
            if ($itemid == -1) {
                $equipped = $db->fetch_single($db->query("SELECT `equip_{$slot}` FROM `users` WHERE `userid` = {$user}"));
                if ($equipped > 0) {
                    return true;
                }
            } //Specific item equipped
            elseif ($itemid > 0) {
                $itemid = (isset($itemid) && is_numeric($itemid)) ? abs(intval($itemid)) : 0;
                $equipped = $db->fetch_single($db->query("SELECT `equip_{$slot}` FROM `users` WHERE `userid` = {$user}"));
                if ($equipped == $itemid) {
                    return true;
                }
            } //Nothing equipped
            elseif ($itemid == 0) {
                $equipped = $db->fetch_single($db->query("SELECT `equip_{$slot}` FROM `users` WHERE `userid` = {$user}"));
                if ($equipped == 0) {
                    return true;
                }
            }
        }
    }
	/*
        Tests the inputted user to see if they're in the infirmary
        @param int user = User ID to test against.
        Returns true if user is in the infirmary
        Returns false if user is not in the infirmary
    */
    function inInfirmary($user)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        return user_infirmary($user);
    }
	/*
        Tests the inputted user to see if they're in the dungeon
        @param int user = User ID to test against.
        Returns true if user is in the dungeon.
        Returns false if user is not in the dungeon.
    */
    function inDungeon($user)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        return user_dungeon($user);
    }
	/*
        Places or removes infirmary time on the specified user.
        @param int user = User ID to test against.
        @param int time = Minutes user is in infirmary
        @param text reason = Reason why user is in the infirmary
        Returns true if user is placed in the infirmary, or is removed from it.
        Returns false otherwise.
    */
    function setInfirmary($user, $time, $reason)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $reason = $db->escape(stripslashes($reason));
		if ($time >= 0) {
			$time = (isset($time) && is_numeric($time)) ? abs(intval($time)) : 0;
			put_infirmary($user, $time, $reason);
			return true;
		} elseif ($time < 0) {
			$time = (isset($time) && is_numeric($time)) ? abs(intval($time)) : 0;
			remove_infirmary($user, $time);
			return true;
		}
    }
	/*
        Places or removes dungeon/infirmary time on the specified user.
        @param int user = User ID to test against.
        @param int time = Minutes user is in dungeon.
        @param text reason = Reason why user is in the dungeon.
        Returns true if user is placed in the dungeon, or is removed from it.
        Returns false otherwise.
    */
	function setDungeon($user, $time, $reason)
    {
        global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $reason = $db->escape(stripslashes($reason));
		if ($time >= 0) {
			$time = (isset($time) && is_numeric($time)) ? abs(intval($time)) : 0;
			put_dungeon($user, $time, $reason);
			return true;
		} elseif ($time < 0) {
			$time = (isset($time) && is_numeric($time)) ? abs(intval($time)) : 0;
			remove_dungeon($user, $time);
			return true;
		}
    }
	/*
     * Function to simulate a user training.
     * @param int userid = User ID of the player you wish to simular.
     * @param text stat = Stat you wish for the user to train.
     * @param int times = How much you wish the user to train.
     * Returns stats gained.
     */
    function train($userid, $stat, $times, $multiplier = 1)
    {
        global $db;
        $userid = (isset($userid) && is_numeric($userid)) ? abs(intval($userid)) : 0;
        $stat = $db->escape(stripslashes(strtolower($stat)));
        $times = (isset($times) && is_numeric($times)) ? abs(intval($times)) : 0;
        $multiplier = (isset($multiplier) && is_numeric($multiplier)) ? abs(intval($multiplier)) : 1;
        //Return empty if the call isn't complete.
        if (empty($userid) || (empty($stat)) || (empty($times))) {
            return 0;
        }
        $StatArray = array("strength", "agility", "guard", "labor", "iq");
        if (!in_array($stat, $StatArray)) {
            return -1;
        }
        $udq = $db->query("SELECT * FROM `users` WHERE `userid` = {$userid}");
        $userdata = $db->fetch_row($udq);
        $gain = 0;
        //Do while value is less than the user's energy input, then add one to value.
        for ($i = 0; $i < $times; $i++) {
            //(1-4)/(600-1000)*(500-1000)*((User's Will+25)/175)
            $gain +=
                Random(1, 4) / Random(600, 1000) * Random(500, 1000) * (($userdata['will'] + 25) / 175);
            //Subtract a random number from user's will.
            $userdata['will'] -= Random(1, 3);
            //User's will ends up negative, set to zero.
            if ($userdata['will'] < 0) {
                $userdata['will'] = 0;
            }
        }
        //User's class is warrior
        if ($userdata['class'] == 'Warrior') {
            //Trained stat is strength, double its output.
            if ($stat == 'strength') {
                $gain *= 2;
            }
            //Trained stat is guard, half its output.
            if ($stat == 'guard') {
                $gain /= 2;
            }
        }
        //User's class is Rogue.
        if ($userdata['class'] == 'Rogue') {
            //Trained stat is agility, double its output.
            if ($stat == 'agility') {
                $gain *= 2;
            }
            //Trained stat is strength, half its output.
            if ($stat == 'strength') {
                $gain /= 2;
            }
        }
        //User's class is Defender.
        if ($userdata['class'] == 'Defender') {
            //Trained stat is guard, double its output.
            if ($stat == 'guard') {
                $gain *= 2;
            }
            //Trained stat is agility, half its output.
            if ($stat == 'agility') {
                $gain /= 2;
            }
        }
        //Add multiplier, if needed.
        $gain *= $multiplier;
        //Round the gained stats.
        $gain = floor($gain);
        //Update the user's stats.
        $db->query("UPDATE `userstats`
                    SET `{$stat}` = `{$stat}` + {$gain}
                    WHERE `userid` = {$userid}");
        //Update user's will and energy.
        $db->query("UPDATE `users`
                    SET `will` = {$userdata['will']},
                    `energy` = `energy` - {$times}
                    WHERE `userid` = {$userid}");
        return $gain;
    }
	 /*
        Get the user's member level. Can test for exact member level, or if user is above specified member level.
        @param int user = User to test on.
        @param text level = Member level to test for. [Valid: npc, member, web dev, forum moderator, assistant, admin]
        @param boolean exact = Return true if ranked ONLY specified level. [Default: false]
        Returns true if user is exactly or equal to/above specified member level. False if not.
    */

    //This function needs refactored ASAP
    function getStaffLevel($user, $level, $exact = false)
    {
        global $db;
        $level = $db->escape(stripslashes(strtolower($level)));
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        if ($user > 0) {
            $userexist = $db->query("SELECT `userid` FROM `users` WHERE `userid` =  {$user}");
            if ($db->num_rows($userexist) > 0) {
                $ulevel = $db->fetch_single($db->query("SELECT `user_level` FROM `users` WHERE `userid` = {$user}"));
                if ($exact == true) {
                    if ($level == $ulevel) {
                        return true;
                    }
                } else {
                    if ($level == 'member') {
                        if ($ulevel == 'Member' || $ulevel == 'Forum Moderator' || $ulevel == 'Assistant'
                            || $ulevel == 'Web Developer' || $ulevel == 'Admin'
                        ) {
                            return true;
                        }
                    } elseif ($level == 'forum moderator') {
                        if ($ulevel == 'Forum Moderator' || $ulevel == 'Assistant' || $ulevel == 'Web Developer' || $ulevel == 'Admin') {
                            return true;
                        }
                    } elseif ($level == 'assistant') {
                        if ($ulevel == 'Assistant' || $ulevel == 'Web Developer' || $ulevel == 'Admin') {
                            return true;
                        }
                    } elseif ($level == 'web dev') {
                        if ($ulevel == 'Web Developer' || $ulevel == 'Admin') {
                            return true;
                        }
                    } elseif ($level == 'npc') {
                        if ($ulevel == 'Member' || $ulevel == 'NPC' || $ulevel == 'Forum Moderator' || $ulevel == 'Assistant'
                            || $ulevel == 'Web Developer' || $ulevel == 'Admin'
                        ) {
                            return true;
                        }
                    } elseif ($level == 'admin') {
                        if ($ulevel == 'Admin') {
                            return true;
                        }
                    }
                }
            }
        }
    }
	/*
        Set the specified user's stat to a value.
        @param int user = User to test on.
        @param text stat = User's table row to return.
        @param int change = Numeric change (as a value)
        Returns the value in the stat specified.
        Throws E_ERROR if attempting to edit a sensitive field (Such as passwords)
    */
	function setInfo($user, $stat, $change)
	{
		global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $stat = $db->escape(stripslashes(strtolower($stat)));
		$change = (isset($change) && is_numeric($change)) ? intval($change) : 0;
        if (in_array($stat, array('password', 'email', 'lastip', 'loginip', 'registerip', 'personal_notes', 'staff_notes')))
		{
            trigger_error("You do not have permission to set the {$stat} on this user.", E_ERROR);
        } 
		else
		{
			$db->query("UPDATE users SET `{$stat}` = `{$stat}` + {$change} WHERE `userid` = {$user}");
			$db->query("UPDATE users SET `{$stat}` = `max{$stat}` WHERE `{$stat}` > `max{$stat}`");
			return true;
		}
	}
	/*
        Set the specified user's stat to a percent.
        @param int user = User to test on.
        @param text stat = User's table row to return.
        @param int change = Numeric change (as percent)
        Returns the value in the stat specified.
        Throws E_ERROR if attempting to edit a sensitive field (Such as passwords)
    */
	function setInfoPercent($user, $stat, $change)
	{
		global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $stat = $db->escape(stripslashes(strtolower($stat)));
		$change = (isset($change) && is_numeric($change)) ? intval($change) : 0;
        if (in_array($stat, array('password', 'email', 'lastip', 'loginip', 'registerip', 'personal_notes', 'staff_notes')))
		{
            trigger_error("You do not have permission to set the {$stat} on this user.", E_ERROR);
        } 
		else
		{
			$maxstat = $db->fetch_single($db->query("SELECT `max{$stat}` FROM `users` WHERE `userid` = {$user}"));
			$number = ($change / 100) * $maxstat;
			$db->query("UPDATE users SET `{$stat}`=`{$stat}`+{$number} WHERE `{$stat}` < `max{$stat}`");
			$db->query("UPDATE users SET `{$stat}` = `max{$stat}` WHERE `{$stat}` > `max{$stat}`");
			return true;
		}
	}
	/*
        Returns the specified user's stat
        @param int user = User to test on.
        @param text stat = User's table row to return.
        Returns the value in the stat specified.
		Throws E_ERROR if attempting to edit a sensitive field (Such as passwords)
    */
	function getInfo($user, $stat)
	{
		global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $stat = $db->escape(stripslashes(strtolower($stat)));
        if (in_array($stat, array('password', 'email', 'lastip', 'loginip', 'registerip', 'personal_notes', 'staff_notes')))
		{
            trigger_error("You do not have permission to get the {$stat} on this user.", E_ERROR);
        }
		else
		{
			return $db->fetch_single($db->query("SELECT `{$stat}` FROM `users` WHERE `userid` = {$user}"));
		}
	}
	/*
        Returns the specified user's stat as a percent
        @param int user = User to test on.
        @param text stat = User's table row to return.
        Returns the value in the stat specified.
		Throws E_ERROR if attempting to edit a sensitive field (Such as passwords)
    */
	function getInfoPercent($user, $stat)
	{
		global $db;
        $user = (isset($user) && is_numeric($user)) ? abs(intval($user)) : 0;
        $stat = $db->escape(stripslashes(strtolower($stat)));
        if (in_array($stat, array('password', 'email', 'lastip', 'loginip', 'registerip', 'personal_notes', 'staff_notes'))) 
		{
            trigger_error("You do not have permission to get the {$stat} on this user.", E_ERROR);
        } 
		else
		{
			$min = $db->fetch_single($db->query("SELECT `{$stat}` FROM `users` WHERE `userid` = {$user}"));
			$max = $db->fetch_single($db->query("SELECT `max{$stat}` FROM `users` WHERE `userid` = {$user}"));
			return round($min / $max * 100);
		}
	}
}