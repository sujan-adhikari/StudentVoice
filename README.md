# College Complaint Management System (CCMS)
### Tribhuvan University (TU) - Bachelor of Computer Applications (BCA) 4th Semester Project I

A modern, transparent, and responsive web application designed for college students to lodge, track, and resolve campus grievances. Built with standard native **PHP (PDO)**, **MySQL**, **Bootstrap 5.3**, **Vanilla JavaScript**, and **Chart.js**.

---

## 📌 Features Overview

### 🎓 Student Features
- **Account Registration & Login**: Session-based authentication with Bcrypt password hashing.
- **Student Dashboard**: Live summary cards (Total Lodged, Pending, In Progress, Resolved).
- **Lodge Grievance (CREATE)**:
  - Category selection (Infrastructure, Labs, Wi-Fi, Library, Hostel, etc.).
  - Detailed issue description with live character counter.
  - Optional photographic evidence upload (JPG, PNG, WEBP max 2MB) with live client preview.
  - Automatically generates readable reference numbers: `CMP-YYYY-XXXX`.
- **My Complaints (READ)**:
  - Tabular list of student complaints with status badges.
  - Filter pills: All, Pending, In Progress, Resolved, Rejected.
  - Keyword search by reference number or subject.
- **Track Status & Timeline**:
  - Full details view with attached evidence.
  - Official administration remarks.
  - Step-by-step resolution history timeline.
- **Edit Grievance (UPDATE)**: Students can edit details or replace evidence while the status is still **Pending**.
- **Delete Grievance (DELETE)**: Students can withdraw pending complaints; safely cleans up uploaded images.
- **Profile & Password**: Update contact phone number and change password with current password verification.

### 🛡️ Administrator Features
- **Admin Dashboard**:
  - Real-time statistics cards (Total Lodged, Pending, In Progress, Resolved, Rejected, Registered Students).
  - Status ratio chart (Chart.js doughnut graph).
  - Category breakdown chart (Chart.js bar chart).
  - Quick action table for recent submissions.
- **Grievance Management (READ, UPDATE, DELETE)**:
  - Master search across reference ID, subject, or student name.
  - Multi-filter by category and grievance status.
  - Update complaint status (`Pending` &rarr; `In Progress` &rarr; `Resolved` or `Rejected`).
  - Add official remarks / resolution notes visible to students.
  - Automatic audit logging in `complaint_logs`.
  - Delete spam/invalid complaints with image cleanup.
- **Category Management (Full CRUD)**:
  - Add new campus categories.
  - Edit existing categories.
  - Delete categories with referential integrity guard (`ON DELETE RESTRICT`).
- **User Directory**: View registered students, email, phone, and total complaints lodged.

### 🌐 Public Features
- **Public Complaint Tracker**: Students can look up their complaint reference number directly on the home page without logging in.

---

## 💻 Technology Stack

- **Frontend**: HTML5, CSS3 (Custom Design System + Bootstrap 5.3 CDN), Vanilla JavaScript, Bootstrap Icons CDN, Google Fonts (Inter).
- **Charts**: Chart.js 4.4.
- **Backend**: Native PHP 8.x (No frameworks, pure and easy to understand).
- **Database**: MySQL / MariaDB (InnoDB engine with foreign keys).
- **Data Access**: PDO (PHP Data Objects) with Prepared Statements.
- **Target Server**: XAMPP (Apache + MySQL on Windows).

---

## 🚀 Step-by-Step Installation Guide (XAMPP)

