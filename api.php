<?php
/**
 * CSE 391: Programming for the Internet - Assignment 3
 * Server API Endpoint (api.php)
 * Communicates with the webpage at every step of the appointment process:
 *  - Step 1: Real-time mechanic slot availability check by date
 *  - Step 2: Real-time duplicate client booking check on selected date
 *  - Step 3: Validated appointment creation (max 4 cars/mechanic/day & 1 booking/client/day)
 *  - Step 4: Admin appointment listing & reassignment (date + mechanic update)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

/**
 * Server-side input validation helper
 */
function validateBookingFields(array $data): array {
    $errors = [];

    $client_name      = trim($data['client_name'] ?? '');
    $address          = trim($data['address'] ?? '');
    $phone            = trim($data['phone'] ?? '');
    $car_license_no   = strtoupper(trim($data['car_license_no'] ?? ''));
    $car_engine_no    = trim($data['car_engine_no'] ?? '');
    $appointment_date = trim($data['appointment_date'] ?? '');
    $mechanic_id      = isset($data['mechanic_id']) ? (int)$data['mechanic_id'] : 0;

    if ($client_name === '' || strlen($client_name) < 2) {
        $errors['client_name'] = 'Client Name is required and must not be empty.';
    }
    if ($address === '' || strlen($address) < 4) {
        $errors['address'] = 'Address is required and must not be empty.';
    }
    if ($phone === '' || !preg_match('/^[0-9]{7,15}$/', $phone)) {
        $errors['phone'] = 'Phone number must contain only digits (7 to 15 numbers).';
    }
    if ($car_license_no === '') {
        $errors['car_license_no'] = 'Car License / Registration Number is required.';
    }
    if ($car_engine_no === '' || !preg_match('/^[0-9]+$/', $car_engine_no)) {
        $errors['car_engine_no'] = 'Car Engine Number must not be empty and must contain only numbers.';
    }
    if ($appointment_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
        $errors['appointment_date'] = 'A valid Appointment Date (YYYY-MM-DD) is required.';
    } else {
        $parts = explode('-', $appointment_date);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
            $errors['appointment_date'] = 'The provided Appointment Date is not a valid calendar date.';
        }
    }
    if ($mechanic_id <= 0) {
        $errors['mechanic_id'] = 'You must select a desired mechanic from the list.';
    }

    return [
        'errors' => $errors,
        'clean'  => [
            'client_name'      => $client_name,
            'address'          => $address,
            'phone'            => $phone,
            'car_license_no'   => $car_license_no,
            'car_engine_no'    => $car_engine_no,
            'appointment_date' => $appointment_date,
            'mechanic_id'      => $mechanic_id,
        ]
    ];
}

