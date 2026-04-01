<?php
session_start();

require_once '../includes/db.php';

$errors = [];
$old = [];

$allowed_roles = ['politician', 'citizen'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? '';

    $old['username'] = htmlspecialchars($username);
    $old['email']    = htmlspecialchars($email);
    $old['role']     = $role;

    // Validation — collect ALL errors before displaying
    if ($username === '') {
        $errors[] = 'Το όνομα χρήστη είναι υποχρεωτικό.';
    }

    if ($email === '') {
        $errors[] = 'Το email είναι υποχρεωτικό.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Το email δεν είναι έγκυρο.';
    }

    if ($password === '') {
        $errors[] = 'Ο κωδικός είναι υποχρεωτικός.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
    }

    if ($confirm === '') {
        $errors[] = 'Η επιβεβαίωση κωδικού είναι υποχρεωτική.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Οι κωδικοί δεν ταιριάζουν.';
    }

    // Validate role server-side — never trust the client
    if (!in_array($role, $allowed_roles, true)) {
        $errors[] = 'Παρακαλώ επιλέξτε έγκυρο τύπο χρήστη.';
    }

    // Check uniqueness only if email format is valid
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Το email χρησιμοποιείται ήδη.';
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role, created_at)
             VALUES (:username, :email, :password_hash, :role, NOW())'
        );
        $stmt->execute([
            ':username'      => $username,
            ':email'         => $email,
            ':password_hash' => $hash,
            ':role'          => $role,
        ]);

        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εγγραφή — Πόθεν Έσχες</title>
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
            max-width: 420px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        h1 { font-size: 1.4rem; margin-bottom: 1.5rem; color: #1a1a2e; text-align: center; }
        label { display: block; font-size: 0.875rem; color: #444; margin-bottom: 4px; }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 0.55rem 0.75rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            background: #fff;
            color: #333;
        }
        input:focus, select:focus { outline: none; border-color: #4a90d9; }
        select option[value=""] { color: #aaa; }
        .btn {
            width: 100%;
            padding: 0.65rem;
            background: #1a1a2e;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn:hover { background: #2d2d50; }
        .errors {
            background: #fdecea;
            border: 1px solid #f5c6c6;
            border-radius: 5px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #c0392b;
        }
        .errors ul { padding-left: 1.2rem; }
        .errors li { margin-bottom: 3px; }
        .login-link { text-align: center; margin-top: 1rem; font-size: 0.875rem; }
        .login-link a { color: #4a90d9; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
        .role-hint {
            font-size: 0.78rem;
            color: #888;
            margin-top: -0.75rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Πόθεν Έσχες — Εγγραφή</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <label for="username">Όνομα χρήστη</label>
        <input type="text" id="username" name="username"
               value="<?= $old['username'] ?? '' ?>" autocomplete="username">

        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?= $old['email'] ?? '' ?>" autocomplete="email">

        <label for="role">Τύπος χρήστη</label>
        <select id="role" name="role">
            <option value="" disabled <?= empty($old['role']) ? 'selected' : '' ?>>-- Επιλέξτε --</option>
            <option value="politician" <?= (($old['role'] ?? '') === 'politician') ? 'selected' : '' ?>>Πολιτικός</option>
            <option value="citizen"   <?= (($old['role'] ?? '') === 'citizen')    ? 'selected' : '' ?>>Απλός Πολίτης</option>
        </select>
        <p class="role-hint">Οι πολιτικοί υποχρεούνται να δηλώσουν την περιουσία τους.</p>

        <label for="password">Κωδικός (min 8 χαρακτήρες)</label>
        <input type="password" id="password" name="password" autocomplete="new-password">

        <label for="confirm_password">Επιβεβαίωση κωδικού</label>
        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">

        <button class="btn" type="submit">Εγγραφή</button>
    </form>

    <div class="login-link">
        Έχετε ήδη λογαριασμό; <a href="login.php">Σύνδεση</a>
    </div>
</div>
</body>
</html>