### Step 1: Install & Start XAMPP
1. Download and install [XAMPP for Windows](https://www.apachefriends.org/).
2. Open the **XAMPP Control Panel**.
3. Start **Apache** and **MySQL** services (both should show green indicators).

### Step 2: Place the Project in `htdocs`
Copy this folder `Complaint-Management-System` into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\Complaint-Management-System
```
*(Or if XAMPP is on drive `E:\`, place it in `E:\XAMPP Server\htdocs\Complaint-Management-System`)*

### Step 3: Create and Import the MySQL Database
1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Databases** tab.
3. Enter database name: `college_complaint_system` and click **Create** (Collation: `utf8mb4_unicode_ci`).
4. Select the newly created `college_complaint_system` database on the left sidebar.
5. Click on the **Import** tab at the top.
6. Click **Choose File**, browse to:
   ```
   Complaint-Management-System/database.sql
   ```
7. Click the **Import** button at the bottom of the page.
8. You should see a success message: `Import has been successfully finished`.

### Step 4: Verify Database Connection Config
Open `config/database.php` and confirm your MySQL credentials match your XAMPP settings:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');       // Default XAMPP has no password
define('DB_NAME', 'college_complaint_system');
define('DB_PORT', 3306);
```

### Step 5: Open the Application in Your Browser
Open Google Chrome or any browser and visit:
```
http://localhost/Complaint-Management-System/
```

---

## 🔑 Demo Login Credentials

For quick evaluation and viva demonstrations, the database includes two pre-seeded accounts:

| Role | Email Address | Password | Permissions |
|---|---|---|---|
| **Administrator** | `admin@college.edu` | `admin123` | Full administrative control, review complaints, update remarks, manage categories. |
| **Student** | `student@college.edu` | `student123` | Lodge complaints, track status, edit/delete pending complaints, profile. |

*You can also click **Register** to create a brand-new student account.*

---

## 📂 Project Directory Structure

```
Complaint-Management-System/
│
├── config/
│   ├── database.php            # PDO connection instance with error handling
│   └── constants.php           # Constants, file paths, dynamic BASE_URL detector
│
├── includes/
│   ├── auth.php                # Session authentication, login/logout, and role guards
│   ├── functions.php           # Helper functions: e(), flash messages, CSRF, upload handler
│   ├── header.php              # Global head, fonts, Bootstrap CSS, custom styles
│   ├── navbar.php              # Responsive top navigation with active user menu
│   ├── sidebar.php             # Contextual sidebar (Student vs Admin)
│   └── footer.php              # Global footer, Bootstrap JS bundle, main.js
│
├── assets/
│   ├── css/
│   │   └── style.css           # Custom academic design system & timeline styling
│   └── js/
│       └── main.js             # Vanilla JS: image preview, character counter, alert timers
│
├── uploads/                    # Stores student uploaded evidence images
│   ├── .htaccess               # Disables PHP script execution inside uploads folder
│   └── index.html              # Blank 403 Forbidden index
│
├── student/
│   ├── dashboard.php           # Student metrics cards & recent complaints
│   ├── submit_complaint.php    # CREATE: Lodge grievance with optional image
│   ├── my_complaints.php       # READ: Filtered list of student's grievances
│   ├── view_complaint.php      # READ: Details, evidence photo, timeline & remarks
│   ├── edit_complaint.php      # UPDATE: Edit grievance (only if Pending)
│   ├── delete_complaint.php    # DELETE: Delete grievance (only if Pending)
│   └── profile.php             # Student profile info & change password
│
├── admin/
│   ├── dashboard.php           # Admin metrics, Chart.js doughnut & bar graphs
│   ├── complaints.php          # READ: Master complaint directory with search & filters
│   ├── view_complaint.php      # UPDATE: Update status, post official remarks & log timeline
│   ├── delete_complaint.php    # DELETE: Purge complaint & unlinks stored image
│   ├── categories.php          # Full CRUD: Add, edit, delete campus categories
│   └── users.php               # READ: Registered students audit directory
│
├── docs/
│   ├── project_report.md       # Complete BCA Project I documentation report
│   └── viva_qa_guide.md        # Comprehensive BCA Viva Q&A preparation guide
│
├── index.php                   # Public landing page with instant complaint tracker
├── login.php                   # Unified login page with role detection
├── register.php                # Student registration page with validation
├── logout.php                  # Session destruction and redirect
├── database.sql                # Complete MySQL DDL & Seed Data script
└── README.md                   # This installation and user guide
```

---

## 🔒 Security Best Practices Implemented

1. **SQL Injection Prevention**: 100% of database queries use **PDO Prepared Statements** with bound parameters (`:param`).
2. **Cross-Site Scripting (XSS) Prevention**: All user-generated outputs are sanitized through `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` via the helper function `e()`.
3. **Password Security**: Passwords are never stored in plain text. Hashed using **Bcrypt** (`password_hash()`) and verified using `password_verify()`.
4. **Cross-Site Request Forgery (CSRF)**: Sensitive forms include hidden `csrf_token` validation fields tied to the active session.
5. **Secure File Uploads**:
   - File size restricted to 2 MB.
   - Whitelist validation for extensions (`jpg`, `jpeg`, `png`, `webp`).
   - Server-side MIME type verification using `finfo_file(FILEINFO_MIME_TYPE)` rather than trusting user-submitted headers.
   - Uploads folder protected with `.htaccess` to block any script execution.
6. **Authorization & Role Guards**: `requireRole('student')` and `requireRole('admin')` enforce strict server-side authorization on every protected page.

---

## 📚 Academic Documentation & Viva Defense

For your university presentation and documentation report:
- See [`docs/project_report.md`](file:///d:/Complaint-Management-System/docs/project_report.md) for the project report template (Introduction, SRS, ERD, Schema, Testing).
- See [`docs/viva_qa_guide.md`](file:///d:/Complaint-Management-System/docs/viva_qa_guide.md) for 25+ typical viva questions and sample answers covering PHP, MySQL, and software design concepts.
