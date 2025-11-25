<?php
// Lecturer dashboard — review cases and apply sanctions (admin/lecturer only)

if (file_exists(__DIR__ . './config/config.php')) require_once __DIR__ . '/config.php';
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1:3305';
    $db_user = defined('DB_USER') ? DB_USER : 'root';
    $db_pass = defined('DB_PASS') ? DB_PASS : '';
    $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_errno) { http_response_code(500); echo 'DB connect failed'; exit; }
}
$mysqli->set_charset('utf8mb4');

session_start();
$currentUser = $_SESSION['user'] ?? null;
function user_has_role($user, array $roles = []) {
    if (!$user) return false;
    $role = $user['role'] ?? $user['Role'] ?? $user['user_type'] ?? null;
    if (!$role) return false;
    return in_array(strtolower($role), array_map('strtolower', $roles), true);
}
if (!user_has_role($currentUser, ['lecturer','admin'])) {
    http_response_code(403); echo 'Unauthorized'; exit;
}

// detect case table (prefers incidentreport)
$candidateTables = ['incidentreport','incidents','cases','reports','student_cases','disciplinary_cases'];
$foundTable = null;
foreach ($candidateTables as $t) {
    $r = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$mysqli->real_escape_string($t)}' LIMIT 1");
    if ($r && $r->num_rows) { $foundTable = $t; $r->free(); break; }
    if ($r) $r->free();
}
if (!$foundTable) {
    // fallback fuzzy search
    $r = $mysqli->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    while ($r && ($row = $r->fetch_assoc())) {
        if (stripos($row['TABLE_NAME'],'incident')!==false || stripos($row['TABLE_NAME'],'case')!==false) { $foundTable = $row['TABLE_NAME']; break; }
    }
    if ($r) $r->free();
}

if (!$foundTable) {
    echo '<p>No case/incident table detected. Add your case table name to $candidateTables in this file.</p>'; exit;
}

// map important columns (best-effort)
function pick_col(array $cols, array $candidates) {
    foreach ($candidates as $c) {
        foreach ($cols as $col) if (strcasecmp($col,$c)===0) return $col;
    }
    foreach ($candidates as $c) {
        foreach ($cols as $col) if (stripos($col,$c) !== false) return $col;
    }
    return null;
}
$cols = [];
$res = $mysqli->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".$mysqli->real_escape_string($foundTable)."'");
while ($res && ($r = $res->fetch_assoc())) $cols[] = $r['COLUMN_NAME'];
if ($res) $res->free();

$col_id = pick_col($cols, ['IncidentID','ReportID','id','CaseID']);
$col_student = pick_col($cols, ['StudentID','student_id','EnrollmentNo','MatricNo']);
$col_desc = pick_col($cols, ['Description','description','ReportText','Details','Narrative','Offense']);
$col_status = pick_col($cols, ['Status','status','CaseStatus']);
$col_severity = pick_col($cols, ['Severity','Level','CaseLevel']);
$col_report_date = pick_col($cols, ['ReportDate','report_date','date_reported','ReportedAt']);
$col_sanctions = pick_col($cols, ['Sanctions','sanctions','Penalty','penalties']);

// query open cases (heuristic)
// --- REPLACED BLOCK: qualify case table columns with alias to avoid ambiguous column errors ---
$ftAlias = 'c'; // alias for the found case table

$selectCols = [];
if ($col_id)         $selectCols[] = "`{$ftAlias}`.`{$col_id}` AS id";
if ($col_student)    $selectCols[] = "`{$ftAlias}`.`{$col_student}` AS student";
if ($col_report_date)$selectCols[] = "`{$ftAlias}`.`{$col_report_date}` AS reported";
if ($col_severity)   $selectCols[] = "`{$ftAlias}`.`{$col_severity}` AS severity";
if ($col_status)     $selectCols[] = "`{$ftAlias}`.`{$col_status}` AS status";
if ($col_desc)       $selectCols[] = "`{$ftAlias}`.`{$col_desc}` AS description";
if ($col_sanctions)  $selectCols[] = "`{$ftAlias}`.`{$col_sanctions}` AS sanctions";

