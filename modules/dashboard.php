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

$isAdmin = $role === 'admin';
$isPolitician = $role === 'politician';
$isCitizen = $role === 'citizen';

if ($isAdmin) {
	include __DIR__ . '/admin/admin.php';
	exit;
}

if ($isPolitician) {
	include __DIR__ . '/submit/submit.php';
	exit;
}

if ($isCitizen) {
	header('Location: /modules/list.php');
	exit;
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
<html lang="el">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ΠΟΘΕΝ ΕΣΧΕΣ — Dashboard</title>
	<style>
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
		body { font-family: Arial, sans-serif; background: #f1f5f9; color: #1f2937; display: flex; flex-direction: column; min-height: 100vh; }

		.top-bar { background: #0f172a; color: #94a3b8; font-size: 0.78rem; padding: 0.35rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
		.top-bar a { color: #94a3b8; text-decoration: none; margin-left: 0.75rem; }
		.top-bar a:hover { color: #f3bf3a; }
		.top-bar-lang a { border: 1px solid #334155; padding: 0.15rem 0.4rem; border-radius: 3px; font-weight: 700; }
		.top-bar-lang a.active { background: #f3bf3a; color: #0f172a; border-color: #f3bf3a; }

		.site-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 1rem 2rem; display: flex; align-items: center; justify-content: center; text-align: center; }
		.logo-title { font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: 0.08em; }
		.logo-sub { font-size: 0.82rem; color: #64748b; margin-top: 0.2rem; }

		.main-nav { background: #f3bf3a; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
		.nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: stretch; }
		.main-nav a { display: flex; align-items: center; padding: 0.85rem 1.3rem; font-size: 0.9rem; font-weight: 700; color: #0f172a; text-decoration: none; white-space: nowrap; transition: background 0.15s; }
		.main-nav a:hover { background: rgba(0,0,0,0.12); }
		.nav-right { margin-left: auto; display: flex; }
		.nav-right a { border-left: 1px solid rgba(0,0,0,0.12); background: #7f1d1d; color: #fef2f2; }
		.nav-right a:hover { background: #991b1b; }

		.auth-wrap { display: flex; align-items: flex-start; justify-content: center; padding: 3rem 1.25rem 3rem; flex: 1; }
		.dash-card { background: #fff; border-radius: 12px; box-shadow: 0 3px 24px rgba(15,23,42,0.12); overflow: hidden; width: 100%; max-width: 520px; }
		.dash-card-header { background: #0f172a; color: #f8fafc; padding: 1.2rem 1.5rem; border-bottom: 3px solid #f3bf3a; }
		.dash-card-header h1 { font-size: 1.2rem; }
		.dash-card-body { padding: 1.4rem; display: grid; gap: 0.8rem; }
		.info-row { display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
		.info-label { font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; font-weight: 700; }
		.info-value { font-size: 0.95rem; color: #0f172a; font-weight: 600; }

		.site-footer { background: #0f172a; color: #94a3b8; padding: 2rem 2rem 1.2rem; margin-top: 0; }
		.footer-inner { max-width: 1200px; margin: 0 auto; }
		.footer-bottom { border-top: 1px solid #1e293b; padding-top: 1rem; font-size: 0.78rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
		.footer-bottom a { color: #94a3b8; text-decoration: none; }
		.footer-bottom a:hover { color: #f3bf3a; }
	</style>
</head>
<body>

<div class="top-bar">
	<span>Κυπριακή Δημοκρατία — Σύστημα ΠΟΘΕΝ ΕΣΧΕΣ</span>
	<div class="top-bar-lang">
		<a href="#" class="active">ΕΛ</a>
		<a href="#">EN</a>
	</div>
</div>

<header class="site-header">
	<div>
		<div class="logo-title">ΠΟΘΕΝ ΕΣΧΕΣ</div>
		<div class="logo-sub">Δηλώσεις Περιουσιακής &amp; Επαγγελματικής Κατάστασης</div>
	</div>
</header>

<nav class="main-nav">
	<div class="nav-inner">
		<a href="list.php">Αρχική</a>
		<div class="nav-right">
			<a href="../auth/logout.php">Αποσύνδεση</a>
		</div>
	</div>
</nav>

<div class="auth-wrap">
	<div class="dash-card">
		<div class="dash-card-header">
			<h1>Καλωσορίσατε στο Dashboard</h1>
		</div>
		<div class="dash-card-body">
			<div class="info-row">
				<span class="info-label">Χρήστης</span>
				<span class="info-value"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
			</div>
			<div class="info-row">
				<span class="info-label">Ρόλος</span>
				<span class="info-value"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
			</div>
		</div>
	</div>
</div>

<footer class="site-footer">
	<div class="footer-inner">
		<div class="footer-bottom">
			<span>Σύστημα ΠΟΘΕΝ ΕΣΧΕΣ &copy; <?= date('Y') ?></span>
		</div>
	</div>
</footer>

</body>
</html>
