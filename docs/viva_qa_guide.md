# BCA 4th Semester Project I: Viva Examination Guide & Code Defense
### College Complaint Management System (CCMS)
**University**: Tribhuvan University (TU) &bull; **Program**: BCA &bull; **Course**: Project I

This guide prepares you for your internal and external viva evaluations. It explains key concepts, architecture decisions, and code snippets line-by-line so you can confidently answer any examiner question.

---

## 🎓 SECTION 1: ARCHITECTURE & SYSTEM DESIGN

### Q1: What architecture did you use for this project?
**Answer:**
> "I implemented a **3-Tier Modular Web Architecture**:
> 1. **Presentation Tier**: Built using HTML5, Bootstrap 5.3, custom CSS3, and Vanilla JavaScript for responsive user interfaces and Chart.js analytics.
> 2. **Application (Business Logic) Tier**: Native PHP scripts handling authentication, input validation, role-based authorization, and business rules (e.g. only pending complaints can be modified).
> 3. **Data Tier**: A relational MySQL database accessed securely using PDO (PHP Data Objects) with prepared statements.
>
> I organized the project into modular folders: `config/` for database and constants, `includes/` for reusable components and guards, `student/` for student operations, `admin/` for administrative modules, and `uploads/` for evidence photos."

---

### Q2: How does data flow from the HTML form to MySQL and back to the user?
**Answer:**
> "The data workflow follows 5 clear stages:
> 1. **User Input (HTML Form)**: The student enters details into an HTML `<form method=\"POST\">`.
> 2. **Transmission**: When submitted, the browser sends an HTTP POST request to the PHP backend.
> 3. **PHP Server Processing**:
>    - PHP verifies the CSRF token to prevent forgery.
>    - PHP sanitizes and validates input (checking lengths, data types, and image MIME types).
> 4. **MySQL Database Storage**:
>    - PHP uses **PDO Prepared Statements** with bound parameters (`:param`) to execute an `INSERT` or `UPDATE` query.
>    - MySQL stores the record safely and returns a status/insert ID.
> 5. **Feedback & HTML Response**:
>    - PHP sets a session flash message (e.g. 'Complaint submitted successfully').
>    - PHP redirects the browser (PRG pattern: Post/Redirect/Get) to `my_complaints.php`.
>    - The page renders the fresh record with HTML escaping via `e()` to prevent XSS."

---

## 🔒 SECTION 2: SECURITY & DATABASE (CRITICAL VIVA TOPICS)