// Try to join student table to get a readable name.
// Heuristic: if case table student column looks like an id, join on student.StudentID,
// otherwise join on student.EnrollmentNo (if that column exists).
$joinSql = '';
if ($col_student) {
    $studentJoinKey = null;
    if (preg_match('/id$/i', $col_student) || stripos($col_student, 'student') !== false) {
        $studentJoinKey = 'StudentID';
    } else {
        $studentJoinKey = 'EnrollmentNo';
    }

    // verify the chosen column exists in student table
    $chk = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student' AND COLUMN_NAME = '".$mysqli->real_escape_string($studentJoinKey)."' LIMIT 1");
    if ($chk && $chk->num_rows) {
        if ($chk) $chk->free();
        $joinSql = " LEFT JOIN `student` AS s ON s.`{$studentJoinKey}` = `{$ftAlias}`.`{$col_student}`";
        // create a readable student_name column (first + last) from student table
        $selectCols[] = "TRIM(CONCAT(COALESCE(s.FirstName,''),' ',COALESCE(s.LastName,''))) AS student_name";
    } else {
        if ($chk) $chk->free();
    }
}

// --- NEW: define open statuses and build WHERE safely ---
$openStatuses = ['Pending','Pending Review','Under Investigation','Hearing Scheduled','Open','Ongoing','In Progress'];

$selectSql = count($selectCols) ? implode(', ', $selectCols) : "{$ftAlias}.*";

// build the static "open" clause (non-parameterized, safe because values are escaped)
$openWhere = '';
if ($col_status && is_array($openStatuses) && count($openStatuses) > 0) {
    $escaped = array_map([$mysqli, 'real_escape_string'], $openStatuses);
    $placeholders = implode("','", $escaped);
    $openWhere = " AND `{$ftAlias}`.`{$col_status}` IN ('{$placeholders}')";
}

// --- NEW: Search handling (q = query, field = all|student|id|description|status|severity) ---
$searchTerm  = trim((string)($_GET['q'] ?? ''));
$searchField = ($_GET['field'] ?? 'all');

$searchWhere = '';
$params = [];
$types = '';

if ($searchTerm !== '') {
    $like = '%' . $searchTerm . '%';
    // Build clauses using qualified column names (alias $ftAlias for case table, 's' for student join)
    switch ($searchField) {
        case 'student':
            if ($joinSql !== '') {
                $searchWhere = "(TRIM(CONCAT(COALESCE(s.FirstName,''),' ',COALESCE(s.LastName,''))) LIKE ? OR COALESCE(s.EnrollmentNo,'') LIKE ? OR `{$ftAlias}`.`{$col_student}` LIKE ?)";
                $types .= 'sss'; $params[] = $like; $params[] = $like; $params[] = $like;
            } elseif ($col_student) {
                $searchWhere = "`{$ftAlias}`.`{$col_student}` LIKE ?";
                $types .= 's'; $params[] = $like;
            }
            break;
        case 'id':
            if ($col_id) {
                $searchWhere = "`{$ftAlias}`.`{$col_id}` LIKE ?";
                $types .= 's'; $params[] = $like;
            }
            break;
        case 'description':
            if ($col_desc) {
                $searchWhere = "`{$ftAlias}`.`{$col_desc}` LIKE ?";
                $types .= 's'; $params[] = $like;
            }
            break;
        case 'status':
            if ($col_status) {
                $searchWhere = "`{$ftAlias}`.`{$col_status}` LIKE ?";
                $types .= 's'; $params[] = $like;
            }
            break;
        case 'severity':
            if ($col_severity) {
                $searchWhere = "`{$ftAlias}`.`{$col_severity}` LIKE ?";
                $types .= 's'; $params[] = $like;
            }
            break;
        default:
            // all: combine student, id, description, status, severity where available
            $parts = [];
            if ($joinSql !== '') {
                $parts[] = "TRIM(CONCAT(COALESCE(s.FirstName,''),' ',COALESCE(s.LastName,''))) LIKE ?";
                $parts[] = "COALESCE(s.EnrollmentNo,'') LIKE ?";
                $types .= 'ss'; $params[] = $like; $params[] = $like;
            } elseif ($col_student) {
                $parts[] = "`{$ftAlias}`.`{$col_student}` LIKE ?";
                $types .= 's'; $params[] = $like;
            }
            if ($col_id) { $parts[] = "`{$ftAlias}`.`{$col_id}` LIKE ?"; $types .= 's'; $params[] = $like; }
            if ($col_desc) { $parts[] = "`{$ftAlias}`.`{$col_desc}` LIKE ?"; $types .= 's'; $params[] = $like; }
            if ($col_status) { $parts[] = "`{$ftAlias}`.`{$col_status}` LIKE ?"; $types .= 's'; $params[] = $like; }
            if ($col_severity) { $parts[] = "`{$ftAlias}`.`{$col_severity}` LIKE ?"; $types .= 's'; $params[] = $like; }
            if (!empty($parts)) $searchWhere = '(' . implode(' OR ', $parts) . ')';
            break;
    }
}

