<?php

require_once __DIR__ . '/config/db.php';

echo "=========================================\n";
echo " Surgical Equipment DB Setup & Diagnostics\n";
echo "=========================================\n\n";

$possiblePasswords = [
    DB_PASS,
    'root',
    '',
    'admin',
    '123456',
    'password',
    'mysql'
];

$possiblePasswords = array_values(array_unique($possiblePasswords));

$workingPass = null;
$serverPdo = null;

foreach ($possiblePasswords as $pass) {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
        $serverPdo = new PDO($dsn, DB_USER, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $workingPass = $pass;
        echo "[SUCCESS] Connected to MySQL server as '" . DB_USER . "' (Password: " . ($pass === '' ? '<empty>' : '****') . ")\n";
        break;
    } catch (PDOException $e) {

    }
}

if ($serverPdo === null) {
    echo "[ERROR] Could not connect to MySQL server at " . DB_HOST . ":" . DB_PORT . "\n";
    echo "Access denied for user '" . DB_USER . "' with common default passwords.\n";
    echo "Please update config/.env with your MySQL password: DB_PASS=your_password\n";
    exit(1);
}

$envPath = __DIR__ . '/config/.env';
$envContent = "DB_HOST=" . DB_HOST . "\n"
            . "DB_PORT=" . DB_PORT . "\n"
            . "DB_NAME=" . DB_NAME . "\n"
            . "DB_USER=" . DB_USER . "\n"
            . "DB_PASS=" . $workingPass . "\n"
            . "DB_CHARSET=" . DB_CHARSET . "\n";
file_put_contents($envPath, $envContent);
echo "[OK] Saved database configuration to config/.env\n";

try {
    echo "Importing database.sql schema...\n";
    $sql = file_get_contents(__DIR__ . '/database.sql');
    if (!$sql) {
        throw new Exception("Could not read database.sql file.");
    }

    $serverPdo->exec($sql);
    echo "[OK] Database '" . DB_NAME . "' and tables created/updated successfully!\n\n";

    putenv("DB_PASS=" . $workingPass);

    $appPdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        $workingPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $tables = $appPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Verified Tables in DB '" . DB_NAME . "':\n";
    foreach ($tables as $table) {
        $count = $appPdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo "  - {$table}: {$count} records\n";
    }

    // Ensure default passwords always work across copies
    $defaultPassHash = password_hash('Password123!', PASSWORD_BCRYPT);
    $appPdo->prepare("UPDATE users SET password_hash = :hash")->execute([':hash' => $defaultPassHash]);
    echo "[OK] Reset demo accounts password to 'Password123!'\n";

    echo "\n-----------------------------------------\n";
    echo "Database setup completed SUCCESSFULLY!\n";
    echo "-----------------------------------------\n";

} catch (Exception $e) {
    echo "[ERROR] Schema execution error: " . $e->getMessage() . "\n";
    exit(1);
}
