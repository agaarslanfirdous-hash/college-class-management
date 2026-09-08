<?php
/**
 * Run timetable board SQL against the DB from app config (port-aware).
 *
 * Usage:
 *   php scripts/apply_timetable_migrations.php              — run ALL listed files (see warning below)
 *   php scripts/apply_timetable_migrations.php --check      — verify 005 tables exist, no SQL
 *   php scripts/apply_timetable_migrations.php --only=005  — only run 005 (safe to repeat: CREATE IF NOT EXISTS)
 *
 * WARNING: The full list includes 002_timetable_board_seed, which TRUNCATEs tt_cells / tt_placements.
 * If you only need the latest tables (swaps + student presence), use --only=005.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$allFiles = [
    '002' => dirname(__DIR__) . '/database/migrations/002_timetable_board.sql',
    '002s' => dirname(__DIR__) . '/database/migrations/002_timetable_board_seed_caiml.sql',
    '003' => dirname(__DIR__) . '/database/migrations/003_timetable_departments_responses.sql',
    '004' => dirname(__DIR__) . '/database/migrations/004_classes_section_batch.sql',
    '005' => dirname(__DIR__) . '/database/migrations/005_swap_proposals_and_presence.sql',
];

$only = null;
$checkOnly = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--check' || $arg === 'check') {
        $checkOnly = true;
    }
    if (str_starts_with($arg, '--only=')) {
        $only = trim(substr($arg, 7), " \t\"'"); // e.g. 005
    }
}

$c = \app_config()['db'] ?? [];
$host = (string) ($c['host'] ?? '127.0.0.1');
$port = isset($c['port']) ? (int) $c['port'] : 3306;
$name = (string) ($c['name'] ?? 'college_cms');
$user = (string) ($c['user'] ?? 'root');
$pass = (string) ($c['pass'] ?? '');

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
]);

/** Tables from migration 005 (swap approvals + student “teacher seen”). */
$tablesToVerify005 = ['tt_swap_proposals', 'student_teacher_presence'];

function tableExists(PDO $pdo, string $tableName): bool
{
    $st = $pdo->prepare(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    $st->execute([$tableName]);
    return $st->fetchColumn() !== false;
}

if ($checkOnly) {
    echo "Checking database \"{$name}\" (005 tables)…\n";
    $ok = true;
    foreach ($tablesToVerify005 as $t) {
        $exists = tableExists($pdo, $t);
        echo ($exists ? "  OK   " : "  MISS ") . " {$t}\n";
        if (!$exists) {
            $ok = false;
        }
    }
    echo $ok
        ? "\n005 migration is applied. You’re done.\n"
        : "\nRun: php scripts/apply_timetable_migrations.php --only=005\n";
    exit($ok ? 0 : 1);
}

$files = array_values($allFiles);
if ($only !== null && $only !== '') {
    if (!isset($allFiles[$only]) || !is_readable($allFiles[$only])) {
        fwrite(STDERR, "Unknown or unreadable --only={$only}. Use: 002, 002s, 003, 004, 005\n");
        exit(1);
    }
    $files = [$allFiles[$only]];
    echo "Single migration: {$only} → " . basename($allFiles[$only]) . "\n\n";
}

if ($only === null) {
    fwrite(
        STDOUT,
        "Note: 002_timetable_board_seed re-TRUNCATEs programme timetable data. For only the latest tables, use: --only=005\n\n"
    );
}

foreach ($files as $f) {
    if (!is_readable($f)) {
        fwrite(STDERR, "Missing: {$f}\n");
        exit(1);
    }
    $sql = file_get_contents($f);
    if ($sql === false) {
        exit(1);
    }
    $pdo->exec($sql);
    echo basename($f) . " OK\n";
}

echo "\nDone. Reload /timetable-board\n\n";

$shouldCheck005 = $only === null || $only === '005';
if ($shouldCheck005) {
    echo "Verifying 005 tables (swap proposals + student teacher presence):\n";
    $miss = false;
    foreach ($tablesToVerify005 as $t) {
        $e = tableExists($pdo, $t);
        echo ($e ? "  OK   " : "  MISS ") . " {$t}\n";
        if (!$e) {
            $miss = true;
        }
    }
    if ($miss) {
        echo "\nRun: php scripts/apply_timetable_migrations.php --only=005\n";
        exit(1);
    }
    echo "\n005: applied and verified.\n";
} else {
    echo "(Skipped 005 verification; you used --only={$only}. Check 005: php scripts/apply_timetable_migrations.php --check)\n";
}