// Build final SQL (apply openWhere then searchWhere). Use parameterized binding for searchWhere only.
$orderByRaw = $col_report_date ?? $col_id ?? null;
if ($orderByRaw) {
    $orderBy = "`{$ftAlias}`.`".$mysqli->real_escape_string($orderByRaw)."`";
} else {
    $orderBy = '1';
}

$sql = "SELECT {$selectSql} FROM `{$foundTable}` AS {$ftAlias} {$joinSql} WHERE 1 {$openWhere}";
if ($searchWhere !== '') {
    $sql .= " AND {$searchWhere}";
}
$sql .= " ORDER BY {$orderBy} DESC LIMIT 200";

$stmt = $mysqli->prepare($sql);
if (!$stmt) { echo 'Query prepare failed: '.$mysqli->error; exit; }

// bind parameters if any
if (!empty($params)) {
    $bind = [];
    $bind[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}

$stmt->execute();
$result = $stmt->get_result();
$cases = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Lecturer Dashboard</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Lecturer Review Dashboard</h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <!-- Search bar -->
    <form class="row g-2 mb-3" method="get" action="lecturer_dashboard.php" role="search">
        <div class="col-auto">
            <select name="field" class="form-select form-select-sm">
                <option value="all" <?php echo (isset($searchField) && $searchField === 'all') ? 'selected' : ''; ?>>All</option>
                <option value="student" <?php echo (isset($searchField) && $searchField === 'student') ? 'selected' : ''; ?>>Student</option>
                <option value="id" <?php echo (isset($searchField) && $searchField === 'id') ? 'selected' : ''; ?>>Case ID</option>
                <option value="description" <?php echo (isset($searchField) && $searchField === 'description') ? 'selected' : ''; ?>>Description</option>
                <option value="status" <?php echo (isset($searchField) && $searchField === 'status') ? 'selected' : ''; ?>>Status</option>
                <option value="severity" <?php echo (isset($searchField) && $searchField === 'severity') ? 'selected' : ''; ?>>Severity</option>
            </select>
        </div>
        <div class="col">
            <input name="q" value="<?php echo htmlspecialchars($searchTerm ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm" type="search" placeholder="Search cases (press Enter)" aria-label="Search cases">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-primary btn-sm" type="submit">Search</button>
            <a href="lecturer_dashboard.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </form>

    <div class="card p-3 mb-3">
        <h6 class="mb-2">Open cases (ready for review)</h6>
        <?php if (empty($cases)): ?>
            <div class="alert alert-info">No open cases detected.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-dark"><tr>
                        <th>#</th><th>Student</th><th>Reported</th><th>Offence / description</th><th>Severity</th><th>Status</th><th class="text-end">Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($cases as $c): ?>
                        <tr data-case-id="<?php echo htmlspecialchars($c['id'] ?? '', ENT_QUOTES); ?>"
                            data-desc="<?php echo htmlspecialchars($c['description'] ?? '', ENT_QUOTES); ?>"
                            data-student="<?php echo htmlspecialchars($c['student_name'] ?? $c['student'] ?? '', ENT_QUOTES); ?>"
                            data-status="<?php echo htmlspecialchars($c['status'] ?? '', ENT_QUOTES); ?>"
                            data-severity="<?php echo htmlspecialchars($c['severity'] ?? '', ENT_QUOTES); ?>">
                            <td><?php echo htmlspecialchars($c['id'] ?? '', ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($c['student_name'] ?? $c['student'] ?? '', ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($c['reported'] ?? '', ENT_QUOTES); ?></td>
                            <td style="max-width:360px;white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars(substr($c['description'] ?? '',0,600), ENT_QUOTES)); ?></td>
                            <td><?php echo htmlspecialchars($c['severity'] ?? '', ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($c['status'] ?? '', ENT_QUOTES); ?></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary btn-review">Review</button>
                                    <button class="btn btn-sm btn-success btn-sanction">Apply sanction</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Review / Sanction Modal -->
    <div class="modal fade" id="sanctionModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <form id="sanctionForm" autocomplete="off">
          <div class="modal-header"><h5 class="modal-title">Review & Apply Sanction</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div id="sanctionAlert" class="alert alert-danger d-none"></div>
            <input type="hidden" name="case_id" id="case_id">
            <div class="mb-2"><strong>Student:</strong> <span id="case_student"></span></div>
            <div class="mb-2"><strong>Description:</strong><div id="case_desc" style="white-space:pre-wrap;background:#f8f9fa;padding:8px;border-radius:4px;"></div></div>

            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Sanction type</label>
                <select name="sanction_type" id="sanction_type" class="form-select" required>
                  <option value="">-- select --</option>
                  <option value="Warning">Warning</option>
                  <option value="Probation">Probation</option>
                  <option value="Suspension">Suspension</option>
                  <option value="Fine">Fine</option>
                  <option value="Community Service">Community Service</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Duration (days)</label>
                <input type="number" name="duration_days" id="duration_days" class="form-control" min="0" placeholder="e.g. 30">
              </div>
              <div class="col-md-3">
                <label class="form-label">Effective date</label>
                <input type="date" name="effective_date" id="effective_date" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Notes / instructions</label>
                <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Details, required actions, deadlines..."></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" id="applySanctionBtn" class="btn btn-success">Apply sanction</button>
          </div>
        </form>
      </div></div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const modalEl = document.getElementById('sanctionModal');
  const bsModal = new bootstrap.Modal(modalEl);
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-sanction') || e.target.closest('.btn-review');
    if (!btn) return;
    const tr = btn.closest('tr');
    if (!tr) return;
    document.getElementById('case_id').value = tr.dataset.caseId || tr.dataset.caseId || tr.getAttribute('data-case-id') || tr.dataset.caseId;
    document.getElementById('case_student').textContent = tr.dataset.student || tr.getAttribute('data-student') || '';
    document.getElementById('case_desc').textContent = tr.dataset.desc || tr.getAttribute('data-desc') || '';
    document.getElementById('sanctionAlert').classList.add('d-none');
    bsModal.show();
  });

  document.getElementById('sanctionForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const alertBox = document.getElementById('sanctionAlert');
    const btn = document.getElementById('applySanctionBtn');
    alertBox.classList.add('d-none'); alertBox.textContent = '';
    btn.disabled = true;
    const payload = {
      case_id: (document.getElementById('case_id').value || '').toString(),
      sanction_type: document.getElementById('sanction_type').value,
      duration_days: document.getElementById('duration_days').value,
      effective_date: document.getElementById('effective_date').value,
      notes: document.getElementById('notes').value
    };
    try {
      const res = await fetch('sanction_apply.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload),
        cache: 'no-store'
      });
      const text = await res.text();
      let json;
      try { json = JSON.parse(text); } catch (err) { throw new Error('Server returned invalid JSON: ' + text); }
      if (!json.success) throw new Error(json.message || 'Failed');
      bsModal.hide();
      location.reload();
    } catch (err) {
      alertBox.textContent = err.message || 'Network error';
      alertBox.classList.remove('d-none');
    } finally { btn.disabled = false; }
  });
})();
</script>
</body></html>
?>