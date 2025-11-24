<?php
// Student dashboard (uses existing tables if present).
// - Detects case/incident table automatically from common names
// - Maps common column names so you don't need to change schema
// - Requires session user with role admin or lecturer

if (file_exists(__DIR__ . '/config.php')) require_once __DIR__ . '/config.php';

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1:3305';
    $db_user = defined('DB_USER') ? DB_USER : 'root';
    $db_pass = defined('DB_PASS') ? DB_PASS : '';
    $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_errno) { http_response_code(500); echo 'DB connection failed'; exit; }
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
if (!user_has_role($currentUser, ['admin','lecturer'])) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($studentId <= 0) {
    http_response_code(400);
    echo 'Missing or invalid student_id';
    exit;
}

// --- Helper: friendly column chooser ---
function pick_col(array $cols, array $candidates, $default = null) {
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) return $c;
        // case-insensitive match
        foreach ($cols as $col) {
            if (strcasecmp($col, $c) === 0) return $col;
        }
    }
    return $default;
}

// --- Inspect student table columns and fetch profile ---
$studentColsRes = $mysqli->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student'");
$studentCols = [];
if ($studentColsRes) {
    while ($r = $studentColsRes->fetch_assoc()) $studentCols[] = $r['COLUMN_NAME'];
    $studentColsRes->free();
}
$col_first = pick_col($studentCols, ['FirstName','first_name','firstname','fname','GivenName']);
$col_last  = pick_col($studentCols, ['LastName','last_name','lastname','lname','Surname']);
$col_enroll = pick_col($studentCols, ['EnrollmentNo','Enrollment','MatricNo','MatricNumber','Matric','StudentNo']);
$col_dept = pick_col($studentCols, ['Department','department','Faculty','faculty','Dept']);
$col_level = pick_col($studentCols, ['Level','Class','Year','StudyLevel']);
$col_status = pick_col($studentCols, ['Status','status','AcademicStatus','StudentStatus']);
$col_created = pick_col($studentCols, ['CreatedAt','created_at','Created','created']);

// Build select list (only include existing columns)
$selectCols = ['StudentID'];
foreach ([$col_enroll, $col_first, $col_last, $col_dept, $col_level, $col_status, $col_created] as $c) {
    if ($c && !in_array($c, $selectCols, true)) $selectCols[] = $c;
}

$stmt = $mysqli->prepare("SELECT " . implode(',', array_map(function($c){ return "`$c`"; }, $selectCols)) . " FROM `student` WHERE StudentID = ? LIMIT 1");
if (!$stmt) { http_response_code(500); echo 'Prepare failed'; exit; }
$stmt->bind_param('i', $studentId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc() ?: null;
$stmt->close();
if (!$profile) { http_response_code(404); echo 'Student not found'; exit; }

// --- Detect case/incident-like table ---
// (ensure incidentreport is checked first)
$candidateTables = ['incidentreport','incidents','incident','cases','student_cases','disciplinary_cases','offences','offenses','complaints','reports'];
$foundTable = null;
foreach ($candidateTables as $t) {
    $res = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$mysqli->real_escape_string($t)}' LIMIT 1");
    if ($res && $res->num_rows) { $foundTable = $t; $res->free(); break; }
    if ($res) $res->free();
}
// fuzzy fallback
if (!$foundTable) {
    $res = $mysqli->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $name = $r['TABLE_NAME'];
            if (stripos($name, 'incident') !== false || stripos($name, 'case') !== false || stripos($name, 'disciplin') !== false) {
                $foundTable = $name; break;
            }
        }
        $res->free();
    }
}

