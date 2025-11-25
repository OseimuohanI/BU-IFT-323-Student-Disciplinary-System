<?php
// index.php - List students from the `student` table
// Tries to reuse existing project config.php if present, otherwise uses sensible defaults.

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// If $mysqli isn't provided by config.php, create a connection with defaults
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
    $db_user = defined('DB_USER') ? DB_USER : 'root';
    $db_pass = defined('DB_PASS') ? DB_PASS : '';
    $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_errno) {
        http_response_code(500);
        echo 'Database connection failed: ' . htmlspecialchars($mysqli->connect_error);
        exit;
    }
}

// start session for login state
session_start();

// after session_start()
$currentUser = $_SESSION['user'] ?? null;

// add this line to detect current page for nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

/**
 * Simple role-check helper.
 * Adjust the field name used to store role in session if your app uses a different key.
 */
function user_has_role($user, array $roles = []) {
    if (!$user) return false;
    // common session keys: 'role', 'Role', 'user_type', 'UserType'
    $role = $user['role'] ?? $user['Role'] ?? $user['user_type'] ?? $user['UserType'] ?? null;
    if (!$role) return false;
    return in_array(strtolower($role), array_map('strtolower', $roles), true);
}

$canViewRecords = user_has_role($currentUser, ['admin', 'lecturer']);

// NEW: convenience flag for lecturer role
$isLecturer = user_has_role($currentUser, ['lecturer']);

$sql = "SELECT StudentID, EnrollmentNo, FirstName, LastName, DOB, Gender, Email, Phone, CreatedAt
        FROM student
        ORDER BY LastName, FirstName";

$result = $mysqli->query($sql);
if ($result === false) {
    http_response_code(500);
    echo 'Query error: ' . htmlspecialchars($mysqli->error);
    exit;
}

/* --- NEW: compute next enrollment number (EN00001 style) and make it available to the form --- */
$nextEnrollment = 'EN00001';
$enRes = $mysqli->query("SELECT MAX(CAST(SUBSTRING(EnrollmentNo,3) AS UNSIGNED)) AS maxnum FROM student WHERE EnrollmentNo REGEXP '^EN[0-9]+$'");
if ($enRes) {
    $r = $enRes->fetch_assoc();
    $maxnum = (int)($r['maxnum'] ?? 0);
    $num = $maxnum + 1;
    $nextEnrollment = 'EN' . str_pad($num, 5, '0', STR_PAD_LEFT);
    $enRes->free();
}

// NEW: search handling (q = query, field = all|name|enrollment|email|phone)
$searchTerm = trim((string)($_GET['q'] ?? ''));
$searchField = ($_GET['field'] ?? 'all');

$where = [];
$params = [];
$types = '';

if ($searchTerm !== '') {
    $like = '%' . $searchTerm . '%';
    switch ($searchField) {
        case 'enrollment':
            $where[] = "EnrollmentNo LIKE ?";
            $types .= 's'; $params[] = $like;
            break;
        case 'email':
            $where[] = "Email LIKE ?";
            $types .= 's'; $params[] = $like;
            break;
        case 'phone':
            $where[] = "Phone LIKE ?";
            $types .= 's'; $params[] = $like;
            break;
        case 'name':
            // search first/last and full name
            $where[] = "(FirstName LIKE ? OR LastName LIKE ? OR CONCAT(FirstName,' ',LastName) LIKE ?)";
            $types .= 'sss'; $params[] = $like; $params[] = $like; $params[] = $like;
            break;
        default:
            // all fields
            $where[] = "(EnrollmentNo LIKE ? OR FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ? OR Phone LIKE ?)";
            $types .= 'sssss';
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
            break;
    }
}

$baseSql = "SELECT StudentID, EnrollmentNo, FirstName, LastName, DOB, Gender, Email, Phone, CreatedAt FROM student";
if (!empty($where)) {
    $baseSql .= " WHERE " . implode(' AND ', $where);
}
$baseSql .= " ORDER BY LastName, FirstName";

