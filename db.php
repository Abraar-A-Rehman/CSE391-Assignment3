<?php
/**
 * CSE 391: Programming for the Internet - Assignment 3
 * Database Connection Configuration (PDO)
 */

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'cse391_workshop';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Database connection failed. Please ensure MySQL is running and database.sql is imported.'
        ]);
        exit;
    }
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}

/**
 * Helper to fetch all 5 senior mechanics along with booked and free slots for a given date.
 */
function getMechanicsAvailability(PDO $pdo, string $date): array {
    $sql = "
        SELECT 
            m.id,
            m.name,
            m.specialty,
            m.experience_years,
            m.phone,
            m.max_daily_slots,
            COUNT(a.id) AS booked_slots,
            GREATEST(0, m.max_daily_slots - COUNT(a.id)) AS free_slots
        FROM mechanics m
        LEFT JOIN appointments a 
            ON m.id = a.mechanic_id AND a.appointment_date = :appointment_date
        GROUP BY m.id, m.name, m.specialty, m.experience_years, m.phone, m.max_daily_slots
        ORDER BY m.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':appointment_date' => $date]);
    return $stmt->fetchAll();
}
?>
