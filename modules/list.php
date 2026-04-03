<?php
session_start();

if (isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') !== 'citizen')) {
    header('Location: ../dashboard.php');
    exit;
}

require_once '../includes/db.php';

$isLoggedInCitizen = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'citizen');
$username = trim((string) ($_SESSION['username'] ?? 'Guest'));

$keyword = trim($_GET['keyword'] ?? '');
$results = [];

if ($keyword !== '') {
    $kw = '%' . $keyword . '%';
    $stmt = $pdo->prepare(
        'SELECT d.*, u.username
         FROM declarations d
         JOIN users u ON d.user_id = u.id
         WHERE u.username LIKE :kw
            OR CAST(d.id AS CHAR) LIKE :kw
            OR CAST(d.user_id AS CHAR) LIKE :kw
            OR CAST(d.declaration_year AS CHAR) LIKE :kw
                OR d.party LIKE :kw
                OR d.position LIKE :kw
                OR d.province LIKE :kw
            OR d.properties LIKE :kw
            OR d.vehicles LIKE :kw
            OR d.shares LIKE :kw
            OR d.debts LIKE :kw
                OR CAST(d.created_at AS CHAR) LIKE :kw
            OR CAST(d.income AS CHAR) LIKE :kw
         ORDER BY d.declaration_year DESC, u.username ASC'
    );
    $stmt->execute([':kw' => $kw]);
    $results = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare(
        'SELECT d.*, u.username
         FROM declarations d
         JOIN users u ON d.user_id = u.id
         ORDER BY d.declaration_year DESC, u.username ASC'
    );
    $stmt->execute();
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Module</title>
    <link rel="stylesheet" href="search/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="side-header">
            <h2>Search Module</h2>
            <p><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <nav class="side-nav">
            <?php if ($isLoggedInCitizen): ?>
                <a href="search/search.php?section=dashboard" class="side-link">Dashboard</a>
            <?php endif; ?>
            <a href="list.php" class="side-link active">Search</a>
            <a href="search/search.php?section=statistics" class="side-link">Statistics</a>
            <?php if ($isLoggedInCitizen): ?>
                <a href="search/search.php?section=change-password" class="side-link">Change Password</a>
            <?php endif; ?>
        </nav>

        <?php if ($isLoggedInCitizen): ?>
            <a class="logout-link" href="../auth/logout.php">Logout</a>
        <?php else: ?>
            <a class="logout-link" href="../auth/login.php">Login</a>
        <?php endif; ?>
    </aside>

    <main class="content">
        <section class="panel">
            <h1>Search</h1>
            <p>Search declarations by any available field.</p>

            <form class="search-form" method="GET" action="list.php">
                <input
                    type="text"
                    name="keyword"
                    placeholder="Name, party, position, debts, province, income..."
                    value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                >
                <button type="submit">Search</button>
            </form>

            <p class="result-count">
                <?php if ($keyword !== ''): ?>
                    <?= count($results) ?> result<?= count($results) !== 1 ? 's' : '' ?> for "<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                <?php else: ?>
                    Showing all <?= count($results) ?> declaration<?= count($results) !== 1 ? 's' : '' ?>
                <?php endif; ?>
            </p>

            <div class="table-wrap">
                <table class="declarations-full-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Year</th>
                            <th>Party</th>
                            <th>Position</th>
                            <th>Province</th>
                            <th>Properties</th>
                            <th>Vehicles</th>
                            <th>Shares</th>
                            <th>Debts</th>
                            <th>Income</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?= (int) $row['id'] ?></td>
                            <td><?= (int) $row['user_id'] ?></td>
                            <td><?= htmlspecialchars((string) $row['username'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $row['declaration_year'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $row['party'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $row['position'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $row['province'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-cell"><?= htmlspecialchars((string) ($row['properties'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-cell"><?= htmlspecialchars((string) ($row['vehicles'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-cell"><?= htmlspecialchars((string) ($row['shares'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-cell"><?= htmlspecialchars((string) ($row['debts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float) $row['income'], 2) ?></td>
                            <td><?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="13" class="empty">No matching records.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
