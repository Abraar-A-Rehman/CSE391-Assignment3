<?php
/**
 * CSE 391: Programming for the Internet - Assignment 3
 * Separate Admin Panel Page (admin.php) with Admin Credential Login Gate
 */
session_start();
require_once __DIR__ . '/db.php';

$admin_user = 'admin';
$admin_pass = 'admin123';
$login_error = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    if ($u === $admin_user && $p === $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Invalid administrator credentials. Please use admin / admin123.';
    }
}

$is_logged_in = !empty($_SESSION['admin_logged_in']);

$mechanics = [];
$appointments = [];
if ($is_logged_in) {
    $mechanics = $pdo->query("SELECT * FROM mechanics ORDER BY id ASC")->fetchAll();
    $appointments = $pdo->query("
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
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — Apex AutoCare Workshop (CSE 391)</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="min-h-screen flex flex-col bg-slate-950 text-slate-100">

    <!-- Top Navigation Bar (User Panel button in the exact same position as Admin Panel button) -->
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center text-slate-950 font-black text-lg">
                    AA
                </div>
                <div>
                    <span class="font-bold text-lg tracking-tight text-white">Apex AutoCare — Admin Panel</span>
                    <span class="block text-xs text-slate-400">Appointment Management &amp; Senior Mechanic Reassignment</span>
                </div>
            </div>
            <nav class="flex items-center">
                <a href="index.php" class="px-4 py-2 rounded-xl text-sm font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-md shadow-amber-500/20 transition">
                    ← User Panel
                </a>
            </nav>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!$is_logged_in): ?>
            <!-- Admin Login Gate -->
            <div class="max-w-md mx-auto py-10">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl space-y-6">
                    <div class="text-center space-y-2">
                        <h1 class="text-2xl font-extrabold text-white">Admin Panel Login</h1>
                        <p class="text-xs text-slate-400">Please enter administrator credentials to manage workshop appointments.</p>
                    </div>
                    <?php if ($login_error !== ''): ?>
                        <div class="p-3.5 rounded-xl bg-red-950/80 border border-red-500/50 text-xs text-red-200">
                            <?= htmlspecialchars($login_error) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="admin.php" class="space-y-4">
                        <input type="hidden" name="admin_login" value="1">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Admin Username *</label>
                            <input type="text" name="username" required placeholder="admin"
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Admin Password *</label>
                            <input type="password" name="password" required placeholder="admin123"
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400">
                        </div>
                        <p class="text-xs text-slate-400">Default Credentials: <code class="text-amber-300">admin</code> / <code class="text-amber-300">admin123</code></p>
                        <button type="submit" class="w-full py-3 px-6 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm transition">
                            Sign In to Admin Panel
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div id="adminBanner" class="hidden mb-6 p-4 rounded-xl border text-sm font-medium"></div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-extrabold text-white">Client Appointment List</h1>
                        <p class="text-sm text-slate-400">
                            View client bookings and modify any client's appointment date or assigned senior mechanic.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-xs text-slate-400 bg-slate-800/80 px-3.5 py-2 rounded-xl border border-slate-700">
                            Total Appointments: <strong class="text-amber-400"><?= count($appointments) ?></strong>
                        </div>
                        <a href="admin.php?logout=1" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-rose-950 text-xs font-semibold text-slate-300 hover:text-rose-300 border border-slate-700 transition">
                            Log Out
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-xs uppercase tracking-wider text-slate-400 bg-slate-800/40">
                                <th class="py-3.5 px-4">ID</th>
                                <th class="py-3.5 px-4">Client Name</th>
                                <th class="py-3.5 px-4">Phone</th>
                                <th class="py-3.5 px-4">Car Registration No.</th>
                                <th class="py-3.5 px-4">Appointment Date</th>
                                <th class="py-3.5 px-4">Mechanic Name</th>
                                <th class="py-3.5 px-4 text-right">Modify Date / Mechanic</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentsTableBody" class="divide-y divide-slate-800/70 text-sm">
                            <?php foreach ($appointments as $appt): ?>
                            <tr class="hover:bg-slate-800/40 transition" data-id="<?= (int)$appt['id'] ?>">
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-400">#<?= (int)$appt['id'] ?></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-white"><?= htmlspecialchars($appt['client_name']) ?></div>
                                    <div class="text-xs text-slate-400"><?= htmlspecialchars($appt['address']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-200"><?= htmlspecialchars($appt['phone']) ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-1 rounded-md bg-slate-800 border border-slate-700 font-mono text-xs text-amber-300">
                                        <?= htmlspecialchars($appt['car_license_no']) ?>
                                    </span>
                                    <div class="text-xs text-slate-500 mt-0.5">Engine: <?= htmlspecialchars($appt['car_engine_no']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <input type="date" value="<?= htmlspecialchars($appt['appointment_date']) ?>"
                                        class="row-date rounded-lg bg-slate-800 border border-slate-700 px-2.5 py-1.5 text-xs text-white focus:border-amber-400">
                                </td>
                                <td class="py-3.5 px-4">
                                    <select class="row-mechanic rounded-lg bg-slate-800 border border-slate-700 px-2.5 py-1.5 text-xs text-white focus:border-amber-400">
                                        <?php foreach ($mechanics as $m): ?>
                                        <option value="<?= (int)$m['id'] ?>" <?= (int)$m['id'] === (int)$appt['mechanic_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-2">
                                    <button type="button" onclick="saveAppointmentRow(<?= (int)$appt['id'] ?>, this)"
                                        class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition">
                                        Save Changes
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
    function showAdminBanner(type, msg) {
        const b = document.getElementById('adminBanner');
        if (!b) return;
        b.className = 'mb-6 p-4 rounded-xl border text-sm font-medium ' + (
            type === 'error'
                ? 'bg-red-950/70 border-red-500/50 text-red-200'
                : 'bg-emerald-950/70 border-emerald-500/50 text-emerald-200'
        );
        b.textContent = msg;
        b.classList.remove('hidden');
    }

    async function saveAppointmentRow(appointmentId, btn) {
        const row = btn.closest('tr');
        const newDate = row.querySelector('.row-date').value;
        const newMechanic = row.querySelector('.row-mechanic').value;

        try {
            const res = await fetch('api.php?action=update_appointment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    appointment_id: appointmentId,
                    appointment_date: newDate,
                    mechanic_id: newMechanic
                })
            });
            const data = await res.json();
            if (res.ok && data.status === 'success') {
                showAdminBanner('success', data.message);
            } else {
                showAdminBanner('error', data.message || 'Could not update appointment.');
            }
        } catch (err) {
            showAdminBanner('error', 'Error communicating with server.');
        }
    }
    </script>
</body>
</html>
