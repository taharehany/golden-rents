<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
try {
	if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
		http_response_code(403);
		exit('An administrator already exists.');
	}
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$token = (string) ($_POST['token'] ?? '');
		$username = trim((string) ($_POST['username'] ?? ''));
		$password = (string) ($_POST['password'] ?? '');
		if (!hash_equals((string) setting('setup_token', ''), $token) || strlen($username) < 3 || strlen($username) > 64 || strlen($password) < 12) {
			$error = 'Use the configured setup token, a 3–64 character username, and a password of at least 12 characters.';
		} else {
			$q = db()->prepare('INSERT INTO admins(username,password_hash) VALUES(?,?)');
			$q->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
			exit('Administrator created. Remove setup_token from config.local.php and delete this setup.php file. <a href="./">Sign in</a>.');
		}
	}
} catch (Throwable $e) {
	http_response_code(500);
	$error = 'Setup could not complete. Check configuration and error log.';
}
?><!doctype html>
<meta charset="utf-8">
<title>Initial admin setup</title>
<style>
	body {
		font: 16px Arial;
		max-width: 480px;
		margin: 60px auto;
		padding: 20px
	}

	label,
	input {
		display: block;
		width: 100%;
		box-sizing: border-box;
		margin: 8px 0
	}

	input {
		padding: 10px
	}

	button {
		padding: 10px 16px
	}

</style>
<h1>
	Initial admin setup</h1>
<?php if (!empty($error))
	echo '<p>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>'; ?>
<form method="post">
	<label>
		Setup token
		<input name="token" required>
	</label>
	<label>
		Username
		<input name="username" required>
	</label>
	<label>
		Password
		<input name="password" type="password" minlength="12" required>
	</label>
	<button>Create administrator</button>
</form>
