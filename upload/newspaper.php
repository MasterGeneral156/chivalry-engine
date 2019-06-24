<script>
    function total_cost() {
        var day = parseInt((document.getElementById("days").value) * 1250);
        var init = parseInt((document.getElementById("init").value));
        var charlength = parseInt((document.getElementById("chars").value.length) * 5);
        var totalcost = day + init + charlength;
        var output = document.getElementById("output").value = totalcost;
    }
</script>
<?php
/*
	File:		newspaper.php
	Created: 	6/23/2019 at 6:11PM Eastern Time
	Info: 		Allows players to read player-created newspaper ads, or create their own!
	Author:		TheMasterGeneral
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
	MIT License

	Copyright (c) 2019 TheMasterGeneral

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all
	copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	SOFTWARE.
*/
require("globals.php");
$CurrentTime = time();
if (!isset($_GET['action'])) {
    $_GET['action'] = '';
}
function csrf_error()
{
    global $h;
    alert('danger', "Action Blocked!", "The action you were trying to do was blocked. It was blocked because you loaded
        another page on the game. If you have not loaded a different page during this time, change your password
        immediately, as another person may have access to your account!");
    die($h->endpage());
}

switch ($_GET['action']) {
    case 'buyad':
        news_buy();
        break;
    default:
        news_home();
        break;
}
function news_home()
{
    global $db, $h, $CurrentTime;
    $AdsQuery = $db->query("SELECT * FROM `newspaper_ads` WHERE `news_end` > {$CurrentTime} ORDER BY `news_cost` ASC");
    $db->query("DELETE FROM `newspaper_ads` WHERE `news_end` < {$CurrentTime}");
    if ($db->num_rows($AdsQuery) == 0) {
        alert("danger", "Uh Oh!", "There aren't any newspaper ads at this time. Maybe you should <a href='?action=buyad'>list</a> one?", false);
        die($h->endpage());
    }
    echo "<h3>The Newspaper</h3>
	<small>List an ad <a href='?action=buyad'>here</a>.<hr />";
    echo "
		<table class='table table-bordered'>
			<thead>
				<tr>
					<th width='33%'>
						Ad Info
					</th>
					<th>
						Ad Content
					</th>
				</tr>
			</thead>
			<tbody>
	";
    while ($Ads = $db->fetch_row($AdsQuery)) {
        $UserName = $db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$Ads['news_owner']}"));
        echo "	<tr>
					<td>
						Posted By <a href='profile.php?user={$Ads['news_owner']}'>{$UserName}</a> [{$Ads['news_owner']}]<br />
						<small>Posted At: " . dateTimeParse($Ads['news_start']) . "<br />
						Ad Ends: " . date('F j, Y g:i:s a', $Ads['news_end']) . "</small>
					</td>
					<td>
						{$Ads['news_text']}
					</td>
				</tr>";
    }
    echo "</tbody></table>";
}

function news_buy()
{
    global $db, $api, $h, $userid, $CurrentTime, $_CONFIG;
    if (isset($_POST['init_cost'])) {
        //Make sure POST is safe to work with
        $ad = $db->escape(nl2br(htmlentities(stripslashes($_POST['ad_text']), ENT_QUOTES, 'ISO-8859-1')));
        $initcost = (isset($_POST['init_cost']) && is_numeric($_POST['init_cost'])) ? abs($_POST['init_cost']) : 0;
        $days = (isset($_POST['ad_length']) && is_numeric($_POST['ad_length'])) ? abs($_POST['ad_length']) : 0;

        //Verify CSRF check has passed.
        if (!isset($_POST['verf']) || !checkCSRF("buy_ad", stripslashes($_POST['verf']))) {
            alert('danger', "Action Blocked!", "Forms expire fairly quickly. Be quicker next time.");
            die($h->endpage());
        }
        //Make sure form is filled out completely.
        if (empty($initcost) || empty($days) || empty($ad)) {
            alert('danger', "Uh Oh!", "You need to fill out the form completely before submitting.");
            die($h->endpage());
        }
        //Add up the costs
        $charcost = ((strlen($ad)) * 5);
        $daycost = $days * 1250;
        $totalcost = $daycost + $charcost + $initcost;
        //End Time
        $endtime=time()+(86400*$days);

        //Make sure user has the cash to buy this ad.
        if (!$api->user->hasCurrency($userid, 'primary', $totalcost)) {
            alert('danger', "Uh Oh!", "You do not have enough {$_CONFIG['primary_currency']} to place this ad.");
            die($h->endpage());
        }
        $api->user->takeCurrency($userid,'primary',$totalcost);
        alert('success',"Success!","You have successfully purchased a newspaper ad.",true,'newspaper.php');
        $db->query("INSERT INTO `newspaper_ads`
                    (`news_cost`, `news_start`, `news_end`, `news_owner`, `news_text`)
                    VALUES
                    ('{$totalcost}', '{$CurrentTime}', '{$endtime}', '{$userid}', '{$ad}')");

    } else {
        $csrf = getHtmlCSRF('buy_ad');
        echo "<h3>Buying an Ad</h3>
        " . alert("info", "Information!", "Remember, buying an ad is subject to the game rules. If you post something
            here that will break a game rule, you will be warned and your ad will be removed. If you find someone abusing
            the newspaper, please let an admin know immediately!", false) . "<hr />";
        echo "
            <form method='post'>
            <table class='table table-bordered'>
                <tr>
                    <td width='33%'>
                        Initial Ad Cost<br />
                        <small>A higher number will rank you higher on the ad list.</small>
                    </td>
                    <td>
                        <input type='number' value='25000' min='25000' name='init_cost' required='1' id='init' onkeyup='total_cost();' class='form-control'>
                    </td>
                </tr>
                <tr>
                    <td>
                        Ad Runtime<br />
                        <small>Each day will add 1,250 {$_CONFIG['primary_currency']} to your cost.</small>
                    </td>
                    <td>
                        <input type='number' value='1' min='1' name='ad_length' id='days' onkeyup='total_cost();' required='1' class='form-control'>
                    </td>
                </tr>
                <tr>
                    <td>
                        Ad Text<br />
                        <small>Each character is worth 5 {$_CONFIG['primary_currency']}.</small>
                    </td>
                    <td>
                        <textarea class='form-control' name='ad_text' id='chars' onkeyup='total_cost();' required='1'></textarea>
                    </td>
                </tr>
                <tr>
                    <td>
                        Total Ad Cost
                    </td>
                    <td>
                        <input type='number' name='ad_cost' id='output' readonly='1' class='form-control'>
                    </td>
                </tr>
                <tr>
                    <td colspan='2'>
                        <input type='submit' class='btn btn-primary' value='List Ad'>
                    </td>
                </tr>
            </table>
            {$csrf}
            </form>";
    }
}

$h->endpage();