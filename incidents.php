<?php
// incidents.php - full page: list incidents, add/edit/delete with modals and login

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1:3305';
    $db_user = defined('DB_USER') ? DB_USER : 'root';
    $db_pass = defined('DB_PASS') ? DB_PASS : '';
    $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_errno) {
        http_response_code(500);
        echo 'Database connection failed: ' . htmlspecialchars($mysqli->connect_error, ENT_QUOTES, 'UTF-8');
        exit;
    }
}

session_start();
$currentUser = $_SESSION['user'] ?? null;

// Build staff and student options for select inputs
$staffOptions = '';
$studentOptions = '';
if ($mysqli instanceof mysqli) {
    $sres = $mysqli->query("SELECT StaffID, Name FROM staff ORDER BY Name");
    if ($sres) {
        while ($r = $sres->fetch_assoc()) {
            $staffOptions .= '<option value="'.(int)$r['StaffID'].'">'.htmlspecialchars($r['Name'], ENT_QUOTES, 'UTF-8').'</option>';
        }
        $sres->free();
    }

    $stres = $mysqli->query("SELECT StudentID, FirstName, LastName, EnrollmentNo FROM student ORDER BY LastName, FirstName");
    if ($stres) {
        while ($r = $stres->fetch_assoc()) {
            $label = trim(($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? '')) . ' (' . ($r['EnrollmentNo'] ?? '') . ')';
            $studentOptions .= '<option value="'.(int)$r['StudentID'].'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>';
        }
        $stres->free();
    }
}

$sql = "SELECT i.IncidentID, i.ReportDate, i.Location, i.Description, i.Status, i.CreatedAt,
               s.StaffID AS ReporterStaffID, s.Name AS ReporterName,
               st.StudentID AS StudentID, CONCAT(st.FirstName, ' ', st.LastName) AS StudentName
        FROM incidentreport i
        LEFT JOIN staff s ON i.ReporterStaffID = s.StaffID
        LEFT JOIN student st ON i.StudentID = st.StudentID
        ORDER BY i.ReportDate DESC, i.IncidentID DESC";

