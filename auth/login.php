<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ../modules/dashboard.php');
    exit;
}

require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Παρακαλώ συμπληρώστε όλα τα πεδία.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        $isValidLogin = false;

        if ($user) {
            $storedPassword = (string) ($user['password_hash'] ?? '');

            if (password_verify($password, $storedPassword)) {
                $isValidLogin = true;
            } elseif ($storedPassword !== '' && hash_equals($storedPassword, $password)) {
                $isValidLogin = true;

                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                $updateStmt->execute([
                    ':password_hash' => $newHash,
                    ':id' => $user['id'],
                ]);
            }
        }

        if ($isValidLogin) {
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            header('Location: ../modules/dashboard.php');
            exit;
        } else {
            $error = 'Λανθασμένα στοιχεία σύνδεσης.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σύνδεση — ΠΟΘΕΝ ΕΣΧΕΣ</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #1f2937; display: flex; flex-direction: column; min-height: 100vh; }

        .site-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 1rem 2rem; display: flex; align-items: center; justify-content: center; text-align: center; }
        .logo-title { font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: 0.08em; }
        .logo-sub { font-size: 0.82rem; color: #64748b; margin-top: 0.2rem; }

        .main-nav { background: #f3bf3a; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
        .nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: stretch; }
        .main-nav a { display: flex; align-items: center; padding: 0.85rem 1.3rem; font-size: 0.9rem; font-weight: 700; color: #0f172a; text-decoration: none; white-space: nowrap; transition: background 0.15s; }
        .main-nav a:hover, .main-nav a.active { background: rgba(0,0,0,0.12); }
        .nav-right { margin-left: auto; display: flex; }
        .nav-right a { border-left: 1px solid rgba(0,0,0,0.12); }
        .nav-right a.btn-login { background: #0f172a; color: #f3bf3a; }
        .nav-right a.btn-login:hover { background: #1e293b; }
        .nav-right a.btn-register { background: #334155; color: #f8fafc; }
        .nav-right a.btn-register:hover { background: #475569; }

        .auth-wrap { display: flex; align-items: flex-start; justify-content: center; padding: 3rem 1.25rem 3rem; flex: 1; }
        .auth-card { background: #fff; border-radius: 12px; box-shadow: 0 3px 24px rgba(15,23,42,0.12); padding: 2.2rem 2rem; width: 100%; max-width: 420px; }
        .auth-card h1 { font-size: 1.3rem; color: #0f172a; text-align: center; margin-bottom: 1.5rem; }
        .auth-card label { display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
        .auth-card input[type="email"], .auth-card input[type="password"] { width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; margin-bottom: 1rem; outline: none; }
        .auth-card input:focus { border-color: #f3bf3a; box-shadow: 0 0 0 3px rgba(243,191,58,0.2); }
        .auth-card .btn { width: 100%; padding: 0.7rem; background: #f3bf3a; color: #0f172a; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; }
        .auth-card .btn:hover { background: #e0aa20; }
        .auth-card .alt-link { text-align: center; margin-top: 1.1rem; font-size: 0.875rem; color: #64748b; }
        .auth-card .alt-link a { color: #0f172a; font-weight: 700; text-decoration: none; }
        .auth-card .alt-link a:hover { text-decoration: underline; }
        .auth-card .msg-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.65rem 1rem; margin-bottom: 1rem; font-size: 0.875rem; color: #b91c1c; }
        .auth-card .msg-success { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 0.65rem 1rem; margin-bottom: 1rem; font-size: 0.875rem; color: #166534; }

        .site-footer { background: #0f172a; color: #94a3b8; padding: 2rem 2rem 1.2rem; margin-top: 0; }
        .footer-inner { max-width: 1200px; margin: 0 auto; }
        .footer-bottom { border-top: 1px solid #1e293b; padding-top: 1rem; font-size: 0.78rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
        .footer-bottom a { color: #94a3b8; text-decoration: none; }
        .footer-bottom a:hover { color: #f3bf3a; }
    </style>
</head>
<body>


<header class="site-header">
    <div>
        <div class="logo-title">ΠΟΘΕΝ ΕΣΧΕΣ</div>
        <div class="logo-sub">Δηλώσεις Περιουσιακής &amp; Επαγγελματικής Κατάστασης</div>
    </div>
</header>

<nav class="main-nav">
    <div class="nav-inner">
        <a href="../modules/list.php">Αρχική</a>
        <a href="../modules/list.php">Δηλώσεις</a>
        <a href="../modules/search/search.php?section=statistics">Στατιστικά</a>
        <div class="nav-right">
            <a href="register.php" class="btn-register">Εγγραφή</a>
            <a href="login.php" class="active btn-login">Σύνδεση</a>
        </div>
    </div>
</nav>

<div class="auth-wrap">
    <div class="auth-card">
        <h1>Σύνδεση</h1>

        <?php if (isset($_GET['registered'])): ?>
            <div class="msg-success">Η εγγραφή ολοκληρώθηκε. Συνδεθείτε παρακάτω.</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="msg-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="email">

            <label for="password">Κωδικός</label>
            <input type="password" id="password" name="password" autocomplete="current-password">

            <button class="btn" type="submit">Σύνδεση</button>
        </form>

        <div class="alt-link">
            Δεν έχετε λογαριασμό; <a href="register.php">Εγγραφή</a>
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
