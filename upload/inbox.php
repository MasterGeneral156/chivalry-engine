<?php
require('globals.php');
require('lib/bbcode_engine.php');
echo "
<div class='table-responsive'>
<table class='table table-bordered table-responsive'>
	<tr>
		<td>
			<a href='inbox.php'>Inbox</a>
		</td>
		<td>
			<a href='?action=outbox'>Outbox</a>
		</td>
		<td>
			<a href='?action=compose'>Compose</a>
		</td>
		<td>
			<a href='?action=delall'>Delete All</a>
		</td>
		<td>
			<a href='#'>Archive</a>
		</td>
		<td>
			<a href='#'>Contacts</a>
		</td>
	</tr>
</table>
</div>";
if (!isset($_GET['action']))
{
    $_GET['action'] = '';
}
function csrf_error($goBackTo)
{
    global $h,$lang;
	echo "<div class='alert alert-danger'> <strong>{$lang['CSRF_ERROR_TITLE']}</strong> 
	{$lang['CSRF_ERROR_TEXT']} {$lang['CSRF_PREF_MENU']} <a href='inbox.php?action={$goBackTo}'>{$lang['GEN_HERE']}.</div>";
    $h->endpage();
    exit;
}
switch ($_GET['action'])
{
case 'compose':
    compose();
    break;
case 'read':
    read();
    break;
case 'send':
    send();
    break;
case 'markread':
    markasread();
    break;
case 'delall':
    delall();
    break;
case 'outbox':
    outbox();
    break;
case 'compose':
    compose();
    break;
default:
    home();
    break;
}
function home()
{
	global $db,$userid,$ir,$lang,$parser;
	echo "<table class='table table-bordered table-striped'>
	<tr>
		<th>
			{$lang['MAIL_USERDATE']}
		</th>
		<th width='50%'>
			{$lang['MAIL_PREVIEW']}
		</th>
		<th width='10%'>
			{$lang['MAIL_ACTION']}
		</th>
	</tr>";
	$MailQuery=$db->query("SELECT * FROM `mail` WHERE `mail_to` = '{$userid}' ORDER BY `mail_time` desc LIMIT 15");
	while ($r = $db->fetch_row($MailQuery))
		{
			$un1=$db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$r['mail_from']}"));
			if ($r['mail_status'] == 'unread')
			{
				$status="<span class='label label-danger'>{$lang['MAIL_MSGUNREAD']}</span>";
			}
			else
			{
				$status="<span class='label label-primary'>{$lang['MAIL_MSGREAD']}</span>";
			}
			$msgtxt=substr($r['mail_text'], 0, 50);
			$parser->parse($msgtxt);
			echo"<tr>
					<td>
						<a href='profile.php?user={$r['mail_from']}'>{$un1}</a> [{$r['mail_from']}]<br />
							{$lang['MAIL_SENTAT']}: " . date('F j, Y g:i:s a', $r['mail_time']) . "<br />
						{$lang['MAIL_STATUS']}: {$status}
					</td>
					<td>
						<b>{$r['mail_subject']}</b> ";
						echo $parser->getAsHtml();
						echo"...
					</td>
					<td>
						<a href='?action=read&msg={$r['mail_id']}'>{$lang['MAIL_READ']}</a><br />
						<a href='playerreport.php'>{$lang['MAIL_REPORT']}</a><br />
						<a href='?action=delete&msg={$r['mail_id']}'>{$lang['MAIL_DELETE']}</a><br />
					</td>
				</tr>";
		}
	
	echo"</table>
	<form action='?action=markread' method='post'>
	<input type='submit' class='btn btn-default' value='{$lang['MAIL_MARKREAD']}'>
	</form>";
}
function read()
{
	global $db,$ir,$userid,$lang,$h,$parser;
	$code = request_csrf_code('inbox_send');
	$_GET['msg'] = (isset($_GET['msg']) && is_numeric($_GET['msg'])) ? abs(intval($_GET['msg'])) : 0;
	if ($_GET['msg'] == 0)
	{
		alert('danger','Oops.','Message does not exist.');
		die($h->endpage());
	}
	if ($db->num_rows($db->query("SELECT `mail_id` FROM `mail` WHERE `mail_id` = {$_GET['msg']} AND `mail_to` = {$userid}")) == 0)
	{
		alert("danger","{$lang['ERROR_SECURITY']}","{$lang['ERROR_MAIL_UNOWNED']}");
		die($h->endpage());
	}
	$msg=$db->fetch_row($db->query("SELECT * FROM `mail` WHERE `mail_id` = {$_GET['msg']}"));
	$username=$db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$msg['mail_from']}"));
	$db->query("UPDATE `mail` SET `mail_status` = 'read' WHERE `mail_id` = {$_GET['msg']}");
	$parser->parse($msg['mail_text']);
	echo "<table class='table table-bordered'>
	<tr>
		<th width='33%'>
			{$lang['MAIL_USERINFO']}
		</th>
		<th>
			{$lang['MAIL_MSGSUB']}
		</th>
	</tr>
	<tr>
		<td>
			<b>{$lang['MAIL_FROM']}:</b> <a href='profile.php?user={$msg['mail_from']}'>{$username}</a><br />
			<b>{$lang['MAIL_SENTAT']}:</b> " . date('F j, Y g:i:s a', $msg['mail_time']) . "
		</td>
		<td>
			<b>{$msg['mail_subject']}</b><br />";
				echo $parser->getAsHtml();
				echo"
		</td>
	</tr>
	</table>
	<hr />
	{$lang['MAIL_QUICKREPLY']}<br />
	<form method='post' action='?action=send'>
	<table class='table table-bordered'>
	<tr>
		<th>
			{$lang['MAIL_SENDTO']}
		</th>
		<td>
			<input type='text' class='form-control' readonly='1' name='sendto' required='1' value='{$username}'>
		</td>
	</tr>
	<tr>
		<th>
			{$lang['MAIL_SUBJECT']}
		</th>
		<td>
			<input type='text' class='form-control' maxlength='50' name='subject' value='{$msg['mail_subject']}'>
		</td>
	</tr>
	<tr>
		<th>
			{$lang['MAIL_MESSAGE']}
		</th>
		<td>
			<textarea class='form-control' rows='5' required='1' maxlength='65655' name='msg'></textarea>
		</td>
	</tr>
	<tr>
		<td colspan='2'>
			<input type='submit' class='btn btn-default'  value='{$lang['MAIL_REPLYTO']} {$username}'>
		</td>
	</tr>
	</table>
	<input type='hidden' name='verf' value='{$code}' />
	</form>";
}
function send()
{
	global $db,$lang,$ir,$userid,$h;
	$subj = $db->escape(str_replace("\n", "<br />",strip_tags(stripslashes($_POST['subject']))));
	$msg = $db->escape(str_replace("\n", "<br />",strip_tags(stripslashes($_POST['msg']))));
	if (!isset($_POST['verf']) || !verify_csrf_code('inbox_send', stripslashes($_POST['verf'])))
	{
		csrf_error('');
	}
	if (empty($msg))
    {
		alert('danger',"{$lang['ERROR_EMPTY']}","{$lang['MAIL_EMPTYINPUT']}");
        die($h->endpage());
    }
	elseif ((strlen($msg) > 65655) || (strlen($subj) > 50))
    {
        alert('danger',"{$lang['ERROR_LENGTH']}","{$lang['MAIL_INPUTLNEGTH']}");
        die($h->endpage());
    }
	 $sendto = (isset($_POST['sendto']) && preg_match("/^[a-z0-9_]+([\\s]{1}[a-z0-9_]|[a-z0-9_])+$/i", $_POST['sendto']) && ((strlen($_POST['sendto']) < 32) && (strlen($_POST['sendto']) >= 3))) ? $_POST['sendto'] : '';
	 if (empty($_POST['sendto']))
    {
		alert('danger',"{$lang['ERROR_EMPTY']}","{$lang['MAIL_NOUSER']}");
        die($h->endpage());
    }
	$q = $db->query("SELECT `userid` FROM `users` WHERE `username` = '{$sendto}'");
	if ($db->num_rows($q) == 0)
    {
        $db->free_result($q);
		alert('danger',"{$lang['MAIL_UDNE']}","{$lang['MAIL_UDNE_TEXT']}");
        die($h->endpage());
    }
	$to = $db->fetch_single($q);
    $db->free_result($q);
	$time=time();
	$db->query("INSERT INTO `mail` 
	(`mail_id`, `mail_to`, `mail_from`, `mail_status`, `mail_subject`, `mail_text`, `mail_time`) 
	VALUES (NULL, '{$to}', '{$userid}', 'unread', '{$subj}', '{$msg}', '{$time}');");
	alert('success',"{$lang['ERROR_SUCCESS']}","{$lang['MAIL_SUCCESS']}");
}
function markasread()
{
	global $db,$h,$userid,$h,$lang;
	$db->query("UPDATE `mail` SET `mail_status` = 'read' WHERE `mail_to` = {$userid}");
	alert('success',"{$lang['ERROR_SUCCESS']}","{$lang['MAIL_READALL']}");
	home();
}
function delall()
{
	global $db,$lang,$h,$userid;
	if (empty($_POST['delete']))
	{
		echo $lang['MAIL_DELETECONFIRM'];
		echo "<br />
		<form method='post'>
			<input type='submit' name='delete' class='btn btn-default' value='{$lang['MAIL_DELETEYES']}'>
		</form>
		<form method='post' action='inbox.php'>
			<input type='submit' class='btn btn-danger' value='{$lang['MAIL_DELETENO']}'>
		</form>";
	}
	else
	{
		$db->query("DELETE FROM `mail` WHERE `mail_to` = {$userid}");
		alert('success',"{$lang['ERROR_SUCCESS']}","{$lang['MAIL_DELETEDONE']}");
	}
}
function outbox()
{
	global $db,$lang,$userid,$lang,$h,$parser;
	echo "	<table class='table table-bordered table-hover table-striped'>
				<thead>
					<th width='33%'>
						{$lang['MAIL_USERDATE']}
					</th>
					<th>
						{$lang['MAIL_MSGSUB']}
					</th>
				</thead>
				<tbody>";
				$query=$db->query("SELECT * FROM `mail` WHERE `mail_from` = {$userid} ORDER BY `mail_time` desc LIMIT 15");
				while ($msg = $db->fetch_row($query))
				{
					$sent=date('F j, Y g:i:s a', $msg['mail_time']);
					$sentto=$db->fetch_single($db->query("SELECT `username` FROM `users` WHERE `userid` = {$msg['mail_to']}"));
					$parser->parse($msg['mail_text']);
					if ($msg['mail_status'] == 'unread')
					{
						$status="<span class='label label-danger'>{$lang['MAIL_MSGUNREAD']}</span>";
					}
					else
					{
						$status="<span class='label label-primary'>{$lang['MAIL_MSGREAD']}</span>";
					}
					echo "<tr>
							<td>
								<b>{$lang['MAIL_SENDTO']}:</b> <a href='profile.php?user={$msg['mail_to']}'>{$sentto}</a><br />
								<b>{$lang['MAIL_SENTAT']}: </b>{$sent}<br />
								<b>{$lang['MAIL_STATUS']}:</b> {$status}<br />
							</td>
							<td>
								<b>{$msg['mail_subject']}</b> ";
								echo $parser->getAsHtml();
								echo"
							</td>
					
					</tr>";
				}
			echo "</tbody></table>'";
}
function compose()
{
	global $db,$userid,$lang,$h;
	$code = request_csrf_code('inbox_send');
	echo "
	<form method='post' action='?action=send'>
	<table class='table table-bordered'>
	<tr>
		<th>
			{$lang['MAIL_SENDTO']}
		</th>
		<td>
			<input type='text' class='form-control' name='sendto' required='1'>
		</td>
	</tr>
	<tr>
		<th>
			{$lang['MAIL_SUBJECT']}
		</th>
		<td>
			<input type='text' class='form-control' maxlength='50' name='subject'>
		</td>
	</tr>
	<tr>
		<th>
			{$lang['MAIL_MESSAGE']}
		</th>
		<td>
			<textarea class='form-control' rows='5' required='1' maxlength='65655' name='msg'></textarea>
		</td>
	</tr>
	<tr>
		<td colspan='2'>
			<input type='submit' class='btn btn-default'  value='{$lang['MAIL_SENDMSG']}'>
		</td>
	</tr>
	</table>
	<input type='hidden' name='verf' value='{$code}' />
	</form>";
}
$h->endpage();