### Q3: Why did you use PDO instead of `mysqli` or older `mysql_*` functions?
**Answer:**
> "I chose **PDO (PHP Data Objects)** for three reasons:
> 1. **Named Parameters**: PDO allows readable named placeholders (e.g., `:email`, `:id`) rather than confusing `?` positional parameters.
> 2. **Database Portability**: PDO works across multiple database systems (MySQL, PostgreSQL, SQLite) without rewriting application logic.
> 3. **Robust Exception Handling**: With `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, SQL errors throw catchable exceptions, preventing silent database failures."

---

### Q4: How did you prevent SQL Injection in your project?
**Answer:**
> "I eliminated SQL Injection by using **PDO Prepared Statements with parameter binding** for all SQL queries. User input is never concatenated directly into SQL strings.
> 
> **Example from our code (`login.php`):**
> ```php
> // CORRECT: Prepared statement separates SQL command from user data
> $stmt = $pdo->prepare(\"SELECT * FROM users WHERE email = :email LIMIT 1\");
> $stmt->execute([':email' => $email]);
> $user = $stmt->fetch();
> ```
> Even if a user enters `' OR '1'='1`, MySQL treats it purely as a literal string value, not as executable SQL syntax."

---

### Q5: How did you prevent Cross-Site Scripting (XSS)?
**Answer:**
> "XSS occurs when malicious JavaScript is injected into the database and executed when another user views the page.
> To prevent this, I created a global helper function `e()` in `includes/functions.php`:
> ```php
> function e(?string $string): string {
>     return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
> }
> ```
> Every time user data is printed in HTML, it is wrapped in `e()`, converting characters like `<`, `>`, and `\"` into safe HTML entities (`&lt;`, `&gt;`, `&quot;`)."

---

### Q6: Why should passwords never be stored using MD5, SHA1, or plain text?
**Answer:**
> "Plain text exposes passwords immediately if the database is compromised. Older algorithms like MD5 and SHA-1 are fast hashing algorithms designed for file integrity, not passwords. They are vulnerable to brute-force attacks and precomputed Rainbow Tables.
> 
> In this project, I used **Bcrypt** through PHP's native:
> ```php
> // Hash password during registration
> $hash = password_hash($password, PASSWORD_BCRYPT);
> 
> // Verify password during login
> if (password_verify($password, $user['password'])) { ... }
> ```
> Bcrypt incorporates a unique random salt for every user and uses a configurable work factor (computational cost), making brute-force attacks computationally unfeasible."

---

### Q7: What is CSRF (Cross-Site Request Forgery) and how did you prevent it?
**Answer:**
> "CSRF is an attack where an unauthorized site tricks an authenticated user's browser into executing an unwanted action (like deleting a complaint).
> 
> I protected against CSRF using **anti-CSRF session tokens**:
> 1. In `includes/functions.php`, `getCsrfToken()` generates a 32-byte cryptographic random token stored in `$_SESSION['csrf_token']`.
> 2. Forms include this token in a hidden field via `csrfField()`.
> 3. When submitted, the server compares the submitted token with the session token using `hash_equals($_SESSION['csrf_token'], $token)` before executing any write or delete operations."

---

### Q8: What security checks did you implement for image uploads?
**Answer:**
> "Uploading files poses serious risks if an attacker uploads a malicious PHP script. I implemented 4 layers of protection in `handleImageUpload()` in `includes/functions.php`:
> 1. **Size Restriction**: Maximum 2 MB limit (`MAX_FILE_SIZE`).
> 2. **Extension Whitelist**: Only `.jpg`, `.jpeg`, `.png`, and `.webp` extensions are allowed.
> 3. **Server-Side MIME Inspection**: Instead of trusting the browser's `$_FILES['image']['type']` (which can be faked), I inspect the file header on the server using PHP Fileinfo:
>    ```php
>    $finfo = finfo_open(FILEINFO_MIME_TYPE);
>    $mimeType = finfo_file($finfo, $file['tmp_name']);
>    ```
> 4. **Randomized Filenames**: Files are saved with unique random names (e.g. `evidence_20260916_180320_a1b2c3d4.jpg`) to prevent directory traversal and file overwriting.
> 5. **`.htaccess` Execution Block**: In the `uploads/` folder, an `.htaccess` rule disables execution of `.php` scripts even if one were somehow uploaded."

---

## 📊 SECTION 3: DATABASE DESIGN & RELATIONSHIPS

### Q9: Explain your database tables and foreign keys.
**Answer:**
> "The database has 4 tables:
> 1. `users`: Stores user accounts with a `role` ENUM (`'student'`, `'admin'`).
> 2. `categories`: Stores campus grievance categories (e.g., Infrastructure, Labs, Wi-Fi).
> 3. `complaints`: Stores grievance records. 
>    - Foreign key `user_id` &rarr; `users(id)` with `ON DELETE CASCADE`.
>    - Foreign key `category_id` &rarr; `categories(id)` with `ON DELETE RESTRICT`.
> 4. `complaint_logs`: Stores the timeline history of every status update and admin remark.
>    - Foreign key `complaint_id` &rarr; `complaints(id)` with `ON DELETE CASCADE`.
>    - Foreign key `updated_by` &rarr; `users(id)` with `ON DELETE CASCADE`."

---

### Q10: What is the difference between `ON DELETE CASCADE` and `ON DELETE RESTRICT`? Why did you use both?
**Answer:**
> - **`ON DELETE CASCADE`**: When a parent record is deleted, all related child records are deleted automatically.
>   - *Where I used it*: On `complaints` &rarr; when a complaint is deleted, all its timeline entries in `complaint_logs` are deleted automatically.
> - **`ON DELETE RESTRICT`**: Prevents the deletion of a parent record if child records are linked to it.
>   - *Where I used it*: On `categories` &rarr; if an administrator tries to delete the 'Computer Labs' category while complaints are linked to it, MySQL blocks the deletion to preserve data integrity and prevent orphaned complaints."

---

### Q11: What is a Database Transaction and where did you use it?
**Answer:**
> "A transaction ensures that a group of SQL operations either all succeed together or all fail together (**ACID: Atomicity**).
> 
> In `student/submit_complaint.php` and `admin/view_complaint.php`, I wrapped multi-table writes inside transactions:
> ```php
> $pdo->beginTransaction();
> // 1. Update complaints table
> // 2. Insert timeline log into complaint_logs table
> $pdo->commit();
> ```
> If any error occurs between step 1 and step 2, `$pdo->rollBack()` cancels the changes, ensuring database consistency."

---

## 💻 SECTION 4: CRUD FUNCTIONALITY IN YOUR PROJECT

### Q12: How is CRUD demonstrated in your project?
**Answer:**
> "CRUD is implemented for two core entities:
> 
> **1. Complaints CRUD**:
> - **Create**: Student lodges a grievance via `student/submit_complaint.php` with optional photo upload.
> - **Read**: Student views their grievances in `student/my_complaints.php`; Admin reviews all in `admin/complaints.php`.
> - **Update**: Student can update details in `student/edit_complaint.php` (only while status is 'Pending'); Admin updates status and remarks in `admin/view_complaint.php`.
> - **Delete**: Student can withdraw pending complaints in `student/delete_complaint.php`; Admin can delete complaints in `admin/delete_complaint.php`.
> 
> **2. Categories CRUD**:
> - Admin creates, reads, updates, and deletes campus categories in `admin/categories.php`."

---

### Q13: Why can a student only edit or delete a complaint when its status is 'Pending'?
**Answer:**
> "This is a key **business rule**:
> Once an administrator updates the status to 'In Progress' or 'Resolved', official resources and personnel (e.g. technicians, electricians) have already been mobilized. Allowing students to modify or delete the complaint after action has started would create false records and disrupt institutional audits."

---

## 📈 SECTION 5: DASHBOARD & ANALYTICS

### Q14: How does Chart.js get data from MySQL and PHP?
**Answer:**
> "In `admin/dashboard.php`:
> 1. PHP runs SQL aggregate queries (e.g. `COUNT(c.id) GROUP BY cat.id`).
> 2. PHP organizes the counts into PHP arrays (`$chartLabels`, `$chartData`).
> 3. PHP encodes the arrays into JSON format using `json_encode()`:
>    ```javascript
>    labels: <?= json_encode($chartLabels) ?>,
>    data: <?= json_encode($chartData) ?>
>    ```
> 4. Chart.js consumes this JSON data on the client side and renders responsive canvas charts (doughnut and bar charts)."

---

## 💡 SECTION 6: QUICK DEFINITIONS FOR VIVA

| Term | Simple Definition for Viva |
|---|---|
| **Session** | A server-side mechanism to store user state (e.g. user ID, role) across multiple HTTP requests. |
| **Prepared Statement** | A pre-compiled SQL query where user data is supplied separately through placeholders, preventing SQL Injection. |
| **Bcrypt** | An adaptive, salted cryptographic hashing algorithm used by PHP's `password_hash()` to secure passwords. |
| **CSRF Token** | A random, secret cryptographic token shared between server and client to verify the request originated from the genuine user. |
| **Referential Integrity** | A database rule ensuring foreign key values correspond to valid existing primary key values in the referenced table. |
| **MIME Type** | A media format standard (e.g. `image/png`, `image/jpeg`) identifying the true nature of a file regardless of its extension. |
