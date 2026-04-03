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
    <title>Σύνδεση — Πόθεν Έσχες</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        h1 { font-size: 1.4rem; margin-bottom: 1.5rem; color: #1a1a2e; text-align: center; }
        label { display: block; font-size: 0.875rem; color: #444; margin-bottom: 4px; }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.55rem 0.75rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        input:focus { outline: none; border-color: #4a90d9; }
        .btn {
            width: 100%;
            padding: 0.65rem;
            background: #b2c200;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn:hover { background: #2d2d50; }
        .error {
            background: #fdecea;
            border: 1px solid #f5c6c6;
            border-radius: 5px;
            padding: 0.65rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #c0392b;
        }
        .success {
            background: #eafaf1;
            border: 1px solid #a9dfbf;
            border-radius: 5px;
            padding: 0.65rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #1e8449;
        }
        .register-link { text-align: center; margin-top: 1rem; font-size: 0.875rem; }
        .register-link a { color: #4a90d9; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <h1>Πόθεν Έσχες — Σύνδεση</h1>

    <?php if (isset($_GET['registered'])): ?>
        <div class="success">Η εγγραφή ολοκληρώθηκε. Συνδεθείτε παρακάτω.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" autocomplete="email">

        <label for="password">Κωδικός</label>
        <input type="password" id="password" name="password" autocomplete="current-password">

        <button class="btn" type="submit">Σύνδεση</button>
    </form>

    <div class="register-link">
        Δεν έχετε λογαριασμό; <a href="register.php">Εγγραφή</a>
    </div>
</div>
</body>
</html>
