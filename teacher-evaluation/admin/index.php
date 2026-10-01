<?php
require_once __DIR__ . '/../includes/permissions.php';
require_role('admin');
$pdo = db();
$counts = [];
foreach (['Users' => 'users', 'Teachers' => 'teachers', 'Students' => 'students', 'Classes' => 'classes', 'Submitted evaluations' => 'evaluations'] as $label => $table) {
    $counts[$label] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}
$departmentsCount = (int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();
$subjectsCount = (int) $pdo->query('SELECT COUNT(*) FROM subjects WHERE status="active"')->fetchColumn();
$assignmentsCount = (int) $pdo->query('SELECT COUNT(*) FROM teacher_subjects')->fetchColumn();
$enrollmentsCount = (int) $pdo->query('SELECT COUNT(*) FROM class_students')->fetchColumn();
$activeQuestionsCount = (int) $pdo->query('SELECT COUNT(*) FROM questions q JOIN question_categories c ON c.id=q.category_id WHERE q.is_active=1 AND c.is_active=1')->fetchColumn();
$unconfiguredTeachers = (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN teachers t ON t.user_id=u.id WHERE r.name='teacher' AND (t.id IS NULL OR u.department_id IS NULL)")->fetchColumn();
$unconfiguredStudents = (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN students s ON s.user_id=u.id LEFT JOIN departments d ON d.id=u.department_id WHERE r.name='student' AND (s.id IS NULL OR d.id IS NULL OR d.code NOT IN ('BSIT','BSCRIM','BSHM','BSA','BSBA','BSED'))")->fetchColumn();
$openPeriod = $pdo->query("SELECT name, ends_at FROM evaluation_periods WHERE status='open' AND NOW() BETWEEN starts_at AND ends_at ORDER BY starts_at DESC LIMIT 1")->fetch();
$periodStats = $pdo->query("SELECT p.name, (SELECT COUNT(*) FROM teacher_subjects ts JOIN class_students cs ON cs.class_id=ts.class_id) AS assignments, (SELECT COUNT(*) FROM evaluations e WHERE e.period_id=p.id) AS responses FROM evaluation_periods p WHERE p.status IN ('open','closed') ORDER BY p.starts_at DESC LIMIT 1")->fetch();
$peopleReady = $departmentsCount > 0 && $counts['Teachers'] > 0 && $counts['Students'] > 0 && $unconfiguredTeachers === 0 && $unconfiguredStudents === 0;
$steps = [
    ['label' => 'Departments and people', 'detail' => $peopleReady ? 'Teacher and student accounts have profiles and departments.' : 'Create teacher and student accounts, then assign their departments.', 'complete' => $peopleReady],
    ['label' => 'Classes and assignments', 'detail' => 'Connect subjects, classes, teachers, and enrolled students.', 'complete' => $subjectsCount > 0 && $counts['Classes'] > 0 && $assignmentsCount > 0 && $enrollmentsCount > 0],
    ['label' => 'Questionnaire', 'detail' => 'Make sure an active category has at least one active question.', 'complete' => $activeQuestionsCount > 0],
    ['label' => 'Evaluation period', 'detail' => 'Open a period when everything is ready.', 'complete' => (bool) $openPeriod],
];
$completedSteps = count(array_filter($steps, static fn(array $step): bool => $step['complete']));
$nextStepIndex = null;
foreach ($steps as $index => $step) {
    if (!$step['complete']) {
        $nextStepIndex = $index;
        break;
    }
}
$stepUrls = ['admin/users.php', 'admin/academics.php', 'admin/questionnaire.php', 'admin/academics.php'];
$nextSetupUrl = $nextStepIndex === null ? 'admin/reports.php' : $stepUrls[$nextStepIndex];
$nextSetupLabel = $nextStepIndex === null ? 'Review reports' : 'Continue: ' . $steps[$nextStepIndex]['label'];
$pageTitle = 'Administrator overview';
require __DIR__ . '/../includes/header.php';
?>
<div class="overview-page">
    <section class="overview-hero">
        <div class="overview-hero-copy">
            <div class="eyebrow">OLPC-SMI &nbsp; / &nbsp; ADMINISTRATION</div>
            <h1>Prepare the next<br><em>feedback cycle.</em></h1>
            <p>Good day, <?= e(current_user()['name']) ?>. Review people, classes, and evaluation progress, then continue with the next setup step.</p>
            <a class="button button-primary" href="<?= e(url($nextSetupUrl)) ?>"><?= e($nextSetupLabel) ?> <span aria-hidden="true">↗</span></a>
        </div>
        <figure class="overview-hero-art" data-overview-scene>
            <canvas class="overview-scene-canvas" aria-hidden="true"></canvas>
            <figcaption class="overview-art-caption">TEACHING FEEDBACK <span>FIELD STUDY</span></figcaption>
            <div class="overview-art-controls" aria-label="Adjust illustration angle">
                <button type="button" data-scene-turn="-1" aria-label="Turn evaluation sheet left" title="Turn left">‹</button>
                <button type="button" data-scene-turn="1" aria-label="Turn evaluation sheet right" title="Turn right">›</button>
            </div>
        </figure>
        <div class="overview-hero-status">
            <span class="overview-status-label">CURRENT EVALUATION</span>
            <span class="overview-status-rule" aria-hidden="true"></span>
            <strong><?= $openPeriod ? e($openPeriod['name']) : 'No open period' ?></strong>
            <span class="overview-status-detail"><?= $openPeriod ? 'Accepting responses until ' . date('M j, Y', strtotime($openPeriod['ends_at'])) : 'Your next step is to prepare an evaluation period.' ?></span>
            <a href="<?= e(url('admin/academics.php')) ?>">View academic setup <span aria-hidden="true">→</span></a>
        </div>
        <span class="overview-hero-index" aria-hidden="true">01</span>
    </section>

    <section class="overview-metrics" aria-label="School overview">
        <?php foreach ($counts as $label => $count): ?><article class="overview-metric"><span><?= e($label) ?></span><strong><?= number_format($count) ?></strong></article><?php endforeach; ?>
    </section>

    <div class="overview-lower-grid">
        <section class="overview-setup">
            <div class="overview-section-heading"><div><div class="eyebrow">A GOOD PLACE TO BEGIN</div><h2>Evaluation readiness</h2></div><span><?= $completedSteps ?> <small>of 4 complete</small></span></div>
            <div class="overview-step-list">
                <?php foreach ($steps as $index => $step): ?>
                    <a class="overview-step<?= $step['complete'] ? ' is-complete' : ($nextStepIndex === $index ? ' is-next' : '') ?>" href="<?= e(url($stepUrls[$index])) ?>">
                        <span class="overview-step-number"><?= $step['complete'] ? '✓' : sprintf('%02d', $index + 1) ?></span>
                        <span class="overview-step-copy"><strong><?= e($step['label']) ?></strong><small><?= e($step['detail']) ?></small></span>
                        <span class="overview-step-arrow" aria-hidden="true">↗</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="overview-reports">
            <?php if ($periodStats): ?>
                <?php $rate = (int) ($periodStats['assignments'] ?? 0) > 0 ? min(100, round(100 * (int) $periodStats['responses'] / (int) $periodStats['assignments'])) : 0; ?>
                <div class="eyebrow">LATEST PERIOD</div>
                <h2><?= e($periodStats['name']) ?></h2>
                <p><?= (int) $periodStats['responses'] ?> <span>of <?= (int) $periodStats['assignments'] ?> eligible evaluations</span></p>
                <div class="overview-progress"><span style="width:<?= $rate ?>%"></span></div>
                <div class="overview-report-foot"><span><?= $rate ?>% response rate</span><a href="<?= e(url('admin/reports.php')) ?>">Open reports <span aria-hidden="true">→</span></a></div>
                <p class="overview-privacy-note">Teacher results stay hidden until a period has at least 3 submissions.</p>
                <span class="overview-updated">Overview refreshed <?= e(date('M j, Y · g:i A')) ?></span>
            <?php else: ?>
                <div class="eyebrow">REPORTING</div>
                <h2>No evaluation period yet</h2>
                <p class="overview-empty-copy">Create a period after classes, teacher assignments, and questions are ready. Response rates will appear here.</p>
                <a class="overview-empty-link" href="<?= e(url('admin/academics.php')) ?>">Prepare an evaluation period <span aria-hidden="true">→</span></a>
                <span class="overview-updated">Overview refreshed <?= e(date('M j, Y · g:i A')) ?></span>
            <?php endif; ?>
        </section>
    </div>
</div>
<script type="module" src="<?= e(url('assets/js/hero-3d.js?v=1')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>