try {
    // 1. GET MECHANICS AVAILABILITY FOR A GIVEN DATE
    if ($action === 'availability') {
        $date = trim($_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $mechanics = getMechanicsAvailability($pdo, $date);
        echo json_encode([
            'status'    => 'success',
            'date'      => $date,
            'mechanics' => $mechanics
        ]);
        exit;
    }

    // 2. LIVE CHECK IF CLIENT ALREADY BOOKED ON SELECTED DATE
    if ($action === 'check_client') {
        $date           = trim($_GET['date'] ?? '');
        $phone          = trim($_GET['phone'] ?? '');
        $car_license_no = strtoupper(trim($_GET['car_license_no'] ?? ''));

        if ($date === '' || ($phone === '' && $car_license_no === '')) {
            echo json_encode(['status' => 'success', 'has_duplicate' => false]);
            exit;
        }

        $sql = "
            SELECT a.*, m.name AS mechanic_name
            FROM appointments a
            JOIN mechanics m ON a.mechanic_id = m.id
            WHERE a.appointment_date = :date
              AND (
                  (:phone != '' AND a.phone = :phone2)
                  OR (:license != '' AND UPPER(a.car_license_no) = :license2)
              )
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':date'     => $date,
            ':phone'    => $phone,
            ':phone2'   => $phone,
            ':license'  => $car_license_no,
            ':license2' => $car_license_no,
        ]);
        $existing = $stmt->fetch();

        echo json_encode([
            'status'        => 'success',
            'has_duplicate' => (bool)$existing,
            'existing'      => $existing ?: null
        ]);
        exit;
    }

    // 3. CREATE NEW APPOINTMENT (USER PANEL SUBMISSION)
    if ($action === 'book' && $method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $validation = validateBookingFields($payload);
        if (!empty($validation['errors'])) {
            http_response_code(422);
            echo json_encode([
                'status'  => 'error',
                'message' => reset($validation['errors']),
                'errors'  => $validation['errors']
            ]);
            exit;
        }

        $clean = $validation['clean'];

        // Check Rule A: Has this client already taken an appointment with ANY mechanic on this date?
        $dupStmt = $pdo->prepare("
            SELECT a.id, a.client_name, m.name AS mechanic_name
            FROM appointments a
            JOIN mechanics m ON a.mechanic_id = m.id
            WHERE a.appointment_date = :appointment_date
              AND (
                  a.phone = :phone
                  OR UPPER(a.car_license_no) = :car_license_no
                  OR a.car_engine_no = :car_engine_no
              )
            LIMIT 1
        ");
        $dupStmt->execute([
            ':appointment_date' => $clean['appointment_date'],
            ':phone'            => $clean['phone'],
            ':car_license_no'   => $clean['car_license_no'],
            ':car_engine_no'    => $clean['car_engine_no'],
        ]);
        $duplicate = $dupStmt->fetch();

        if ($duplicate) {
            http_response_code(409);
            echo json_encode([
                'status'  => 'error',
                'code'    => 'DUPLICATE_APPOINTMENT',
                'message' => "Appointment Rejected: You already have an active appointment on {$clean['appointment_date']} assigned to senior mechanic {$duplicate['mechanic_name']}. Each client/vehicle can only book one appointment per date."
            ]);
            exit;
        }

        // Check Rule B: Is the selected mechanic already occupied with maximum (4) appointments on this date?
        $mechStmt = $pdo->prepare("
            SELECT m.id, m.name, m.max_daily_slots, COUNT(a.id) AS booked_count
            FROM mechanics m
            LEFT JOIN appointments a
                ON m.id = a.mechanic_id AND a.appointment_date = :appointment_date
            WHERE m.id = :mechanic_id
            GROUP BY m.id, m.name, m.max_daily_slots
        ");
        $mechStmt->execute([
            ':appointment_date' => $clean['appointment_date'],
            ':mechanic_id'      => $clean['mechanic_id']
        ]);
        $mechanic = $mechStmt->fetch();

        if (!$mechanic) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Selected mechanic does not exist.'
            ]);
            exit;
        }

        if ((int)$mechanic['booked_count'] >= (int)$mechanic['max_daily_slots']) {
            http_response_code(409);
            echo json_encode([
                'status'  => 'error',
                'code'    => 'MECHANIC_FULL',
                'message' => "Appointment Rejected: Senior mechanic {$mechanic['name']} is already occupied with the maximum of {$mechanic['max_daily_slots']} cars on {$clean['appointment_date']}. Please choose another available mechanic."
            ]);
            exit;
        }

        // Approve & store appointment
        $insertStmt = $pdo->prepare("
            INSERT INTO appointments (client_name, address, phone, car_license_no, car_engine_no, appointment_date, mechanic_id)
            VALUES (:client_name, :address, :phone, :car_license_no, :car_engine_no, :appointment_date, :mechanic_id)
        ");
        $insertStmt->execute([
            ':client_name'      => $clean['client_name'],
            ':address'          => $clean['address'],
            ':phone'            => $clean['phone'],
            ':car_license_no'   => $clean['car_license_no'],
            ':car_engine_no'    => $clean['car_engine_no'],
            ':appointment_date' => $clean['appointment_date'],
            ':mechanic_id'      => $clean['mechanic_id']
        ]);

        $newId = (int)$pdo->lastInsertId();
        $remaining = (int)$mechanic['max_daily_slots'] - ((int)$mechanic['booked_count'] + 1);

        echo json_encode([
            'status'         => 'success',
            'appointment_id' => $newId,
            'message'        => "Appointment Approved! Your vehicle ({$clean['car_license_no']}) is scheduled with {$mechanic['name']} on {$clean['appointment_date']}. ({$remaining} free slot(s) remaining for {$mechanic['name']} on this date.)",
            'mechanics'      => getMechanicsAvailability($pdo, $clean['appointment_date'])
        ]);
        exit;
    }

    // 4. LIST ALL APPOINTMENTS FOR ADMIN PANEL
    if ($action === 'list_appointments') {
        $sql = "
            SELECT 
                a.id,
                a.client_name,
                a.address,
                a.phone,
                a.car_license_no,
                a.car_engine_no,
                a.appointment_date,
                a.mechanic_id,
                m.name AS mechanic_name,
                m.specialty AS mechanic_specialty
            FROM appointments a
            JOIN mechanics m ON a.mechanic_id = m.id
            ORDER BY a.appointment_date DESC, a.id DESC
        ";
        $appointments = $pdo->query($sql)->fetchAll();
        echo json_encode([
            'status'       => 'success',
            'appointments' => $appointments
        ]);
        exit;
    }

    // 5. ADMIN UPDATE APPOINTMENT (CHANGE DATE AND/OR ASSIGNED MECHANIC)
    if ($action === 'update_appointment' && $method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $appointment_id = isset($payload['appointment_id']) ? (int)$payload['appointment_id'] : 0;
        $new_date       = trim($payload['appointment_date'] ?? '');
        $new_mechanic   = isset($payload['mechanic_id']) ? (int)$payload['mechanic_id'] : 0;

        if ($appointment_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date) || $new_mechanic <= 0) {
            http_response_code(422);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Valid appointment ID, new appointment date, and mechanic selection are required.'
            ]);
            exit;
        }

        // Fetch current appointment record
        $curStmt = $pdo->prepare("SELECT * FROM appointments WHERE id = :id");
        $curStmt->execute([':id' => $appointment_id]);
        $current = $curStmt->fetch();

        if (!$current) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Appointment record not found.']);
            exit;
        }

        // Check if client already has ANOTHER appointment on the target date
        $dupStmt = $pdo->prepare("
            SELECT a.id, m.name AS mechanic_name
            FROM appointments a
            JOIN mechanics m ON a.mechanic_id = m.id
            WHERE a.appointment_date = :new_date
              AND a.id != :current_id
              AND (a.phone = :phone OR UPPER(a.car_license_no) = UPPER(:car_license_no))
            LIMIT 1
        ");
        $dupStmt->execute([
            ':new_date'       => $new_date,
            ':current_id'     => $appointment_id,
            ':phone'          => $current['phone'],
            ':car_license_no' => $current['car_license_no']
        ]);
        $dupOther = $dupStmt->fetch();
        if ($dupOther) {
            http_response_code(409);
            echo json_encode([
                'status'  => 'error',
                'message' => "Cannot reschedule: Client {$current['client_name']} already has another appointment on {$new_date} (with {$dupOther['mechanic_name']})."
            ]);
            exit;
        }

        // Check if target mechanic has free slots on target date (excluding this appointment itself if date/mechanic unchanged)
        $capStmt = $pdo->prepare("
            SELECT m.id, m.name, m.max_daily_slots,
                   COUNT(a.id) AS booked_count
            FROM mechanics m
            LEFT JOIN appointments a
                ON m.id = a.mechanic_id
                AND a.appointment_date = :new_date
                AND a.id != :current_id
            WHERE m.id = :mechanic_id
            GROUP BY m.id, m.name, m.max_daily_slots
        ");
        $capStmt->execute([
            ':new_date'    => $new_date,
            ':current_id'  => $appointment_id,
            ':mechanic_id' => $new_mechanic
        ]);
        $targetMech = $capStmt->fetch();

        if (!$targetMech) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Selected mechanic not found.']);
            exit;
        }

        if ((int)$targetMech['booked_count'] >= (int)$targetMech['max_daily_slots']) {
            http_response_code(409);
            echo json_encode([
                'status'  => 'error',
                'message' => "Cannot assign {$targetMech['name']} on {$new_date}: Already occupied with maximum {$targetMech['max_daily_slots']} active cars."
            ]);
            exit;
        }

        $updStmt = $pdo->prepare("
            UPDATE appointments
            SET appointment_date = :new_date,
                mechanic_id = :new_mechanic
            WHERE id = :id
        ");
        $updStmt->execute([
            ':new_date'     => $new_date,
            ':new_mechanic' => $new_mechanic,
            ':id'           => $appointment_id
        ]);

        echo json_encode([
            'status'  => 'success',
            'message' => "Updated appointment #{$appointment_id} for {$current['client_name']}: Rescheduled to {$new_date} with senior mechanic {$targetMech['name']}."
        ]);
        exit;
    }

    // 6. ADMIN DELETE APPOINTMENT
    if ($action === 'delete_appointment' && $method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }
        $appointment_id = isset($payload['appointment_id']) ? (int)$payload['appointment_id'] : 0;
        $delStmt = $pdo->prepare("DELETE FROM appointments WHERE id = :id");
        $delStmt->execute([':id' => $appointment_id]);
        echo json_encode([
            'status'  => 'success',
            'message' => "Appointment #{$appointment_id} removed and mechanic slot freed."
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid API action requested.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
