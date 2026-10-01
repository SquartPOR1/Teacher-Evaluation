<?php
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_role('admin');
$pdo = db();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'department') {
            $name = post_string('name', 120); $code = strtoupper(post_string('code', 20));
            if ($name === '' || $code === '') throw new InvalidArgumentException('Department name and code are required.');
            $pdo->prepare('INSERT INTO departments (name, code) VALUES (?, ?)')->execute([$name, $code]);
        } elseif ($action === 'subject') {
            $pdo->prepare('INSERT INTO subjects (department_id, code, name) VALUES (?, ?, ?)')->execute([(int) $_POST['department_id'], strtoupper(post_string('code',30)), post_string('name',160)]);
        } elseif ($action === 'class') {
            $pdo->prepare('INSERT INTO classes (department_id, name, school_year, semester) VALUES (?, ?, ?, ?)')->execute([(int) $_POST['department_id'], post_string('name',100), post_string('school_year',20), post_string('semester',30)]);
        } elseif ($action === 'assignment') {
            $pdo->prepare('INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) SELECT t.id,s.id,c.id FROM teachers t JOIN users u ON u.id=t.user_id JOIN subjects s ON s.id=? JOIN classes c ON c.id=? WHERE t.id=? AND s.department_id=c.department_id AND u.department_id=s.department_id')->execute([(int) $_POST['subject_id'], (int) $_POST['class_id'], (int) $_POST['teacher_id']]);
            if ($pdo->query('SELECT ROW_COUNT()')->fetchColumn() !== 1) throw new InvalidArgumentException('Select a valid teacher, subject, and class.');
        } elseif ($action === 'enrollment') {
            $pdo->prepare('INSERT INTO class_students (class_id, student_id) SELECT c.id,s.id FROM classes c JOIN students s ON s.id=? JOIN users u ON u.id=s.user_id WHERE c.id=? AND u.department_id=c.department_id')->execute([(int) $_POST['student_id'], (int) $_POST['class_id']]);
        } elseif ($action === 'period') {
            $start = post_string('starts_at',30); $end = post_string('ends_at',30);
            if (!valid_date_range($start,$end)) throw new InvalidArgumentException('The end date must be later than the start date.');
            $pdo->prepare('INSERT INTO evaluation_periods (name, starts_at, ends_at, created_by) VALUES (?, ?, ?, ?)')->execute([post_string('name',120), date('Y-m-d H:i:s', strtotime($start)), date('Y-m-d H:i:s', strtotime($end)), current_user()['id']]);
        } elseif ($action === 'period_status') {
            $periodId = (int) $_POST['period_id'];
            $periodInfo = $pdo->prepare('SELECT name,status,starts_at,ends_at FROM evaluation_periods WHERE id=?');
            $periodInfo->execute([$periodId]);
            $period = $periodInfo->fetch();
            if (!$period) throw new InvalidArgumentException('Evaluation period not found.');
            $status = (string) $_POST['status'];
            if (!in_array($status,['draft','open','closed'],true)) throw new InvalidArgumentException('Choose a valid period status.');
            if ($status === 'open' && (strtotime($period['starts_at']) > time() || strtotime($period['ends_at']) <= time())) throw new InvalidArgumentException('The period can only open between its configured start and end dates.');
            $pdo->prepare("UPDATE evaluation_periods SET status=? WHERE id=? AND (? <> 'open' OR starts_at <= NOW()) AND (? <> 'open' OR ends_at > NOW())")->execute([(string) $_POST['status'], (int) $_POST['period_id'], (string) $_POST['status'], (string) $_POST['status']]);
            if ($status === 'open' && $period['status'] !== 'open') {
                $periodName = $period['name'];
                if ($periodName !== '') {
                    $notify = $pdo->prepare('INSERT INTO notifications(user_id,title,message) SELECT DISTINCT st.user_id,?,? FROM class_students cs JOIN students st ON st.id=cs.student_id JOIN teacher_subjects ts ON ts.class_id=cs.class_id');
                    $notify->execute(['Evaluations are open', 'The evaluation period "'.$periodName.'" is now open. Sign in to view your assigned evaluations.']);
                }
            }
        } else {
            throw new InvalidArgumentException('Unknown setup action.');
        }
        audit('configure', 'academics');
        flash('Academic setup updated.');
        redirect('admin/academics.php');
    } catch (Throwable $exception) {
        $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : ($exception->getCode() === '23000' ? 'That record or assignment already exists.' : 'The requested setup could not be saved.');
    }
}
$departments = $pdo->query('SELECT id,name,code FROM departments ORDER BY name')->fetchAll();
$subjects = $pdo->query('SELECT s.id,s.name,s.code,d.name department FROM subjects s JOIN departments d ON d.id=s.department_id ORDER BY s.name')->fetchAll();
$classes = $pdo->query('SELECT c.id,c.name,c.school_year,c.semester,d.name department FROM classes c JOIN departments d ON d.id=c.department_id ORDER BY c.school_year DESC,c.name')->fetchAll();
$teachers = $pdo->query('SELECT t.id,u.full_name FROM teachers t JOIN users u ON u.id=t.user_id WHERE u.status="active" ORDER BY u.full_name')->fetchAll();
$students = $pdo->query('SELECT s.id,u.full_name FROM students s JOIN users u ON u.id=s.user_id WHERE u.status="active" ORDER BY u.full_name')->fetchAll();
$periods = $pdo->query('SELECT * FROM evaluation_periods ORDER BY starts_at DESC')->fetchAll();
$pageTitle = 'Academics & setup';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><div class="eyebrow">ACADEMIC CONFIGURATION</div><h1>Academics & setup</h1><p>Build the department → class → subject → teacher relationships students are allowed to evaluate.</p></div></div><?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="setup-grid"><section class="panel"><h2>Departments</h2><form method="post" class="form-stack compact-form"><?= csrf_field() ?><input type="hidden" name="action" value="department"><label>Name<input name="name" required maxlength="120"></label><label>Code<input name="code" required maxlength="20"></label><button class="button button-primary">Add department</button></form><ul class="simple-list"><?php foreach ($departments as $d): ?><li><?= e($d['name']) ?><span><?= e($d['code']) ?></span></li><?php endforeach; ?></ul></section>
<section class="panel"><h2>Subjects</h2><form method="post" class="form-stack compact-form"><?= csrf_field() ?><input type="hidden" name="action" value="subject"><label>Department<select name="department_id" required><?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></label><label>Code<input name="code" required maxlength="30"></label><label>Name<input name="name" required maxlength="160"></label><button class="button button-primary">Add subject</button></form><ul class="simple-list"><?php foreach ($subjects as $s): ?><li><?= e($s['name']) ?><span><?= e($s['code']) ?></span></li><?php endforeach; ?></ul></section>
<section class="panel"><h2>Classes</h2><form method="post" class="form-stack compact-form"><?= csrf_field() ?><input type="hidden" name="action" value="class"><label>Department<select name="department_id" required><?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></label><label>Class / section<input name="name" required maxlength="100" placeholder="BSIT · Year 2 · A"></label><div class="field-row"><label>School year<input name="school_year" required maxlength="20" placeholder="2026–2027"></label><label>Semester<input name="semester" required maxlength="30" placeholder="First"></label></div><button class="button button-primary">Add class</button></form><ul class="simple-list"><?php foreach ($classes as $c): ?><li><?= e($c['name']) ?><span><?= e($c['school_year'].' · '.$c['semester']) ?></span></li><?php endforeach; ?></ul></section>
<section class="panel"><h2>Teacher assignment</h2><p class="muted">Assignment is restricted by the selected existing records.</p><form method="post" class="form-stack compact-form"><?= csrf_field() ?><input type="hidden" name="action" value="assignment"><label>Teacher<select name="teacher_id" required><?php foreach ($teachers as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach; ?></select></label><label>Subject<select name="subject_id" required><?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['code'].' · '.$s['name']) ?></option><?php endforeach; ?></select></label><label>Class<select name="class_id" required><?php foreach ($classes as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name'].' · '.$c['school_year']) ?></option><?php endforeach; ?></select></label><button class="button button-primary">Assign teacher</button></form></section>
<section class="panel"><h2>Student enrollment</h2><form method="post" class="form-stack compact-form"><?= csrf_field() ?><input type="hidden" name="action" value="enrollment"><label>Student<select name="student_id" required><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?></option><?php endforeach; ?></select></label><label>Class<select name="class_id" required><?php foreach ($classes as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name'].' · '.$c['school_year']) ?></option><?php endforeach; ?></select></label><button class="button button-primary">Enroll student</button></form></section>
<section class="panel"><h2>Evaluation periods</h2><form method="post" class="form-stack compact-form"><?= csrf_field() ?><input type="hidden" name="action" value="period"><label>Period name<input name="name" required maxlength="120" placeholder="2026–2027 · Semester 1"></label><div class="field-row"><label>Opens<input type="datetime-local" name="starts_at" required></label><label>Closes<input type="datetime-local" name="ends_at" required></label></div><button class="button button-primary">Create period</button></form><?php foreach ($periods as $p): ?><div class="period-row"><span><strong><?= e($p['name']) ?></strong><small><?= e(date('M j, Y',strtotime($p['starts_at'])).' – '.date('M j, Y',strtotime($p['ends_at']))) ?></small></span><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="period_status"><input type="hidden" name="period_id" value="<?= (int)$p['id'] ?>"><select name="status"><option value="draft" <?= $p['status']==='draft'?'selected':'' ?>>Draft</option><option value="open" <?= $p['status']==='open'?'selected':'' ?>>Open</option><option value="closed" <?= $p['status']==='closed'?'selected':'' ?>>Closed</option></select><button class="button button-small">Save</button></form></div><?php endforeach; ?></section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>