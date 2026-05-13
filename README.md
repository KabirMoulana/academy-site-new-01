# EduCore Academy Management System
## Full-Stack Setup Guide — PHP + MySQL + XAMPP

---

## 📁 Project Structure

```
academy-db/
├── index.html                     ← Landing / Home page
├── educore.sql                    ← Full database schema + seed data
├── config/
│   └── db.php                     ← DB connection + shared helpers
├── api/
│   ├── auth.php                   ← Login, logout, profile, password reset
│   ├── users.php                  ← User CRUD (all roles)
│   ├── students.php               ← Student CRUD + statistics
│   ├── lecturers.php              ← Lecturer CRUD + access control
│   ├── parents.php                ← Parent CRUD + child linking
│   ├── subjects.php               ← Subject CRUD
│   ├── batches.php                ← Batch CRUD
│   ├── enrollments.php            ← Enroll / unenroll / re-enroll
│   ├── sessions.php               ← Class sessions + link/recording upload
│   ├── attendance.php             ← Mark / edit / delete attendance
│   ├── results.php                ← Exam result upload / edit / delete
│   ├── payments.php               ← Record / approve / delete payments
│   ├── receipts.php               ← Generate / delete receipts
│   ├── feedback.php               ← Student feedback CRUD
│   ├── announcements.php          ← Post / edit / delete announcements
│   ├── notifications.php          ← Send / mark read / delete notifications
│   ├── materials.php              ← Study material upload / edit / delete
│   ├── performance.php            ← Performance points + leaderboard
│   └── reports.php                ← Generate / delete reports
├── css/
│   └── style.css
├── js/
│   ├── app.js                     ← Shared UI helpers
│   ├── layout.js                  ← Sidebar/topbar builder
│   └── api.js                     ← JavaScript API client
└── pages/
    ├── login.html
    ├── admin-dashboard.html
    ├── manager-dashboard.html
    ├── director-dashboard.html
    ├── lecturer-dashboard.html
    ├── student-dashboard.html
    ├── parent-dashboard.html
    ├── receptionist-dashboard.html
    ├── profile.html
    ├── subjects.html
    ├── batches.html
    ├── payments.html
    ├── receipts.html
    └── leaderboard.html
```

---

## 🚀 Setup Steps

### 1. Start XAMPP
Open the XAMPP Control Panel and start both:
- ✅ **Apache**
- ✅ **MySQL**

---

### 2. Copy Project to htdocs
Copy the entire `academy-db` folder to:
```
C:\xampp\htdocs\academy-db\
```

---

### 3. Create the Database

**Option A — phpMyAdmin (Recommended)**
1. Open your browser → go to `http://localhost/phpmyadmin`
2. Click **Import** in the top menu
3. Click **Choose File** → select `academy-db/educore.sql`
4. Click **Go** at the bottom

**Option B — MySQL CLI**
```bash
mysql -u root -p < C:\xampp\htdocs\academy-db\educore.sql
```

---

### 4. Configure Database Connection (if needed)
Edit `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password (empty by default in XAMPP)
define('DB_NAME', 'educore');
```

---

### 5. Open the App
```
http://localhost/academy-db/
```

---

## 🔐 Default Login Credentials

All passwords are: **`password`**

| Email                       | Role         |
|-----------------------------|--------------|
| admin@educore.lk            | Admin        |
| manager@educore.lk          | Manager      |
| director@educore.lk         | Director     |
| lecturer@educore.lk         | Lecturer     |
| student@educore.lk          | Student      |
| receptionist@educore.lk     | Receptionist |

> To add a Parent account, log in as Admin/Manager and create a user with the "Parent" role, then link a student ID.

---

## 🔗 API Reference

All API endpoints follow the pattern:
```
GET/POST/PUT/DELETE  /api/{module}.php?action={action}&id={id}
```

### Auth
| Method | Action | Description |
|--------|--------|-------------|
| POST | login | Login with email + password |
| POST | logout | End session |
| GET | me | Get current user |
| PUT | update_profile | Update name/email |
| PUT | reset_password | Change password |

### Users
| Method | Action | Description |
|--------|--------|-------------|
| GET | list | List all (filter by ?role=) |
| POST | create | Create user + role record |
| PUT | update | Update name/email/status |
| DELETE | delete | Delete user (cascades) |

### Students / Lecturers / Parents
| Method | Action | Description |
|--------|--------|-------------|
| GET | getByUser | Get by userID |
| GET | list | List all |
| GET | get | Get by ID |
| POST | add | Create new (creates user too) |
| PUT | edit | Update details |
| DELETE | delete | Delete (cascades) |

### Subjects / Batches
`list`, `create`, `update`, `delete`

### Enrollments
`list`, `enroll`, `unenroll`, `reenroll`

### Sessions
`list`, `create`, `upload_link`, `upload_recording`, `delete`

### Attendance
`list` (filter by studentID/sessionID), `mark` (bulk), `edit`, `delete`

### Results / Payments / Receipts / Feedback / Materials
`list`, `upload`/`record`/`generate`/`provide`, `edit`, `delete`

### Announcements / Notifications
`list`, `post`/`send`, `edit`, `delete`, `mark_read`, `mark_all_read`

### Performance & Leaderboard
`list`, `add`, `edit`, `delete`, `total`, `leaderboard`, `rebuild_leaderboard`, `reset_leaderboard`

---

## ⚙️ Session Notes
- Sessions are PHP native sessions stored server-side
- The JS `authGuard()` function on every dashboard page calls `GET /api/auth.php?action=me` — if not logged in, it redirects to `login.html`
- All API endpoints call `requireAuth()` which checks `$_SESSION['userID']`

---

## 🛠️ Extending the System
To add a new module:
1. Create `api/mymodule.php` using the same pattern as existing APIs
2. Add API methods to `js/api.js`
3. Call from any dashboard page using `API.myMethod()`