// If found, inspect its columns and map them
$caseCols = [];
$caseColMap = [];
if ($foundTable) {
    $cRes = $mysqli->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$mysqli->real_escape_string($foundTable)}'");
    if ($cRes) {
        while ($r = $cRes->fetch_assoc()) $caseCols[] = $r['COLUMN_NAME'];
        $cRes->free();
    }
    // typical mappings (include Description / ReportText candidates)
    $caseColMap['id'] = pick_col($caseCols, ['IncidentID','IncidentId','id','CaseID','CaseId','ReportID','ReportId']);
    $caseColMap['student_fk'] = pick_col($caseCols, ['StudentID','student_id','studentid','MatricID','EnrollmentNo','MatricNo']);
    $caseColMap['report_date'] = pick_col($caseCols, ['ReportDate','report_date','date_reported','ReportedAt','created_at','ReportDateTime']);
    $caseColMap['offense'] = pick_col($caseCols, ['Offense','Title','Offence','OffenceTitle','OffenseTitle','Description','description','OffenceDesc','OffenceDescription']);
    $caseColMap['description'] = pick_col($caseCols, ['Description','description','Details','Narrative','ReportText','ReportDetails','Desc']);
    $caseColMap['severity'] = pick_col($caseCols, ['Severity','Level','CaseLevel','SeverityLevel']);
    $caseColMap['status'] = pick_col($caseCols, ['Status','status','CaseStatus','case_status']);
    $caseColMap['next_action'] = pick_col($caseCols, ['NextAction','next_action','RequiredAction','Action','NextStep']);
    $caseColMap['deadline'] = pick_col($caseCols, ['Deadline','deadline','due_date','action_deadline','DueDate']);
    $caseColMap['resolved_date'] = pick_col($caseCols, ['ResolvedAt','resolved_at','DateResolved','resolved_date']);
    $caseColMap['decision'] = pick_col($caseCols, ['Decision','Outcome','Result','DecisionMade']);
    $caseColMap['sanctions'] = pick_col($caseCols, ['Sanctions','sanctions','Penalty','penalties']);
    $caseColMap['assigned'] = pick_col($caseCols, ['AssignedTo','StaffID','Officer','Investigator']);
}

// --- Gather metrics (active cases cross-referenced by StudentID, show descriptions) ---
$totalIncidents = 0;
$openCount = 0;
$resolvedCount = 0;
$statusDistribution = [];
$recentOpen = [];
$recentResolved = [];
$sanctionsList = [];

if ($foundTable && $caseColMap['student_fk']) {
    $fk = $caseColMap['student_fk'];
    $fkValue = $profile[$fk] ?? $studentId; // try profile FK value (EnrollmentNo etc) else StudentID

    // total count
    $sql = "SELECT COUNT(*) FROM `{$foundTable}` WHERE `{$fk}` = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $fkValue);
    $stmt->execute(); $stmt->bind_result($totalIncidents); $stmt->fetch(); $stmt->close();

    // status distribution
    if ($caseColMap['status']) {
        $sql = "SELECT `{$caseColMap['status']}` AS st, COUNT(*) AS cnt FROM `{$foundTable}` WHERE `{$fk}` = ? GROUP BY `{$caseColMap['status']}`";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $fkValue);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) { $statusDistribution[$r['st'] ?? 'Unknown'] = (int)$r['cnt']; }
        $stmt->close();

        $openStatuses = ['Pending','Pending Review','Under Investigation','Hearing Scheduled','Open','Ongoing','In Progress'];
        $resolvedStatuses = ['Resolved','Closed','Decided','Finalized','Completed'];
        foreach ($statusDistribution as $sname => $ct) {
            if (in_array($sname, $openStatuses, true)) $openCount += $ct;
            elseif (in_array($sname, $resolvedStatuses, true)) $resolvedCount += $ct;
        }
    }

    // fallback resolved by resolved_date column
    if ($caseColMap['resolved_date']) {
        $sql = "SELECT COUNT(*) FROM `{$foundTable}` WHERE `{$fk}` = ? AND `{$caseColMap['resolved_date']}` IS NOT NULL AND `{$caseColMap['resolved_date']}` <> ''";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $fkValue);
        $stmt->execute(); $stmt->bind_result($resolvedCount); $stmt->fetch(); $stmt->close();
        $openCount = max(0, $totalIncidents - $resolvedCount);
    }

    // Recent open cases: explicitly include description column if present
    $selectCols = [];
    foreach (['id','report_date','offense','description','severity','status','next_action','deadline'] as $k) {
        if (!empty($caseColMap[$k])) $selectCols[] = "`{$caseColMap[$k]}`";
    }
    if (empty($selectCols)) $selectCols = ['*'];
    $sql = "SELECT " . implode(',', $selectCols) . " FROM `{$foundTable}` WHERE `{$fk}` = ? ";
    if ($caseColMap['status']) $sql .= " AND `{$caseColMap['status']}` NOT IN ('Closed','Resolved','Decided','Finalized','Completed') ";
    $sql .= " ORDER BY " . ($caseColMap['report_date'] ?? $caseColMap['id'] ?? '1') . " DESC LIMIT 20";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $fkValue);
    $stmt->execute(); $recentOpen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

    // Recent resolved cases (include description)
    if ($caseColMap['resolved_date']) {
        $selectCols = [];
        foreach (['id','report_date','offense','description','severity','status','resolved_date','decision','sanctions'] as $k) {
            if (!empty($caseColMap[$k])) $selectCols[] = "`{$caseColMap[$k]}`";
        }
        if (empty($selectCols)) $selectCols = ['*'];
        $sql = "SELECT " . implode(',', $selectCols) . " FROM `{$foundTable}` WHERE `{$fk}` = ? AND `{$caseColMap['resolved_date']}` IS NOT NULL AND `{$caseColMap['resolved_date']}` <> '' ORDER BY `{$caseColMap['resolved_date']}` DESC LIMIT 20";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $fkValue);
        $stmt->execute(); $recentResolved = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    }

    // sanctions list
    if ($caseColMap['sanctions']) {
        $sql = "SELECT `{$caseColMap['id']}` AS id, `{$caseColMap['offense']}` AS offense, `{$caseColMap['sanctions']}` AS sanction, `{$caseColMap['status']}` AS status FROM `{$foundTable}` WHERE `{$fk}` = ? AND `{$caseColMap['sanctions']}` IS NOT NULL LIMIT 20";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $fkValue);
        $stmt->execute(); $sanctionsList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    }
}

