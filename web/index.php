<?php
require 'config.php';
if (isset($_POST['submit'])) {
	$name = htmlspecialchars(trim($_POST['name']));
	$guid = htmlspecialchars(trim($_POST['guid']));
	$email = htmlspecialchars(trim($_POST['email']));
	if (strlen($name) < 3) {
		$error = 'Invalid name (at least 3 characters)!';
	} elseif (!preg_match('/\b[0-9a-f]{32}\b/', $guid)) {
		$error = 'Invalid GUID!';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error = 'Invalid email address!';
	} elseif (!preg_match('/\bzombie(s)?\b/i', $_POST['human'])) {
		$error = 'Sorry, but your answer is not correct! Hint: it starts with a Z.';
	} else {
		require 'db.php';
		// name unique check
		$res = $db->query("SELECT id FROM whitelist_temp WHERE name = '$name'");
		if ($res->num_rows !== 0) {
			$error = "There is already a registered player with the name \"$name\", please choose another.";
		} else {
			// GUID unique check
			$res = $db->query("SELECT name FROM whitelist_temp WHERE guid = '$guid'");
			if ($res->num_rows !== 0) {
				$error = "There is already a registered player with the GUID \"$guid\": " . $res->fetch_row()[0] . '.';
			} else {
				// email unique check
				$res = $db->query("SELECT name FROM whitelist_temp WHERE email = '$email'");
				if ($res->num_rows !== 0) {
					$error = "There is already a registered player with the email address \"$email\".";
				} else {
					// all ok, insert the player data
					$ip = $_SERVER['REMOTE_ADDR'];
					$activation_code = substr(md5(uniqid()), 0, 10);
					$res = $db->query("INSERT INTO whitelist_temp (name, guid, email, ip, activation_code) VALUES ('$name', '$guid', '$email', '$ip', '$activation_code')");
					if ($res) {
						require 'mail.php';
						$mail->addAddress($email, "$name");
						if ($mail->send()) {
							$success = "$name, an activation link has been send to your email address.";
						} else {
							$error = 'Could not send the activation mail: ' . $mail->ErrorInfo;
						}
					} else {
						$error = 'Internal database error: ' . $db->error;
					}
				}
			}
		}
		$db->close();
	}
} elseif (isset($_GET['activate'])) {
	$activation_code = htmlspecialchars(trim($_GET['activate']));
	$guid = htmlspecialchars(trim($_GET['guid'])); 
	require 'db.php';
	$res = $db->query("SELECT id, name, guid, activated FROM whitelist_temp WHERE guid = '$guid' AND activation_code = '$activation_code'");
	if ($res->num_rows === 1) {
		$wlt = $res->fetch_object();
		if ($wlt->activated == 1) {
			$success = 'This whitelist request has already been activated.';
		} else {
			$id = $wlt->id;
			$name = $wlt->name;
			$guid = $wlt->guid; // better take the GUID from the DB
			if ($db->query("UPDATE whitelist_temp SET activated = '1' WHERE id = '$id'") && 
				$db->query("INSERT INTO whitelist (guid, name) VALUES ('$guid', '$name')")) {
				$success = 'Your whitelist request was successfully activated. You can join the game server now, have fun.';
			} else {
				$error = 'Internal database error: ' . $db->error;
			}
		}
	} else {
		$error = 'Invalid activation link!';
	}
	$db->close();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="content-language" content="en" />
	<meta http-equiv="content-style-type" content="text/css" />
	<meta http-equiv="content-script-type" content="text/javascript" />
	<title><?= $site_title; ?> - Whitelist Application Form</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>
<div class="center">
<?php
if ($success) {
?>
	<p class="box info top"><?= $success; ?></p>
<?php
} elseif ($error) {
?>
	<p class="box error top"><?= $error; ?></p>
<?php
}
?>
	<p style="padding: 25px">
		<span style="font-size: 22pt; font-variant: small-caps; font-stretch: expanded">Whitelist Application Form</span><br />
		<span style="font-size: 8pt; color: #a0a0a0"><?= $site_title; ?></span>
	</p>
	<form action="index.php" method="post" class="fixed">
		<fieldset>
			<legend>Player Data</legend>
			<div class="mandatory">
				<label for="name">Player Name</label>
				<input type="text" id="name" name="name" maxlength="128" value="<?= $_POST['name']; ?>" /> *
			</div>
			<div class="mandatory">
				<label for="guid">GUID</label>
				<input type="text" id="guid" name="guid" maxlength="32" value="<?= $_POST['guid']; ?>" /> **
			</div>
			<div class="mandatory">
				<label for="email">Email</label>
				<input type="text" id="email" name="email" maxlength="254" value="<?= $_POST['email']; ?>" /> ***
			</div>
		</fieldset>
		<fieldset>
			<legend>Are you human?</legend>
			<div style="padding-left: 40px">What is the common enemy in DayZ?</div>
			<div class="mandatory">
				<label for="human">Answer</label>
				<input type="text" id="human" name="human" maxlength="10" value="<?= $_POST['human']; ?>" />
			</div>
		</fieldset>
		<div><button style="margin-top: 5px;" type="submit" name="submit" <?php if ($success) { echo 'disabled=""'; } ?>>Submit Whitelist Request</button></div>
	</form>
	<p style="margin-top: 40px; color: #555">* <b>Your player name will be bound to your GUID.</b><br/>
	That means you can only log in the game with the name you submit with this form.</p>
	<p style="margin-top: 10px; color: #555">** <b>How to get your GUID?</b><br/>
	Start <b>DayZ Commander</b> and click on <b>Settings</b> and then on the button <b>Copy your GUID to the clipboard</b>.<br/>
	In case you don't use DayZ Commander, try Google: <a href="https://www.google.com/search?q=how+to+get+dayz+guid" target="_blank">How to get DayZ GUID?</a>.</p>
	<p style="margin-top: 10px; color: #555">*** <b>An activation link will be send to your email address.</b><br/>
	After submitting this form check your email inbox (and spam folder) for an email from <b><?= $site_title; ?></b>,<br/>
	and click on the provided activation link to complete your whitelist request.</p>
	<p style="margin-top: 40px">Visit our website at <a href="<?= $site_url; ?>"><?= $site_name; ?></a></p>
</div>
</body>
</html>
