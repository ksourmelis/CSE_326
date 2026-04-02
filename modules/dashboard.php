<?php
session_start();

if (!isset($_SESSION['user_id'])) {
	header('Location: ../auth/login.php');
	exit;
}

$username = trim((string) ($_SESSION['username'] ?? ''));
$role = trim((string) ($_SESSION['role'] ?? ''));

if ($username === '') {
	$username = 'Unknown User';
}

$roleLabels = [
	'admin' => 'Administrator',
	'politician' => 'Politician',
	'user' => 'User',
	'citizen' => 'Citizen',
];

$roleLabel = $roleLabels[$role] ?? 'Unknown Role';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard</title>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			min-height: 100vh;
			font-family: Arial, sans-serif;
			background: linear-gradient(140deg, #f4f7fb 0%, #e4eef7 100%);
			color: #1f2937;
			display: grid;
			place-items: center;
			padding: 1.25rem;
		}
		.card {
			width: 100%;
			max-width: 560px;
			background: #ffffff;
			border-radius: 12px;
			box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
			overflow: hidden;
		}
		.card-header {
			background: #0f172a;
			color: #f8fafc;
			padding: 1.2rem 1.4rem;
		}
		.card-header h1 {
			font-size: 1.35rem;
			line-height: 1.25;
		}
		.card-content {
			padding: 1.4rem;
			display: grid;
			gap: 0.9rem;
		}
		.info-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 0.9rem 1rem;
			border: 1px solid #dbe3ee;
			border-radius: 8px;
			background: #f8fbff;
		}
		.label {
			font-size: 0.84rem;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #475569;
			font-weight: 700;
		}
		.value {
			font-size: 1rem;
			color: #0f172a;
			font-weight: 600;
			text-align: right;
		}
		.actions {
			margin-top: 0.5rem;
			display: flex;
			justify-content: flex-end;
		}
		.logout-btn {
			display: inline-block;
			text-decoration: none;
			background: #0f172a;
			color: #fff;
			border-radius: 8px;
			padding: 0.65rem 0.9rem;
			font-weight: 600;
			font-size: 0.92rem;
		}
		.logout-btn:hover {
			background: #1e293b;
		}
	</style>
</head>
<body>
	<main class="card">
		<header class="card-header">
			<h1>Welcome to your dashboard</h1>
		</header>

		<section class="card-content">
			<div class="info-row">
				<span class="label">Logged In User</span>
				<span class="value"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
			</div>

			<div class="info-row">
				<span class="label">Role</span>
				<span class="value"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
			</div>

			<div class="actions">
				<a class="logout-btn" href="../auth/logout.php">Logout</a>
			</div>
		</section>
	</main>
</body>
</html>