// Compute severity/risk score: simple heuristic (assign weights by severity labels)
$severityScore = 0;
$maxPossible = 1;
if (!empty($recentOpen)) {
    $weights = ['Critical' => 5, 'Major' => 3, 'Minor' => 1];
    $count = 0; $acc = 0;
    foreach ($recentOpen as $r) {
        $sev = '';
        foreach (['severity','Severity','level','Level','CaseLevel'] as $k) if (isset($r[$k])) { $sev = $r[$k]; break; }
        $w = 1;
        if ($sev) {
            foreach ($weights as $k=>$v) if (stripos($sev, $k) !== false) { $w = $v; break; }
        }
        $acc += $w; $count++;
    }
    $severityScore = $count ? min(100, round(($acc / (max(1,$count*5))) * 100)) : 0;
    $maxPossible = 100;
}

// --- Render dashboard ---
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.small-desc{color:#6c757d;font-size:.9rem}
.card-stat{min-height:110px}
.badge-sev-critical{background:#e74a3b}
.badge-sev-major{background:#f6c23e;color:#000}
.badge-sev-minor{background:#1cc88a}
</style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3 p-3 bg-white rounded shadow-sm">
        <div>
            <h4 class="mb-0">Student Dashboard</h4>
            <div class="small text-muted">Disciplinary analytics & case management</div>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to students</a>
        </div>
    </div>

    <!-- Profile + summary -->
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="card p-3">
                <div class="d-flex">
                    <div>
                        <h5 class="mb-1"><?php echo htmlspecialchars(($profile[$col_enroll] ?? $profile['StudentID'] ?? ''), ENT_QUOTES,'UTF-8'); ?></h5>
                        <h3 class="mb-0"><?php echo htmlspecialchars(trim(($profile[$col_first] ?? '') . ' ' . ($profile[$col_last] ?? '')), ENT_QUOTES,'UTF-8'); ?></h3>
                        <div class="small-desc mt-1">
                            Dept/Faculty: <?php echo htmlspecialchars($profile[$col_dept] ?? '-', ENT_QUOTES,'UTF-8'); ?> •
                            Level/Class: <?php echo htmlspecialchars($profile[$col_level] ?? '-', ENT_QUOTES,'UTF-8'); ?> •
                            Status: <?php echo htmlspecialchars($profile[$col_status] ?? 'Unknown', ENT_QUOTES,'UTF-8'); ?>
                        </div>
                    </div>
                    <div class="ms-auto text-end">
                        <div class="small-desc">Open cases</div>
                        <h2 class="mb-0 text-warning"><?php echo (int)$openCount; ?></h2>
                        <div class="small-desc">Resolved: <?php echo (int)$resolvedCount; ?> • Total: <?php echo (int)$totalIncidents; ?></div>
                    </div>
                </div>
            </div>

            <!-- Active / Open Cases -->
            <div class="card mt-3 p-3">
                <h6>Active / Open Cases</h6>
                <?php if (!$foundTable): ?>
                    <div class="alert alert-info">No case/incident table detected. The dashboard shows profile only. If your case data exists in another table name, add it to the candidate list in the script or rename it to a common name (incidents, cases).</div>
                <?php elseif (empty($recentOpen)): ?>
                    <div class="alert alert-success small mb-0">No active cases</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentOpen as $c): ?>
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex w-100 justify-content-between">
                                    <div>
                                        <strong>#<?php echo htmlspecialchars($c[$caseColMap['id']] ?? ($c['id'] ?? ''), ENT_QUOTES,'UTF-8'); ?></strong>
                                        <span class="small text-muted ms-2"><?php echo htmlspecialchars($c[$caseColMap['report_date']] ?? ($c['report_date'] ?? ''), ENT_QUOTES,'UTF-8'); ?></span>
                                        <div class="small-desc mt-1"><?php echo htmlspecialchars(substr($c[$caseColMap['offense']] ?? ($c['offense'] ?? ''), 0, 160), ENT_QUOTES,'UTF-8'); ?></div>
                                    </div>
                                    <div class="text-end small">
                                        <?php $sev = $c[$caseColMap['severity']] ?? ($c['severity'] ?? ''); ?>
                                        <div class="mb-1"><span class="badge <?php echo stripos($sev,'crit')!==false?'badge-sev-critical':(stripos($sev,'maj')!==false?'badge-sev-major':'badge-sev-minor'); ?>"><?php echo htmlspecialchars($sev?:'N/A', ENT_QUOTES,'UTF-8'); ?></span></div>
                                        <div class="small-desc"><?php echo htmlspecialchars($c[$caseColMap['status']] ?? ($c['status'] ?? ''), ENT_QUOTES,'UTF-8'); ?></div>
                                        <div class="small-desc mt-2"><?php echo htmlspecialchars($c[$caseColMap['next_action']] ?? ($c['next_action'] ?? ''), ENT_QUOTES,'UTF-8'); ?></div>
                                        <?php if (!empty($c[$caseColMap['deadline']] ?? ($c['deadline'] ?? ''))): ?>
                                            <div class="small-desc text-danger mt-1">Deadline: <?php echo htmlspecialchars($c[$caseColMap['deadline']] ?? '', ENT_QUOTES,'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Case history -->
            <div class="card mt-3 p-3">
                <h6>Case history (resolved)</h6>
                <?php if (!$foundTable || empty($recentResolved)): ?>
                    <div class="alert alert-info small mb-0">No resolved case history available.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead><tr>
                                <th>Case</th><th>Reported</th><th>Resolved</th><th>Offence</th><th>Decision / Sanctions</th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($recentResolved as $h): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($h[$caseColMap['id']] ?? ($h['id'] ?? ''), ENT_QUOTES,'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($h[$caseColMap['report_date']] ?? ($h['report_date'] ?? ''), ENT_QUOTES,'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($h[$caseColMap['resolved_date']] ?? ($h['resolved_date'] ?? ''), ENT_QUOTES,'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(substr($h[$caseColMap['offense']] ?? ($h['offense'] ?? ''), 0, 80), ENT_QUOTES,'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(substr(($h[$caseColMap['decision']] ?? ($h['decision'] ?? '')) . ' ' . ($h[$caseColMap['sanctions']] ?? ($h['sanctions'] ?? '')), 0, 120), ENT_QUOTES,'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="col-md-4">
            <div class="card p-3 card-stat">
                <h6 class="mb-2">Disciplinary status overview</h6>
                <div class="mb-2"><strong><?php
                    // simple overall status heuristic
                    if ($openCount > 3 || stripos(($profile[$col_status] ?? ''), 'suspend') !== false) echo 'Suspended/High risk';
                    elseif ($openCount >= 1) echo 'Under review / Warning';
                    else echo 'Good standing';
                ?></strong></div>
                <div class="mb-2 small-desc">Open: <?php echo (int)$openCount; ?> • Resolved: <?php echo (int)$resolvedCount; ?></div>
                <canvas id="severityGauge" width="220" height="160"></canvas>
                <div class="mt-2 small-desc">Severity score: <?php echo (int)$severityScore; ?> / 100</div>
            </div>

            <div class="card p-3 mt-3">
                <h6 class="mb-2">Notifications & alerts</h6>
                <?php if (!$foundTable): ?>
                    <div class="small-desc">No case table detected — notifications unavailable.</div>
                <?php else: ?>
                    <ul class="list-unstyled small mb-0">
                        <?php
                        // hearing / deadline reminders (cases with upcoming deadlines)
                        $now = date('Y-m-d');
                        $upcoming = [];
                        if (!empty($caseColMap['deadline'])) {
                            $sql = "SELECT `{$caseColMap['id']}` AS id, `{$caseColMap['deadline']}` AS dl, `{$caseColMap['offense']}` AS off FROM `{$foundTable}` WHERE `{$caseColMap['student_fk']}` = ? AND `{$caseColMap['deadline']}` >= ? ORDER BY `{$caseColMap['deadline']}` ASC LIMIT 6";
                            $stmt = $mysqli->prepare($sql);
                            $stmt->bind_param('ss', $profile[$caseColMap['student_fk']] ?? $studentId, $now);
                            $stmt->execute();
                            $upcoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $stmt->close();
                        }
                        if (empty($upcoming)) {
                            echo '<li class="small-desc">No upcoming deadlines.</li>';
                        } else {
                            foreach ($upcoming as $u) {
                                echo '<li><strong>#' . htmlspecialchars($u['id'], ENT_QUOTES,'UTF-8') . '</strong> — ' . htmlspecialchars($u['off'] ?? '', ENT_QUOTES,'UTF-8') . '<br><span class="small-desc">Deadline: ' . htmlspecialchars($u['dl'], ENT_QUOTES,'UTF-8') . '</span></li>';
                            }
                        }
                        ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card p-3 mt-3">
                <h6 class="mb-2">Sanctions & tasks</h6>
                <?php if (empty($sanctionsList)): ?>
                    <div class="small-desc">No sanctions recorded.</div>
                <?php else: ?>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach ($sanctionsList as $s): ?>
                            <li><strong>#<?php echo htmlspecialchars($s['id'], ENT_QUOTES,'UTF-8'); ?></strong> — <?php echo htmlspecialchars($s['offense'] ?? '', ENT_QUOTES,'UTF-8'); ?><br>
                                <span class="small-desc">Sanction: <?php echo htmlspecialchars($s['sanction'] ?? '', ENT_QUOTES,'UTF-8'); ?> • Status: <?php echo htmlspecialchars($s['status'] ?? '', ENT_QUOTES,'UTF-8'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card p-3 mt-3">
                <h6 class="mb-2">Appeals (if applicable)</h6>
                <div class="small-desc">This section shows cases eligible for appeal if your case table contains appeal dates/flags. If you want appeals tracked add a column like appeal_deadline or appeal_status to the case table.</div>
            </div>
        </div>
    </div>

    <footer class="small text-muted text-center my-4">Dashboard generated at <?php echo date('Y-m-d H:i:s'); ?></footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    // severityGauge: draw simple doughnut representing severityScore
    const score = <?php echo json_encode((int)$severityScore); ?>;
    const ctx = document.getElementById('severityGauge');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Severity','Remaining'],
                datasets: [{ data: [score, 100-score], backgroundColor: ['#e74a3b','#e9ecef'], hoverOffset: 4 }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    tooltip: { enabled: false },
                    legend: { display: false },
                    beforeDraw: {
                        // noop
                    }
                }
            }
        });
    }
})();
</script>
</body>
</html>

<?php
// Debug panel: set true to show detection results
$DEBUG = true;

if ($DEBUG) {
    $debugInfo = [
        'foundTable' => $foundTable,
        'caseColsDetected' => $caseCols,
        'caseColMap' => $caseColMap,
        'studentColsDetected' => $studentCols,
        'studentProfileSample' => $profile,
    ];
    // render simple debug block (will appear above dashboard content)
    echo '<div class="container py-3"><div class="alert alert-warning"><strong>Debug info</strong><pre style="white-space:pre-wrap;">' . htmlspecialchars(print_r($debugInfo, true), ENT_QUOTES, 'UTF-8') . '</pre></div></div>';
}