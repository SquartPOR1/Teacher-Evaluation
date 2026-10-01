<?php
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_role('admin');
$pdo = db();
$studentPrograms = ['BSIT', 'BSCRIM', 'BSHM', 'BSA', 'BSBA', 'BSED'];
$allDepartments = $pdo->query('SELECT id,name FROM departments ORDER BY name')->fetchAll();
foreach ($studentPrograms as $program) {
    $departmentLookup = $pdo->prepare('SELECT id FROM departments WHERE code = ? LIMIT 1');
    $departmentLookup->execute([$program]);
    $departmentId = $departmentLookup->fetchColumn();
    if ($departmentId === false) {
        $departmentLookup = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
        $departmentLookup->execute([$program]);
        $departmentId = $departmentLookup->fetchColumn();
    }
    if ($departmentId === false) {
        $pdo->prepare('INSERT INTO departments (name, code) VALUES (?, ?)')->execute([$program, $program]);
    }
}
$programPlaceholders = implode(',', array_fill(0, count($studentPrograms), '?'));
$departmentQuery = $pdo->prepare("SELECT id,name FROM departments WHERE code NOT IN ($programPlaceholders) AND name NOT IN ($programPlaceholders) ORDER BY name");
$departmentQuery->execute(array_merge($studentPrograms, $studentPrograms));
$departments = $departmentQuery->fetchAll();
$studentDepartmentIds = [];
foreach ($studentPrograms as $program) {
    $departmentLookup = $pdo->prepare('SELECT id FROM departments WHERE code = ? LIMIT 1');
    $departmentLookup->execute([$program]);
    $departmentId = $departmentLookup->fetchColumn();
    if ($departmentId === false) {
        $departmentLookup = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
        $departmentLookup->execute([$program]);
        $departmentId = $departmentLookup->fetchColumn();
    }
    if ($departmentId !== false) {
        $studentDepartmentIds[$program] = (int) $departmentId;
    }
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'update') {
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $name = post_string('full_name', 160);
        $email = mb_strtolower(post_string('email', 190));
        $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['status'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        if (!$userId || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($status, ['active','inactive'], true) || ($newPassword !== '' && strlen($newPassword) < 12)) {
            $error = 'Enter valid account details; a new password must be at least 12 characters.';
        } elseif ((int)$userId === (int)current_user()['id'] && $status !== 'active') {
            $error = 'You cannot deactivate your own administrator account.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE users SET full_name=?,email=?,department_id=?,status=? WHERE id=?');
                $stmt->execute([$name,$email,$departmentId ?: null,$status,$userId]);
                if ($newPassword !== '') $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($newPassword,PASSWORD_DEFAULT),$userId]);
                audit('update','users',(int)$userId);
                flash('Account updated.');
                redirect('admin/users.php');
            } catch (PDOException $exception) {
                $error = $exception->getCode()==='23000' ? 'That email address is already in use.' : 'The account could not be updated.';
            }
        }
    } else {
    $name = post_string('full_name', 160);
    $email = mb_strtolower(post_string('email', 190));
    $role = (string) ($_POST['role'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $departmentId = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT) ?: null;
    $profileNumber = post_string('profile_number', 40);
    $year = post_string('year_level', 30);
    $section = post_string('section', 60);
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['admin','teacher','student'], true) || strlen($password) < 12) {
        $error = 'Enter a name, valid email, valid role, and password of at least 12 characters.';
    } elseif ($role === 'student' && !$departmentId) {
        $error = 'Choose a department for this account.';
    } elseif ($role === 'student' && !in_array((int) $departmentId, array_values($studentDepartmentIds), true)) {
        $error = 'Choose one of the listed student programs.';
    } elseif ($role !== 'admin' && $profileNumber === '') {
        $error = $role === 'teacher' ? 'Employee number is required.' : 'Student number is required.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users (role_id, department_id, full_name, email, password_hash) SELECT id, ?, ?, ?, ? FROM roles WHERE name = ?');
            $stmt->execute([$departmentId, $name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            $userId = (int) $pdo->lastInsertId();
            if ($role === 'teacher') {
                $pdo->prepare('INSERT INTO teachers (user_id, employee_number) VALUES (?, ?)')->execute([$userId, $profileNumber]);
            } elseif ($role === 'student') {
                $pdo->prepare('INSERT INTO students (user_id, student_number, year_level, section) VALUES (?, ?, ?, ?)')->execute([$userId, $profileNumber, $year ?: null, $section ?: null]);
            }
            $pdo->commit();
            audit('create', 'users', $userId, ['role' => $role]);
            flash('Account created. Share the temporary password securely.');
            redirect('admin/users.php');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $exception->getCode() === '23000' ? 'Email or profile number is already in use.' : 'The account could not be created.';
        }
    }
    }
}
$users = $pdo->query('SELECT u.id,u.full_name,u.email,u.status,u.department_id,r.name AS role,d.name AS department,u.created_at FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN departments d ON d.id=u.department_id ORDER BY u.created_at DESC LIMIT 100')->fetchAll();
$pageTitle = 'People';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><div class="eyebrow">DIRECTORY</div><h1>People</h1><p>Create and review accounts. Passwords are stored as secure hashes.</p></div></div>
<div class="two-column"><section class="panel"><div class="eyebrow">NEW ACCOUNT</div><h2>Add a person</h2><?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?><form method="post" class="form-stack"><?= csrf_field() ?><label>Full name<input name="full_name" required maxlength="160" value="<?= e($_POST['full_name'] ?? '') ?>"></label><label>Email<input type="email" name="email" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>"></label><label>Role<select name="role" id="user-role" required><option value="">Choose a role</option><?php foreach (['admin'=>'Administrator','teacher'=>'Teacher','student'=>'Student'] as $key=>$label): ?><option value="<?= e($key) ?>" <?= ($_POST['role'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label data-department-kind="student" hidden>Department<select name="department_id" disabled><option value="">Select program</option><?php foreach ($studentPrograms as $program): ?><option value="<?= (int) ($studentDepartmentIds[$program] ?? 0) ?>" <?= ($_POST['department_id'] ?? '') == ($studentDepartmentIds[$program] ?? null) ? 'selected' : '' ?>><?= e($program) ?></option><?php endforeach; ?></select></label>
<label data-department-kind="other">Department<select name="department_id" disabled><option value=""><?= $departments ? 'Select department (optional for admins)' : 'No staff departments configured' ?></option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= ($_POST['department_id'] ?? '') == $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select><?php if (!$departments): ?><small data-staff-department-help hidden>Teacher accounts can be created without a department, but must be assigned one before course assignments. Add departments under <a href="<?= e(url('admin/academics.php')) ?>">Academics &amp; setup</a>.</small><?php endif; ?></label>
<label><span data-profile-number-label>Employee / student number</span><input name="profile_number" maxlength="40"></label>
<div class="field-row" data-student-profile-fields hidden>
<label>Year level (students)<input name="year_level" maxlength="30"></label>
<label>Section (students)<input name="section" maxlength="60"></label>
</div>
<label>Temporary password<input type="password" name="password" minlength="12" autocomplete="new-password" required>
<small>At least 12 characters. Provide it to the account owner through a secure channel.</small>
</label>
<button class="button button-primary" type="submit" data-create-account-button <?= !$departments ? 'data-no-staff-departments="true"' : '' ?>>Create account</button>
</form></section>
<section class="panel"><div class="eyebrow">ACCOUNT DIRECTORY</div><h2>Recently added · edit account details</h2>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Account</th>
<th>Department</th>
<th>Status / update</th>
</tr>
</thead>
<tbody>
<?php foreach ($users as $person): ?>
<tr>
<td><strong><?= e($person['full_name']) ?></strong><small><?= e(ucfirst($person['role'])) ?> · <?= e($person['email']) ?></small></td>
<td><?= e($person['department'] ?? '—') ?></td>
<td>
<details>
<summary class="text-link">Edit account</summary>
<form method="post" class="form-stack compact-form account-edit">
<?= csrf_field() ?>
<input type="hidden" name="action" value="update">
<input type="hidden" name="user_id" value="<?= (int)$person['id'] ?>">
<label>Name<input name="full_name" value="<?= e($person['full_name']) ?>" required maxlength="160"></label>
<label>Email<input type="email" name="email" value="<?= e($person['email']) ?>" required maxlength="190"></label>
<label>Department<select name="department_id">
<option value="">No department</option>
<?php foreach($allDepartments as $department):?>
<option value="<?= (int)$department['id']?>" <?= (int)$person['department_id']===(int)$department['id']?'selected':''?>><?=e($department['name'])?></option>
<?php endforeach;?>
</select>
</label>
<label>Status<select name="status">
<option value="active" <?=$person['status']==='active'?'selected':''?>>Active</option>
<option value="inactive" <?=$person['status']==='inactive'?'selected':''?>>Inactive</option>
</select>
</label>
<label>Reset password <small>(leave blank to keep current)</small>
<input type="password" name="new_password" minlength="12" autocomplete="new-password">
</label>
<button class="button button-primary button-small">Save account</button>
</form>
</details>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$users): ?>
<tr>
<td colspan="3">No accounts yet.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>