<?php
/**
 * CSE 391: Programming for the Internet - Assignment 3
 * User Panel (index.php) - Client Online Appointment Booking
 */
require_once __DIR__ . '/db.php';
$today = date('Y-m-d');
$initialMechanics = getMechanicsAvailability($pdo, $today);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex AutoCare Workshop — Online Mechanic Appointment System (CSE 391)</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="min-h-screen flex flex-col bg-slate-950 text-slate-100">

    <!-- Top Navigation Bar (Single Overlapping Slot for Admin Panel Button) -->
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center text-slate-950 font-black text-lg shadow-lg shadow-amber-500/20">
                    AA
                </div>
                <div>
                    <span class="font-bold text-lg tracking-tight text-white">Apex AutoCare Workshop</span>
                    <span class="block text-xs text-slate-400">CSE 391 Assignment 3 • User Panel</span>
                </div>
            </div>
            <nav class="flex items-center">
                <a href="admin.php" class="px-4 py-2 rounded-xl text-sm font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-md shadow-amber-500/20 transition">
                    Admin Panel
                </a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div id="statusBanner" class="hidden"></div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Column: Live Senior Mechanics Availability -->
            <section class="lg:col-span-5 space-y-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-lg font-bold text-white">Senior Mechanics Availability</h2>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-300 border border-amber-500/30 font-medium">Max 4 Cars / Day</span>
                    </div>
                    <p class="text-xs text-slate-400 mb-4">
                        Select your appointment date in the form to inspect real-time free slots for each of our 5 senior mechanics. Click any available mechanic card to select them.
                    </p>
                    <div id="mechanicsGrid" class="space-y-3">
                        <?php foreach ($initialMechanics as $m): 
                            $free = (int)$m['free_slots'];
                            $max  = (int)$m['max_daily_slots'];
                            $booked = (int)$m['booked_slots'];
                            $isFull = $free <= 0;
                        ?>
                        <div class="mechanic-card rounded-xl border border-slate-700/80 bg-slate-800/70 p-4 <?= $isFull ? 'mechanic-full' : '' ?>">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div>
                                    <h4 class="font-bold text-white text-base"><?= htmlspecialchars($m['name']) ?></h4>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($m['specialty']) ?></p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $isFull ? 'bg-red-500/20 text-red-300 border border-red-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' ?>">
                                    <?= $isFull ? 'Occupied (0 Free)' : $free . ' Free Place' . ($free === 1 ? '' : 's') ?>
                                </span>
                            </div>
                            <div class="w-full bg-slate-700/60 h-2 rounded-full overflow-hidden my-2">
                                <div class="<?= $isFull ? 'bg-red-500' : 'bg-amber-400' ?> h-full" style="width: <?= ($booked / $max) * 100 ?>%"></div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-400 mt-1">
                                <span><?= (int)$m['experience_years'] ?> yrs exp.</span>
                                <span>Assigned: <?= $booked ?> / <?= $max ?> cars</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Right Column: Client Appointment Booking Form -->
            <section class="lg:col-span-7">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
                    <div class="mb-6">
                        <h1 class="text-2xl font-extrabold text-white">Book Your Car Workshop Appointment</h1>
                        <p class="text-sm text-slate-400 mt-1">
                            Complete all vehicle and owner details below. The server validates mechanic slot availability and duplicate bookings at every step.
                        </p>
                    </div>

                    <form id="bookingForm" method="POST" action="api.php?action=book" novalidate class="space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="client_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Client Full Name *</label>
                                <input type="text" id="client_name" name="client_name" placeholder="e.g., Tanvir Ahmed"
                                    class="w-full rounded-xl bg-slate-800/90 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400">
                                <p id="client_name_error" class="hidden text-xs text-red-400 mt-1"></p>
                            </div>

                            <div>
                                <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Phone Number (Digits Only) *</label>
                                <input type="text" id="phone" name="phone" placeholder="e.g., 01712345678"
                                    class="w-full rounded-xl bg-slate-800/90 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400">
                                <p id="phone_error" class="hidden text-xs text-red-400 mt-1"></p>
                            </div>
                        </div>

                        <div>
                            <label for="address" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Residential / Billing Address *</label>
                            <input type="text" id="address" name="address" placeholder="e.g., House 14, Road 7, Dhanmondi, Dhaka"
                                class="w-full rounded-xl bg-slate-800/90 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400">
                            <p id="address_error" class="hidden text-xs text-red-400 mt-1"></p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="car_license_no" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Car License / Registration No. *</label>
                                <input type="text" id="car_license_no" name="car_license_no" placeholder="e.g., DHAKA-METRO-GA-32-4411"
                                    class="w-full rounded-xl bg-slate-800/90 border border-slate-700 px-3.5 py-2.5 text-sm text-white uppercase focus:outline-none focus:border-amber-400">
                                <p id="car_license_no_error" class="hidden text-xs text-red-400 mt-1"></p>
                            </div>

                            <div>
                                <label for="car_engine_no" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Car Engine Number (Numbers Only) *</label>
                                <input type="text" id="car_engine_no" name="car_engine_no" placeholder="e.g., 884920194"
                                    class="w-full rounded-xl bg-slate-800/90 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400">
                                <p id="car_engine_no_error" class="hidden text-xs text-red-400 mt-1"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="appointment_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Appointment Date *</label>
                                <input type="date" id="appointment_date" name="appointment_date" value="<?= htmlspecialchars($today) ?>"
                                    class="w-full rounded-xl bg-slate-800/90 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400">
                                <p id="appointment_date_error" class="hidden text-xs text-red-400 mt-1"></p>
                            </div>

                            <div>
                                <label for="mechanic_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">Select Desired Mechanic *</label>
                                <select id="mechanic_id" name="mechanic_id"
                                    class="w-full rounded-xl bg-slate-800/90 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400">
                                    <option value="">-- Select Desired Senior Mechanic --</option>
                                    <?php foreach ($initialMechanics as $m): 
                                        $free = (int)$m['free_slots'];
                                        $isFull = $free <= 0;
                                    ?>
                                    <option value="<?= (int)$m['id'] ?>" <?= $isFull ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($m['name']) ?> — <?= $isFull ? 'FULL (0 Free Places)' : $free . ' of ' . (int)$m['max_daily_slots'] . ' Free Places' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <p id="mechanic_id_error" class="hidden text-xs text-red-400 mt-1"></p>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="w-full py-3 px-6 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 transition">
                                Confirm &amp; Submit Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <script src="assets/js/app.js"></script>
</body>
</html>
