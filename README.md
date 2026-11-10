# CSE 391: Programming for the Internet
## Assignment 3: PHP and MySQL Project — Apex AutoCare Online Appointment System

This repository contains the complete **HTML5, CSS3, JavaScript, PHP, and MySQL** implementation for the CSE 391 Assignment 3 Car Workshop Online Appointment System.

### Features Implemented

1. **User Panel (`index.php`)**
   - Entry fields for **Client Name**, **Address**, **Phone**, **Car License Number (Registration Number)**, **Car Engine Number**, **Appointment Date**, and **Desired Senior Mechanic**.
   - **Live Server-Client Communication (`api.php`)**:
     - Dynamically queries the MySQL database whenever the Appointment Date changes to display **how many free places (out of 4 maximum daily slots)** are available for each of the 5 senior mechanics.
     - Performs a live check as the client enters their Phone / Car License Number and Date to alert them immediately if they already booked an appointment on that date.
     - Enforces server-side and client-side validation:
       - No empty fields allowed.
       - **Phone** must contain only numbers (7–15 digits).
       - **Car Engine Number** must contain only numbers.
       - **Appointment Date** must be a valid calendar date.
       - **Mechanic Selection** is mandatory and prevents selecting a mechanic whoalready has 4 active cars on that date.

2. **Admin Panel (`admin.php`)**
   - Displays the complete **Client Appointment List** table showing:
     - **Client Name**
     - **Phone**
     - **Car Registration Number (License No.)**
     - **Appointment Date**
     - **Mechanic Name**
     - *(Plus Address, Car Engine Number, and Live Edit Controls)*
   - **Reschedule & Reassign Mechanic**:
     - Admin can change the **Appointment Date** and/or **Assigned Mechanic** for any specific client.
     - Changing the date inside the Admin Edit Modal dynamically fetches available mechanics and their remaining free slots for that target date, ensuring no mechanic exceeds 4 cars/day.

3. **Help & Validation Guide**
   - Built-in interactive Help drawer/section on both `index.php` and `admin.php` explaining booking rules, validation formats, and mechanic slot limits.

---

### Setup & Deployment Instructions

1. **Import the Database**
   - Open **phpMyAdmin** (via XAMPP, WAMP, MAMP, or your web host cPanel) or MySQL CLI.
   - Import `database.sql` (creates the `cse391_workshop` database, `mechanics` table with 5 senior mechanics, and `appointments` table with initial sample records).

2. **Configure Database Credentials**
   - Open `db.php` and update `$db_host`, `$db_name`, `$db_user`, and `$db_pass` if your MySQL server uses credentials other than `root` with an empty password.

3. **Run Locally or Upload to Online Hosting**
   - Place the folder inside `htdocs/cse391_assignment3/` (XAMPP) or `www/` (WAMP) and visit:
     - **User Panel**: `http://localhost/cse391_assignment3/index.php`
     - **Admin Panel**: `http://localhost/cse391_assignment3/admin.php`
   - For online submission (e.g., InfinityFree, AwardSpace, 000webhost, or Railway), upload all files to `public_html/`, import `database.sql` into your hosting MySQL database, and update `db.php`.
