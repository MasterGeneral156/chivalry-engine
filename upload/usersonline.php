<?php
/*
	File:		usersonline.php
	Created: 	4/5/2016 at 12:31AM Eastern Time
	Info: 		Lists players on within the time period set. The GET
				can be set to any integer value, and it'll check that
				number minutes ago.
	Author:		TheMasterGeneral
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
*/
require('globals.php');

//Different options for different time periods. The GET is in minutes.
echo "<h3><i class='fas fa-toggle-on'></i> Users Online</h3><hr />
[<a href='?act=5'>5 Minutes</a>]
[<a href='?act=15'>15 Minutes</a>]
[<a href='?act=60'>1 Hour</a>]
[<a href='?act=1440'>1 Day</a>]
[<a href='?act=43200'>7 Days</a>]
[<a href='?act=10080'>30 Days</a>]
[<a href='?act=131400'>1 Quarter</a>]
[<a href='?act=525600'>1 Year</a>]<hr />";

//Time period isn't set, so set it to 15.
if (!isset($_GET['act'])) {
    $_GET['act'] = 15;
}
$_GET['act'] = (isset($_GET['act']) && is_numeric($_GET['act'])) ? abs($_GET['act']) : 15;
$last_on = time() - ($_GET['act'] * 60);

//Select all players on in the time period set in the GET.
$q = $db->query("/*qc=on*/SELECT * FROM `users` WHERE `laston` > {$last_on} ORDER BY `laston` DESC");
echo "<table class='table table-bordered table-striped'>
	<tr>
		<th>
			User
		</th>
		<th>
			Last Active
		</th>
	</tr>";
while ($r = $db->fetch_row($q)) {
    $r['username']=parseUsername($r['userid']);
    echo "<tr>
		<td>
			<a href='profile.php?user={$r['userid']}'>{$r['username']}</a> [{$r['userid']}]
		</td>
		<td>
			" . DateTime_Parse($r['laston']) . "
		</td>
	</tr>";
}
echo "</table>";
$h->endpage();