$stmt = $mysqli->prepare($baseSql);
if ($stmt === false) {
    http_response_code(500);
    echo 'Query prepare failed: ' . htmlspecialchars($mysqli->error);
    exit;
}
if (!empty($params)) {
    // bind params (references required)
    $bindNames = [];
    $bindNames[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bindNames[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindNames);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Students - Index</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 p-3 bg-white rounded shadow-sm">
        <div class="d-flex flex-column">
            <h2 class="h3 text-success mb-1">Disciplinary System</h2>
            <div class="text-muted small">Records &amp; Actions</div>
        </div>
        <div class="text-end">
            <?php if ($currentUser): ?>
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <div class="me-3 text-end">
                        <div class="small text-muted">Hello</div>
                        <div>
                            <?php if ($isLecturer): ?>
                                <a href="lecturer_dashboard.php"><strong><?php echo htmlspecialchars($currentUser['FullName'] ?? $currentUser['Username'], ENT_QUOTES, 'UTF-8'); ?></strong></a>
                            <?php else: ?>
                                <strong><?php echo htmlspecialchars($currentUser['FullName'] ?? $currentUser['Username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="btn-group me-2" role="group" aria-label="Navigation">
                        <?php
                            $incidentsClass = ($currentPage === 'incidents.php') ? 'btn btn-primary' : 'btn btn-outline-primary';
                            $studentsClass  = ($currentPage === 'index.php') ? 'btn btn-primary' : 'btn btn-outline-primary';
                        ?>
                        <a href="incidents.php" class="<?php echo $incidentsClass; ?>">Incidents</a>
                        <a href="index.php" class="<?php echo $studentsClass; ?>">Students</a>
                    </div>
                    <form method="post" action="logout.php" class="m-0 p-0 d-inline">
                        <button type="submit" class="btn btn-outline-secondary">Logout</button>
                    </form>
                </div>
            <?php else: ?>
                <button id="btnLogin" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search bar -->
    <form class="row g-2 mb-3" method="get" action="index.php" role="search">
        <div class="col-auto">
            <select name="field" class="form-select">
                <option value="all" <?php echo ($searchField === 'all') ? 'selected' : ''; ?>>All</option>
                <option value="name" <?php echo ($searchField === 'name') ? 'selected' : ''; ?>>Name</option>
                <option value="enrollment" <?php echo ($searchField === 'enrollment') ? 'selected' : ''; ?>>Enrollment No</option>
                <option value="email" <?php echo ($searchField === 'email') ? 'selected' : ''; ?>>Email</option>
                <option value="phone" <?php echo ($searchField === 'phone') ? 'selected' : ''; ?>>Phone</option>
            </select>
        </div>
        <div class="col">
            <input name="q" value="<?php echo htmlspecialchars($searchTerm ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control" type="search" placeholder="Search students (press Enter)" aria-label="Search">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-primary" type="submit">Search</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <?php if ($currentUser && $canViewRecords): ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="mb-0">Students</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">+ Add Student</button>
        </div>
    <?php else: ?>
        <h1 class="mb-4">Students</h1>
    <?php endif; ?>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="loginForm" autocomplete="off" novalidate>
                    <!-- hidden dummy field to reduce autofill -->
                    <input type="text" name="fakeuser" id="fakeuser" autocomplete="off" style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true" tabindex="-1">
                    <div class="modal-header">
                        <h5 class="modal-title">Login</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="loginAlert" class="alert alert-danger d-none" role="alert"></div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input name="username" type="text" class="form-control" required autofocus autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input name="password" type="password" class="form-control" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="loginSubmit" class="btn btn-primary">Sign in</button>
                    </div>
                </form>
            </div>
         </div>
     </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="addStudentForm" autocomplete="off" novalidate>
                    <input type="text" name="fakeuser2" autocomplete="off" style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true" tabindex="-1">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="addStudentAlert" class="alert alert-danger d-none" role="alert"></div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Enrollment No</label>
                                <input name="enrollment_no" type="text" class="form-control" required readonly
                                       value="<?php echo htmlspecialchars($nextEnrollment, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">First name</label>
                                <input name="first_name" type="text" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last name</label>
                                <input name="last_name" type="text" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">DOB</label>
                                <input name="dob" type="date" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">-- select --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input name="phone" type="text" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input name="email" type="email" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="addStudentSubmit" class="btn btn-success">Create Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="editStudentForm" autocomplete="off" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="editStudentAlert" class="alert alert-danger d-none" role="alert"></div>
                        <input type="hidden" name="student_id" id="edit_student_id">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Enrollment No</label>
                                <input name="enrollment_no" id="edit_enrollment_no" type="text" class="form-control" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">First name</label>
                                <input name="first_name" id="edit_first_name" type="text" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last name</label>
                                <input name="last_name" id="edit_last_name" type="text" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">DOB</label>
                                <input name="dob" id="edit_dob" type="date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <select name="gender" id="edit_gender" class="form-select">
                                    <option value="">-- select --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input name="phone" id="edit_phone" type="text" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input name="email" id="edit_email" type="email" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="editStudentSubmit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Student Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <form id="deleteStudentForm" autocomplete="off" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="deleteStudentAlert" class="alert alert-danger d-none" role="alert"></div>
                        <input type="hidden" name="student_id" id="delete_student_id">
                        <p>To delete this student, confirm your password:</p>
                        <div class="mb-3">
                            <input name="password" id="delete_student_password" type="password" class="form-control" placeholder="Your password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="deleteStudentSubmit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function(){
        const form = document.getElementById('loginForm');
        const alertBox = document.getElementById('loginAlert');
        const submitBtn = document.getElementById('loginSubmit');

        if (form) {
            form.addEventListener('submit', async function(e){
                e.preventDefault();
                alertBox.classList.add('d-none');
                alertBox.textContent = '';
                submitBtn.disabled = true;

                const data = {
                    username: (form.username.value || '').trim(),
                    password: form.password.value || ''
                };

                try {
                    const res = await fetch('auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data),
                        cache: 'no-store'
                    });

                    // Non-JSON or non-2xx handling
                    if (!res.ok) {
                        let text = await res.text().catch(()=>null);
                        throw new Error(text || 'Server error: ' + res.status);
                    }

                    const json = await res.json().catch(()=>null);
                    if (!json) throw new Error('Invalid server response');

                    if (json.success) {
                        // if server provided role, redirect lecturer to lecturer dashboard
                        const role = (json.role || '').toString().toLowerCase();
                        if (role === 'lecturer') {
                            window.location.href = 'lecturer_dashboard.php';
                            return;
                        }
                        // Close modal then reload to update UI for other roles
                        const modalEl = document.getElementById('loginModal');
                        const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        bsModal.hide();
                        location.reload();
                        return;
                    }

                    // show server message
                    alertBox.textContent = json.message || 'Invalid username or password';
                    alertBox.classList.remove('d-none');
                } catch (err) {
                    alertBox.textContent = err.message || 'Network error';
                    alertBox.classList.remove('d-none');
                } finally {
                    submitBtn.disabled = false;
                }
            });
        }

        // Add Student handler (POSTs JSON to student_create.php)
        const addForm = document.getElementById('addStudentForm');
        if (addForm) {
            addForm.addEventListener('submit', async function(e){
                e.preventDefault();
                const alertBox = document.getElementById('addStudentAlert');
                const submit = document.getElementById('addStudentSubmit');
                alertBox.classList.add('d-none'); alertBox.textContent = '';
                submit.disabled = true;
                const payload = {
                    enrollment_no: (addForm.enrollment_no.value || '').trim(),
                    first_name: (addForm.first_name.value || '').trim(),
                    last_name: (addForm.last_name.value || '').trim(),
                    dob: addForm.dob.value || '',
                    gender: addForm.gender.value || '',
                    phone: (addForm.phone.value || '').trim(),
                    email: (addForm.email.value || '').trim()
                };
                try {
                    const res = await fetch('student_create.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                        cache: 'no-store'
                    });
                    const text = await res.text();
                    let json;
                    try { json = JSON.parse(text); } catch (err) { throw new Error('Server returned invalid JSON'); }
                    if (!json.success) throw new Error(json.message || 'Unable to create student');
                    new bootstrap.Modal(document.getElementById('addStudentModal')).hide();
                    location.reload();
                } catch (err) {
                    alertBox.textContent = err.message || 'Network error';
                    alertBox.classList.remove('d-none');
                } finally { submit.disabled = false; }
            });
        }

        // delegation for edit/delete buttons
        document.addEventListener('click', function(e){
            const editBtn = e.target.closest('.btn-edit-student');
            if (editBtn) {
                const tr = editBtn.closest('tr');
                if (!tr) return;
                const id = tr.dataset.studentId || '';
                document.getElementById('edit_student_id').value = id;
                document.getElementById('edit_enrollment_no').value = tr.dataset.enrollment || '';
                document.getElementById('edit_first_name').value = tr.dataset.first || '';
                document.getElementById('edit_last_name').value = tr.dataset.last || '';
                document.getElementById('edit_dob').value = tr.dataset.dob || '';
                document.getElementById('edit_gender').value = tr.dataset.gender || '';
                document.getElementById('edit_email').value = tr.dataset.email || '';
                document.getElementById('edit_phone').value = tr.dataset.phone || '';
                document.getElementById('editStudentAlert').classList.add('d-none');
                new bootstrap.Modal(document.getElementById('editStudentModal')).show();
                return;
            }

            const delBtn = e.target.closest('.btn-delete-student');
            if (delBtn) {
                const tr = delBtn.closest('tr');
                if (!tr) return;
                document.getElementById('delete_student_id').value = tr.dataset.studentId || '';
                document.getElementById('delete_student_password').value = '';
                document.getElementById('deleteStudentAlert').classList.add('d-none');
                new bootstrap.Modal(document.getElementById('deleteStudentModal')).show();
                return;
            }
        });

        // submit edit
        const editForm = document.getElementById('editStudentForm');
        if (editForm) {
            editForm.addEventListener('submit', async function(e){
                e.preventDefault();
                const alertBox = document.getElementById('editStudentAlert');
                const btn = document.getElementById('editStudentSubmit');
                alertBox.classList.add('d-none'); alertBox.textContent = '';
                btn.disabled = true;
                const payload = {
                    student_id: parseInt(document.getElementById('edit_student_id').value || 0, 10),
                    enrollment_no: document.getElementById('edit_enrollment_no').value || '',
                    first_name: document.getElementById('edit_first_name').value.trim(),
                    last_name: document.getElementById('edit_last_name').value.trim(),
                    dob: document.getElementById('edit_dob').value || '',
                    gender: document.getElementById('edit_gender').value || '',
                    phone: document.getElementById('edit_phone').value.trim(),
                    email: document.getElementById('edit_email').value.trim()
                };
                try {
                    const res = await fetch('student_update.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                        cache: 'no-store'
                    });
                    const text = await res.text();
                    let json;
                    try { json = JSON.parse(text); } catch (err) { throw new Error('Server returned invalid JSON'); }
                    if (!json.success) throw new Error(json.message || 'Unable to save');
                    new bootstrap.Modal(document.getElementById('editStudentModal')).hide();
                    location.reload();
                } catch (err) {
                    alertBox.textContent = err.message || 'Network error';
                    alertBox.classList.remove('d-none');
                } finally { btn.disabled = false; }
            });
        }

        // submit delete
        const delForm = document.getElementById('deleteStudentForm');
        if (delForm) {
            delForm.addEventListener('submit', async function(e){
                e.preventDefault();
                const alertBox = document.getElementById('deleteStudentAlert');
                const btn = document.getElementById('deleteStudentSubmit');
                alertBox.classList.add('d-none'); alertBox.textContent = '';
                btn.disabled = true;
                const payload = {
                    student_id: parseInt(document.getElementById('delete_student_id').value || 0, 10),
                    password: document.getElementById('delete_student_password').value || ''
                };
                try {
                    const res = await fetch('student_delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                        cache: 'no-store'
                    });
                    const text = await res.text();
                    let json;
                    try { json = JSON.parse(text); } catch (err) { throw new Error('Server returned invalid JSON'); }
                    if (!json.success) throw new Error(json.message || 'Unable to delete');
                    new bootstrap.Modal(document.getElementById('deleteStudentModal')).hide();
                    location.reload();
                } catch (err) {
                    alertBox.textContent = err.message || 'Network error';
                    alertBox.classList.remove('d-none');
                } finally { btn.disabled = false; }
            });
        }
    })();
    </script>

    <?php if (! $canViewRecords): ?>
        <div class="alert alert-info">
            You must be signed in as a lecturer or administrator to view student records.
        </div>
    <?php else: ?>

        <?php if ($result->num_rows === 0): ?>
            <div class="alert alert-info">No students found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Enrollment No</th>
                            <th>Name</th>
                            <th>DOB</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created At</th>
                            <th class="text-end"> </th> <!-- actions -->
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()):
                        $sid = (int)$row['StudentID'];
                    ?>
                        <tr
                            data-student-id="<?php echo $sid; ?>"
                            data-enrollment="<?php echo htmlspecialchars($row['EnrollmentNo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-first="<?php echo htmlspecialchars($row['FirstName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-last="<?php echo htmlspecialchars($row['LastName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-dob="<?php echo htmlspecialchars($row['DOB'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-gender="<?php echo htmlspecialchars($row['Gender'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-email="<?php echo htmlspecialchars($row['Email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-phone="<?php echo htmlspecialchars($row['Phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <td><?php echo $sid; ?></td>
                            <td><?php echo htmlspecialchars($row['EnrollmentNo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['DOB'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['Gender'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['Email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['Phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['CreatedAt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-end">
                                <div class="btn-group" role="group" aria-label="Actions">
                                    <!-- view student disciplinary record (opens incidents page filtered) -->
                                    <a href="student_dashboard.php?student_id=<?php echo $sid; ?>" class="btn btn-sm btn-outline-secondary" title="View dashboard">Records</a>
                                    <!-- edit and delete: only visible to authorised users -->
                                    <?php if ($currentUser && $canViewRecords): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-student" title="Edit">Edit</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-student" title="Delete">Delete</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
             </div>
         <?php endif; ?>

    <?php endif; ?>

</div>
</body>
</html>
<?php
$result->free();
$mysqli->close();
?>