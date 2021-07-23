<?php
//Functions relating to the attack system...

/**
 * @internal
 * Return's the current user's weapon effectiveness.
 * @param int $weapID Item ID of the weapon.
 * @param int $attacker User ID of the person wielding the weapon.
 */
function calcWeaponEffectiveness($weapID, $attacker)
{
    global $db, $api;
    $q1 = $db->query("SELECT `weapon`, `ammo` FROM `items` WHERE `itmid` = {$weapID}");
    $r = $db->fetch_row($q1);
    $sharperBladersSkill = ((getSkillLevel($attacker, 9) * 20) / 100);  //20% per skill level
    $r['weapon'] += ($r['weapon'] * $sharperBladersSkill);
    if ($weapID == 235)
        $r['weapon'] = ($r['weapon'] * 0.25) * $api->UserInfoGet($attacker, "level");
    return $r['weapon'];
}