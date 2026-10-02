# CAMS Portal (Client and Appointment Management System)

A web app for managing vehicle service appointments and client records. Built as a senior capstone project (SDC480), drawing from an Air Force vehicle management background to create a practical solution for shop technicians and customers.

---

## 📽️ Project Video Demo
* **Short 5-Minute Walkthrough:** [Watch CAMS Portal Demo Video](https://ecpi.hosted.panopto.com/Panopto/Pages/Viewer.aspx?id=f52ac346-9f66-4a0f-aeaa-b4d70044f099)

---

## What the Project Does

The CAMS Portal is a full-stack PHP and MySQL web application designed to handle auto repair scheduling and shop management:

* **Customer Portal (`dashboard.php`, `booking.php`):** Clients can register, log in, and request service appointments by entering their vehicle details (make, model, year, VIN, mileage) and a brief summary of the issue.
* **Double-Booking & Schedule Checks:** The backend checks requested times against existing database entries and blocks overlapping appointments. It also limits bookings to normal shop hours (Monday–Friday, 8 AM – 5 PM).
* **Admin CRM Dashboard (`admin_dashboard.php`):** Techs and managers can log in, view all incoming appointments, update repair statuses (`Scheduled`, `In-Progress`, `Completed`, `Cancelled`), and save internal staff notes (like parts orders).
* **Global Search (`search.php`):** Admin search tool using SQL `LIKE` wildcards to quickly find records by client name, vehicle model, or job status.
* **Interactive Weekly Calendar (`calendar.php`):** A visual grid showing the week's schedule so shop techs can check overall capacity and click on appointments to edit them.

---

## Security Features

Key security practices implemented across the application:

* **SQL Injection Protection:** Used 100% PDO prepared statements (`prepare()` and `execute()`) for all database queries across login, search, booking, and admin updates.
* **XSS Prevention:** Passed dynamic user inputs through `htmlspecialchars()` before displaying them on the screen to block script injection.
* **Password Hashing:** Stored user passwords using `BCrypt` (`password_hash()`) instead of plain text.
* **Role-Based Access Control:** Checked `$_SESSION` data on every page load to prevent regular users from accessing administrative pages directly through the URL.

---

## Database Design

The project runs on a MySQL database (`cams_db`) with two main linked tables:

```text
+-----------------------------------+       +---------------------------------------+
|               users               |       |             appointments              |
+-----------------------------------+       +---------------------------------------+
| id (INT, PK, AUTO_INCREMENT)      |<------| id (INT, PK, AUTO_INCREMENT)        |
| name (VARCHAR(100))               |   1:N | user_id (INT, FK -> users.id)         |
| email (VARCHAR(150), UNIQUE)      |       | service_type (VARCHAR(100))           |
| password (VARCHAR(255))           |       | appointment_date (DATETIME)           |
| role (ENUM('client', 'admin'))    |       | vehicle_make (VARCHAR(50))            |
| created_at (TIMESTAMP)            |       | vehicle_model (VARCHAR(50))           |
+-----------------------------------+       | vehicle_year (INT)                    |
                                            | vehicle_vin (VARCHAR(17), NULL)       |
                                            | mileage (INT, NULL)                   |
                                            | contact_phone (VARCHAR(20))           |
                                            | contact_preference (VARCHAR(20))      |
                                            | service_summary (VARCHAR(255))        |
                                            | service_details (TEXT, NULL)          |
                                            | status (ENUM)                         |
                                            | staff_notes (TEXT, NULL)              |
                                            | created_at (TIMESTAMP)                |
                                            +---------------------------------------+

```

* Joined `appointments.user_id` to `users.id` with `ON DELETE CASCADE` so deleting a user removes their associated bookings automatically.

---

## Languages & Tech Stack

* **PHP 7.4+** (Backend & Session Management)
* **MySQL & PDO** (Database & Parameterized Queries)
* **HTML5 & CSS3** (Styling, layouts, and status badges)
* **JavaScript** (Frontend validation)
* **Apache / XAMPP** (Local development environment)

---

## How to Run It Locally

1. Clone this repo into your local web folder (e.g., `htdocs` in XAMPP):
```bash
git clone [https://github.com/bengeo7450/CAMS_Portal-.git](https://github.com/bengeo7450/SDC480-Project.git)

```


2. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and import `schema.sql` to build `cams_db`.
3. Verify your database credentials in `db.php`.
4. Start Apache and MySQL in XAMPP control panel.
5. Navigate to `http://localhost/SDC480-Project` in your browser.

---

## Project Summary

### What Went Well:

1. **Database & Security:** Using PDO prepared statements and BCrypt password hashing made database access secure against SQL injection. Adding `htmlspecialchars()` sanitization across all dynamic user inputs also protected against XSS vectors.
2. **Scheduling Rules:** Combining client intake into `booking.php` and enforcing backend time-slot validation prevented duplicate or overlapping appointments.
3. **Admin CRM Tools:** Setting up the weekly interactive calendar (`calendar.php`) and global search engine made it efficient to manage records and update technician notes from the admin dashboard.

### Things to Improve:

1. **Cloud Hosting:** Kept the project on a local XAMPP setup to prioritize database stability and functional reliability over migrating to an AWS EC2/RDS cloud instance.
2. **Page Redirects:** Post-action page routing could be polished further, such as redirecting clients straight back to `dashboard.php` with a success flag when an appointment is canceled.

---

## License

Licensed under the GNU General Public License v3.0.

```

```