$result = $mysqli->query($sql);
if ($result === false) {
    http_response_code(500);
    echo 'Query error: ' . htmlspecialchars($mysqli->error, ENT_QUOTES, 'UTF-8');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Incidents - Disciplinary System</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Header: dark bar with white bold text */
        table.table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        table.table thead th {
            background: #222427;          /* dark header color */
            color: #ffffff;               /* white text */
            font-weight: 700;
            padding: 0.6rem 1rem;
            vertical-align: middle;
            border: 0;                    /* remove default border */
            border-right: 1px solid rgba(255,255,255,0.05); /* subtle divider between headings */
            white-space: nowrap;
        }

        /* Make header row visually taller like your example */
        table.table thead th:first-child { padding-left: 0.75rem; }
        table.table thead th { height: 2.5rem; }

        /* Body rows */
        table.table tbody td {
            background: #ffffff;
            padding: 0.45rem 0.5rem;
            vertical-align: middle;
            border-top: 1px solid #e9ecef; /* subtle row separators */
            color: #222;
        }

        /* Keep bootstrap striped look but slightly lighter */
        table.table.table-striped > tbody > tr:nth-of-type(odd) > td {
            background-color: #f7f7f7;
        }

        /* Description column: clip overflow and show ellipsis */
        td.desc-cell {
            max-width: 20rem;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        /* Small gap between ID and Date columns */
        table.table thead th:nth-child(2),
        table.table tbody td:nth-child(2) {
            padding-left: 1.25rem;
        }

        /* Action cell keep aligned to right */
        td.action-cell { width: 5.5rem; padding-right: 1rem; }

        /* Slide-in buttons (kept from previous) */
        table.table tbody tr { position: relative; }
        .row-actions {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%) translateX(110%);
            transition: transform 180ms ease;
            display: flex;
            gap: .35rem;
            z-index: 3;
            pointer-events: auto;
        }
        table.table tbody tr:hover .row-actions {
            transform: translateY(-50%) translateX(0);
        }

        /* Make header text a bit smaller and uppercase if desired */
        table.table thead th { text-transform: none; font-size: 0.95rem; }
    </style>
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
                        <div><strong><?php echo htmlspecialchars($currentUser['FullName'] ?? $currentUser['Username'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    </div>
                    <div class="btn-group me-2" role="group" aria-label="Navigation">
                        <a href="incidents.php" class="btn btn-primary">Incidents</a>
                        <a href="index.php" class="btn btn-outline-primary">Students</a>
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

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="mb-0">Incidents</h1>
        <?php if ($currentUser): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addIncidentModal">+ Add Incident</button>
        <?php endif; ?>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="loginForm" autocomplete="off" novalidate>
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

    <!-- Add Incident Modal -->
    <div class="modal fade" id="addIncidentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="addIncidentForm" autocomplete="off" novalidate>
                    <input type="text" name="fakeuser2" autocomplete="off" style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true" tabindex="-1">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Incident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="addIncidentAlert" class="alert alert-danger d-none" role="alert"></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Report date</label>
                                <input name="report_date" type="datetime-local" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location</label>
                                <input name="location" type="text" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reporter (staff)</label>
                                <select name="reporter_id" class="form-select" required>
                                    <option value="">-- select reporter --</option>
                                    <?php echo $staffOptions; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Student</label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">-- select student --</option>
                                    <?php echo $studentOptions; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="Pending">Pending</option>
                                    <option value="In Review">In Review</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="addIncidentSubmit" class="btn btn-success">Create Incident</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-info">No incidents found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle">
                <thead class="table-light small">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Date</th>
                        <th scope="col">Location</th>
                        <th scope="col">Reporter</th>
                        <th scope="col">Student</th>
                        <th scope="col">Status</th>
                        <th scope="col">Created</th>
                        <th scope="col">Description</th>
                        <th class="text-end"> </th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()):
                    $id = (int)$row['IncidentID'];
                ?>
                    <tr
                        data-incident-id="<?php echo $id; ?>"
                        data-report-date="<?php echo htmlspecialchars($row['ReportDate'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-location="<?php echo htmlspecialchars($row['Location'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-reporter-id="<?php echo (int)($row['ReporterStaffID'] ?? 0); ?>"
                        data-student-id="<?php echo (int)($row['StudentID'] ?? 0); ?>"
                        data-status="<?php echo htmlspecialchars($row['Status'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-description="<?php echo htmlspecialchars($row['Description'], ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <td class="fw-semibold"><?php echo $id; ?></td>
                        <td><?php echo htmlspecialchars($row['ReportDate'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['Location'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['ReporterName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['StudentName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['Status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['CreatedAt'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="max-width:20rem;">
                            <div class="text-truncate" style="max-width:11.5rem;" title="<?php echo htmlspecialchars($row['Description'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($row['Description'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </td>
                        <td class="action-cell text-end">
                            <div class="row-actions" aria-hidden="true">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-incident" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-incident" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Edit Incident Modal -->
    <div class="modal fade" id="editIncidentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="editIncidentForm" autocomplete="off" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Incident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="editIncidentAlert" class="alert alert-danger d-none" role="alert"></div>
                        <input type="hidden" name="incident_id" id="edit_incident_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Report date</label>
                                <input name="report_date" id="edit_report_date" type="datetime-local" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location</label>
                                <input name="location" id="edit_location" type="text" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reporter (staff)</label>
                                <select name="reporter_id" id="edit_reporter_id" class="form-select" required>
                                    <option value="">-- select reporter --</option>
                                    <?php echo $staffOptions; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Student</label>
                                <select name="student_id" id="edit_student_id" class="form-select" required>
                                    <option value="">-- select student --</option>
                                    <?php echo $studentOptions; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="edit_description" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="Pending">Pending</option>
                                    <option value="In Review">In Review</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="editIncidentSubmit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete confirm modal (asks for user's password) -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <form id="deleteIncidentForm" autocomplete="off" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="deleteIncidentAlert" class="alert alert-danger d-none" role="alert"></div>
                        <input type="hidden" name="incident_id" id="delete_incident_id">
                        <p>To delete this incident, confirm your password:</p>
                        <div class="mb-3">
                            <input name="password" id="delete_password" type="password" class="form-control" placeholder="Your password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="deleteIncidentSubmit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
    // Login handler
    const loginForm = document.getElementById('loginForm');
    const loginAlert = document.getElementById('loginAlert');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e){
            e.preventDefault();
            loginAlert.classList.add('d-none'); loginAlert.textContent = '';
            const submit = document.getElementById('loginSubmit'); submit.disabled = true;
            const data = { username: (loginForm.username.value || '').trim(), password: loginForm.password.value || '' };
            try {
                const res = await fetch('auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data),
                    cache: 'no-store'
                });
                const text = await res.text();
                let json = null;
                try { json = JSON.parse(text); } catch (err) {
                    throw new Error('Server returned invalid JSON');
                }
                if (!json.success) throw new Error(json.message || 'Login failed');
                new bootstrap.Modal(document.getElementById('loginModal')).hide();
                location.reload();
            } catch (err) {
                loginAlert.textContent = err.message || 'Network error';
                loginAlert.classList.remove('d-none');
            } finally { submit.disabled = false; }
        });
    }

    // Add Incident
    const addForm = document.getElementById('addIncidentForm');
    if (addForm) {
        addForm.addEventListener('submit', async function(e){
            e.preventDefault();
            const alertBox = document.getElementById('addIncidentAlert');
            const submitBtn = document.getElementById('addIncidentSubmit');
            alertBox.classList.add('d-none'); alertBox.textContent = '';
            submitBtn.disabled = true;
            const data = {
                report_date: addForm.report_date.value || '',
                location: addForm.location.value.trim(),
                reporter_id: parseInt(addForm.reporter_id.value || 0, 10),
                student_id: parseInt(addForm.student_id.value || 0, 10),
                description: addForm.description.value.trim(),
                status: addForm.status.value || 'Pending'
            };
            try {
                const res = await fetch('incident_create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data),
                    cache: 'no-store'
                });
                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch (err) { throw new Error('Server did not return valid JSON'); }
                if (!json.success) throw new Error(json.message || 'Unable to create incident');
                new bootstrap.Modal(document.getElementById('addIncidentModal')).hide();
                location.reload();
            } catch (err) {
                alertBox.textContent = err.message || 'Network error';
                alertBox.classList.remove('d-none');
            } finally { submitBtn.disabled = false; }
        });
    }

    // Helper to convert "YYYY-MM-DD HH:MM:SS" to "YYYY-MM-DDTHH:MM"
    function toDatetimeLocal(value) {
        if (!value) return '';
        // ensure space between date and time
        const normalized = value.replace('T', ' ').trim();
        const dt = new Date(normalized);
        if (isNaN(dt.getTime())) return '';
        const yyyy = dt.getFullYear().toString().padStart(4,'0');
        const mm = (dt.getMonth()+1).toString().padStart(2,'0');
        const dd = dt.getDate().toString().padStart(2,'0');
        const hh = dt.getHours().toString().padStart(2,'0');
        const mi = dt.getMinutes().toString().padStart(2,'0');
        return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
    }

    // Row actions: edit & delete (delegation)
    document.addEventListener('click', function(e){
        const btnEdit = e.target.closest('.btn-edit-incident');
        if (btnEdit) {
            const tr = btnEdit.closest('tr');
            if (!tr) return;
            const id = tr.dataset.incidentId || '';
            document.getElementById('edit_incident_id').value = id;
            document.getElementById('edit_report_date').value = toDatetimeLocal(tr.dataset.reportDate || '');
            document.getElementById('edit_location').value = tr.dataset.location || '';
            document.getElementById('edit_reporter_id').value = tr.dataset.reporterId || '';
            document.getElementById('edit_student_id').value = tr.dataset.studentId || '';
            document.getElementById('edit_description').value = tr.dataset.description || '';
            document.getElementById('edit_status').value = tr.dataset.status || 'Pending';
            document.getElementById('editIncidentAlert').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('editIncidentModal')).show();
            return;
        }

        const btnDel = e.target.closest('.btn-delete-incident');
        if (btnDel) {
            const tr = btnDel.closest('tr');
            if (!tr) return;
            const id = tr.dataset.incidentId || '';
            document.getElementById('delete_incident_id').value = id;
            document.getElementById('delete_password').value = '';
            document.getElementById('deleteIncidentAlert').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
            return;
        }
    });

    // Edit submit
    const editForm = document.getElementById('editIncidentForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e){
            e.preventDefault();
            const alertBox = document.getElementById('editIncidentAlert');
            const submitBtn = document.getElementById('editIncidentSubmit');
            alertBox.classList.add('d-none'); alertBox.textContent = '';
            submitBtn.disabled = true;
            const payload = {
                incident_id: parseInt(document.getElementById('edit_incident_id').value || 0, 10),
                report_date: document.getElementById('edit_report_date').value || '',
                location: document.getElementById('edit_location').value.trim(),
                reporter_id: parseInt(document.getElementById('edit_reporter_id').value || 0, 10),
                student_id: parseInt(document.getElementById('edit_student_id').value || 0, 10),
                description: document.getElementById('edit_description').value.trim(),
                status: document.getElementById('edit_status').value || 'Pending'
            };
            try {
                const res = await fetch('incident_update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    cache: 'no-store'
                });
                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch (err) { throw new Error('Server returned invalid JSON'); }
                if (!json.success) throw new Error(json.message || 'Unable to save');
                new bootstrap.Modal(document.getElementById('editIncidentModal')).hide();
                location.reload();
            } catch (err) {
                alertBox.textContent = err.message || 'Network error';
                alertBox.classList.remove('d-none');
            } finally { submitBtn.disabled = false; }
        });
    }

    // Delete submit
    const delForm = document.getElementById('deleteIncidentForm');
    if (delForm) {
        delForm.addEventListener('submit', async function(e){
            e.preventDefault();
            const alertBox = document.getElementById('deleteIncidentAlert');
            const submitBtn = document.getElementById('deleteIncidentSubmit');
            alertBox.classList.add('d-none'); alertBox.textContent = '';
            submitBtn.disabled = true;
            const payload = {
                incident_id: parseInt(document.getElementById('delete_incident_id').value || 0, 10),
                password: document.getElementById('delete_password').value || ''
            };
            try {
                const res = await fetch('incident_delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    cache: 'no-store'
                });
                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch (err) { throw new Error('Server returned invalid JSON'); }
                if (!json.success) throw new Error(json.message || 'Unable to delete');
                new bootstrap.Modal(document.getElementById('confirmDeleteModal')).hide();
                location.reload();
            } catch (err) {
                alertBox.textContent = err.message || 'Network error';
                alertBox.classList.remove('d-none');
            } finally { submitBtn.disabled = false; }
        });
    }

})();
</script>
</body>
</html>
<?php
$result->free();
$mysqli->close();
?>