<?php
function setUserShares($userid, $assetID, $costPerShare, $totalShares)
{
    if ($totalShares > 0)
        addUserShares($userid, $assetID, $costPerShare, $totalShares);
    else
        removeUserShares($userid, $assetID, $totalShares);
    assetsOwnedCleanup();
}

function insertUserShares($userid, $assetID, $costPerShare, $totalShares)
{
    global $db;
    $db->query("INSERT INTO `asset_market_owned` 
        (`userid`, `am_id`, `shares_owned`, `shares_cost`) 
        VALUES ('{$userid}', '{$assetID}', '{$totalShares}', '{$costPerShare}')");
}

function addUserShares($userid, $assetID, $costPerShare, $totalShares)
{
    global $db;
    $q = $db->query("SELECT * 
                    FROM `asset_market_owned` 
                    WHERE `userid` = {$userid} 
                    AND `am_id` = {$assetID} 
                    AND `shares_cost` = {$costPerShare}");
    
    if ($db->num_rows($q) == 0)
    {
        insertUserShares($userid, $assetID, $costPerShare, $totalShares);
    }
    else
    {
        $r=$db->fetch_row($q);
        $db->query("UPDATE `asset_market_owned` 
                    SET `shares_owned` = `shares_owned` + {$totalShares} 
                    WHERE `am_id` = {$r['amo_id']}");
    }
}

function removeUserShares($userid, $assetID, $totalShares)
{
    global $db;
    $q = $db->query("SELECT *
                    FROM `asset_market_owned`
                    WHERE `userid` = {$userid}
                    AND `am_id` = {$assetID}");
    $sharesToTake = $totalShares * -1;
    if ($db->num_rows($q) > 0)
    {
        while ($sharesToTake != 0)
        {
            while ($r=$db->fetch_row($q))
            {
                if ($r['shares_owned'] < $sharesToTake)
                {
                    $sharesToTake = $sharesToTake - $r['shares_owned'];
                    $db->query("UPDATE `asset_market_owned` SET `shares_owned` = 0 WHERE `amo_id` = {$r['amo_id']}");
                }
                else 
                {
                    $db->query("UPDATE `asset_market_owned` SET `shares_owned` = `shares_owned` - {$sharesToTake} WHERE `amo_id` = {$r['amo_id']}");
                    $sharesToTake = 0;
                }
            }
        }
    }
}

function assetsOwnedCleanup()
{
    global $db;
    $fiftyDays = (((60 * 60) * 24) * 50);
    $historyDelTime = time() - $fiftyDays;
    $db->query("DELETE FROM `asset_market_owned` WHERE `shares_owned` <= 0");
    $db->query("DELETE FROM `asset_market_history` WHERE `timestamp` < {$historyDelTime}");
}

function runMarketTick($riskLevel)
{
    global $db, $api;
    $q = $db->query("SELECT * FROM `asset_market` WHERE `am_risk` = {$riskLevel}");
    if ($db->num_rows($q) > 0)
    {
        while ($r = $db->fetch_row($q))
        {
            $RNG = Random(1,100);
            $perc = $riskLevel / 100;
            if ($RNG <= 3)   //market crash
            {
                $min = $r['am_cost'] * 0.35;
                $max = $r['am_cost'] * 0.75;
                $change = Random($min, $max) * -1;
            }
            else if ($RNG >= 98) //market bubble
            {
                $min = $r['am_cost'] * 0.10;
                $max = $r['am_cost'] * 0.45;
                $change = Random($min, $max);
            }
            else
            {
                $maxChange = $r['am_cost'] * $perc;
                if ($maxChange < 4)
                    $maxChange == 4;
                $change = Random($maxChange * -1, $maxChange);
            }
            $newVal = clamp(($r['am_cost'] + $change), 0, $r['am_max']);
            //Force sell on crash.
            if ($newVal == 0)
            {
                $q2 = $db->query("SELECT * FROM `asset_market_owned` WHERE `am_id` = {$r['am_id']}");
                $alreadyNotif = array();
                while ($r2 = $db->fetch_row($q2))
                {
                    setUserShares($r2['userid'], $r['am_id'], 0, $r2['shares_owned'] * -1);
                    if (!in_array($r2['userid'], $alreadyNotif))
                    {
                        $notifText = "The {$r['am_name']} asset has crashed and your " . number_format($r2['shares_owned']) . " shares 
                            of {$r['am_name']} have been voided and your original investment is lost";
                        $api->GameAddNotification($r2['userid'], $notifText);
                        array_push($alreadyNotif, $r2['userid']);
                    }
                }
                resetAsset($r['am_id']);
            }
            
            if (($newVal <= $r['am_min']) && ($newVal >= $r['am_min'] - (5 / 100)))
            {
                stockNotifDrop($r['am_id']);
            }
            
            $db->query("UPDATE `asset_market` SET `am_cost` = {$newVal} WHERE `am_id` = {$r['am_id']}");
            logAssetChange($r['am_id'], $r['am_cost'], $change, $newVal);
        }
    }
}

function logAssetChange($assetID, $oldVal, $change, $newVal)
{
    global $db;
    $time = time();
    $db->query("INSERT INTO `asset_market_history` 
                (`am_id`, `old_value`, `difference`, `new_value`, `timestamp`) 
                VALUES ('{$assetID}', '{$oldVal}', '{$change}', '{$newVal}', '{$time}')");
}

function createStockAsset($name, $cost, $change, $risk, $min, $max)
{
    global $db;
    $db->query("INSERT INTO `asset_market` 
        (`am_name`, `am_min`, `am_max`, `am_start`, `am_cost`, `am_change`, `am_risk`) 
        VALUES ('{$name}', '{$min}', '{$max}', '{$cost}', '{$cost}', '{$change}', '{$risk}')");
}

function logAssetProfit($userid, $profit)
{
    global $db;
    $q = $db->query("SELECT * FROM `asset_market_profit` WHERE `userid` = {$userid}");
    if ($db->num_rows($q) == 0)
        $db->query("INSERT INTO `asset_market_profit` (`userid`, `profit`) VALUES ('{$userid}', '{$profit}')");
    else
        $db->query("UPDATE `asset_market_profit` SET `profit` = `profit` + ({$profit}) WHERE `userid` = {$userid}");
}

function returnUserMaxShares($userid)
{
    global $db, $api;
    $level = $api->UserInfoGet($userid, "level");
    $masterRank = $db->fetch_single($db->query("SELECT `reset` FROM `user_settings` WHERE `userid` = {$userid}"));
    
    $ret = 5000;
    $ret = $ret * levelMultiplier($level);
    $ret = $ret * $masterRank;
    
    return $ret;
}

function returnUserAssetShares($userid, $assetID)
{
    global $db;
    $q = $db->query("SELECT SUM(`shares_owned`) FROM `asset_market_owned` WHERE `am_id` = {$assetID} AND `userid` = {$userid}");
    return $db->fetch_single($q);
}

function returnUserAssetCosts($userid, $assetID)
{
    global $db;
    $q = $db->query("SELECT * FROM `asset_market_owned` WHERE `am_id` = {$assetID} AND `userid` = {$userid}");
    $r = $db->fetch_row($q);
    return $r['shares_cost'] * $r['shares_owned'];
}

function calculateUserCurrentAssetValue($userid, $assetID)
{
    global $db;
    $totalShares = returnUserAssetShares($userid, $assetID);
    $currentVal = $db->fetch_single($db->query("SELECT `am_cost` FROM `asset_market` WHERE `am_id` = {$assetID}"));
    return $totalShares * $currentVal;
}

function resetAsset($assetID)
{
    global $db;
    $db->query("UPDATE `asset_market` SET `am_cost` = `am_start` WHERE `am_id` = {$assetID}");
}

function returnUserAllAssetShares($userid)
{
    global $db;
    $q = $db->query("SELECT SUM(`shares_owned`) FROM `asset_market_owned` WHERE `userid` = {$userid}");
    return $db->fetch_single($q);
}

function returnUserAllAssetCosts($userid)
{
    global $db;
    $q = $db->query("SELECT SUM(`shares_cost`) FROM `asset_market_owned` WHERE `userid` = {$userid}");
    return $db->fetch_single($q);
}

function returnUserCurrentValueAllAsset($userid)
{
    global $db;
    $return = 0;
    $q = $db->query("SELECT * FROM `asset_market_owned` WHERE `userid` = {$userid}");
    while ($r = $db->fetch_row($q))
    {
        $q2 = $db->query("SELECT * FROM `asset_market` WHERE `am_id` = {$r['am_id']}");
        while ($r2 = $db->fetch_row($q2))
        {
            $return = $return + ($r2['am_cost'] * $r['shares_owned']);
        }
    }
    return $return;
}

function stockNotifDrop($stock_id)
{
    global $db, $api;
    $q2 = $db->query("SELECT * FROM `asset_market_owned` WHERE `am_id` = {$stock_id}");
    $r['am_name'] = $db->fetch_single($db->query("SELECT `am_name` FROM `asset_market` WHERE `am_id` = {$stock_id}"));
    $alreadyNotif = array();
    while ($r2 = $db->fetch_row($q2))
    {
        if (!in_array($r2['userid'], $alreadyNotif))
        {
            $notifText = "The {$r['am_name']} asset is failing! Sell your assets before it does, or risk your investment!";
            $api->GameAddNotification($r2['userid'], $notifText);
            array_push($alreadyNotif, $r2['userid']);
        }
    }
}