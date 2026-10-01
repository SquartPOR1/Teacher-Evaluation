<?php
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_role('student');
$pdo = db();
$student = $pdo->prepare('SELECT id FROM students WHERE user_id=?');
$student->execute([current_user()['id']]);
$studentId = (int) $student->fetchColumn();
$teacherId = filter_input(INPUT_GET, 'teacher', FILTER_VALIDATE_INT) ?: 0;
$subjectId = filter_input(INPUT_GET, 'subject', FILTER_VALIDATE_INT) ?: 0;
$classId = filter_input(INPUT_GET, 'class', FILTER_VALIDATE_INT) ?: 0;
$periodId = filter_input(INPUT_GET, 'period', FILTER_VALIDATE_INT) ?: 0;
$eligibility = $pdo->prepare("SELECT t.id teacher_id,s.id subject_id,c.id class_id,p.id period_id,u.full_name teacher_name,s.name subject_name,c.name class_name,p.name period_name,p.ends_at FROM class_students cs JOIN teacher_subjects ts ON ts.class_id=cs.class_id JOIN teachers t ON t.id=ts.teacher_id JOIN users u ON u.id=t.user_id JOIN subjects s ON s.id=ts.subject_id JOIN classes c ON c.id=ts.class_id JOIN evaluation_periods p ON p.id=? AND p.status='open' AND NOW() BETWEEN p.starts_at AND p.ends_at WHERE cs.student_id=? AND t.id=? AND s.id=? AND c.id=? LIMIT 1");
$eligibility->execute([$periodId,$studentId,$teacherId,$subjectId,$classId]);
$assignment = $eligibility->fetch();
if (!$assignment) { http_response_code(403); $pageTitle='Unavailable evaluation'; require __DIR__ . '/../includes/header.php'; ?><section class="empty-state"><h1>Evaluation unavailable</h1><p>This assignment is not open for your account.</p><a class="button" href="<?= e(url('student/index.php')) ?>">Back to dashboard</a></section><?php require __DIR__ . '/../includes/footer.php'; exit; }
$check = $pdo->prepare('SELECT id FROM evaluations WHERE student_id=? AND teacher_id=? AND class_id=? AND subject_id=? AND period_id=?');
$check->execute([$studentId,$teacherId,$classId,$subjectId,$periodId]);
if ($check->fetchColumn()) { flash('This evaluation has already been submitted.', 'info'); redirect('student/index.php'); }
$questions = $pdo->query("SELECT q.id,q.prompt,q.question_type,q.is_required,c.name category,c.sort_order category_order,q.sort_order FROM questions q JOIN question_categories c ON c.id=q.category_id WHERE q.is_active=1 AND c.is_active=1 ORDER BY c.sort_order,q.sort_order,q.id")->fetchAll();
$groups = [];
foreach ($questions as $question) $groups[$question['category']][] = $question;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ratings = $_POST['ratings'] ?? [];
    $textAnswers = $_POST['answers'] ?? [];
    $comment = mb_substr(trim((string)($_POST['comment'] ?? '')),0,5000);
    $valid = true;
    foreach ($questions as $question) {
        $id = (string)$question['id'];
        if ($question['question_type'] === 'rating') {
            $value = filter_var($ratings[$id] ?? null, FILTER_VALIDATE_INT);
            if (($question['is_required'] && !$value) || ($value && ($value < 1 || $value > 5))) $valid = false;
        } else {
            if ($question['is_required'] && trim((string)($textAnswers[$id] ?? '')) === '') $valid = false;
        }
    }
    if (!$valid) {
        $error = 'Please answer each required item using a valid response.';
    } else {
        try {
            $pdo->beginTransaction();
            $eligibility->execute([$periodId,$studentId,$teacherId,$subjectId,$classId]);
            if (!$eligibility->fetch()) throw new RuntimeException('Assignment closed or changed.');
            $anonymous = setting('evaluation_anonymous','1') === '1' ? 1 : 0;
            $insert = $pdo->prepare('INSERT INTO evaluations (student_id,teacher_id,class_id,subject_id,period_id,is_anonymous) VALUES (?,?,?,?,?,?)');
            $insert->execute([$studentId,$teacherId,$classId,$subjectId,$periodId,$anonymous]);
            $evaluationId = (int)$pdo->lastInsertId();
            $answerInsert = $pdo->prepare('INSERT INTO evaluation_answers (evaluation_id,question_id,rating_value,answer_text) VALUES (?,?,?,?)');
            foreach ($questions as $question) {
                $id=(string)$question['id'];
                $rating=$question['question_type']==='rating' && isset($ratings[$id]) ? (int)$ratings[$id] : null;
                $text=$question['question_type']==='text' ? mb_substr(trim((string)($textAnswers[$id]??'')),0,5000) : null;
                $answerInsert->execute([$evaluationId,$question['id'],$rating,$text]);
            }
            if ($comment !== '' && setting('comments_enabled','1') === '1') {
                $pdo->prepare('INSERT INTO evaluation_comments (evaluation_id,comment_text,visibility) VALUES (?,?,?)')->execute([$evaluationId,$comment,'teacher']);
            }
            $pdo->prepare('INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)')->execute([current_user()['id'],'Evaluation submitted','Your feedback for '.$assignment['subject_name'].' has been recorded.']);
            $pdo->commit();
            audit('submit', 'evaluations', $evaluationId);
            flash('Thank you. Your evaluation has been submitted.');
            redirect('student/index.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $exception instanceof PDOException && $exception->getCode()==='23000' ? 'This evaluation was already submitted.' : 'Unable to submit. The period may have closed; please try again.';
        }
    }
}
$pageTitle='Evaluate teacher';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><a class="back-link" href="<?= e(url('student/index.php')) ?>">← My evaluations</a><div class="eyebrow"><?= e($assignment['period_name']) ?></div><h1>Evaluate <?= e($assignment['teacher_name']) ?></h1><p><?= e($assignment['subject_name'].' · '.$assignment['class_name']) ?></p></div><span class="pill pill-blue" id="progress-label">0% complete</span></div>
<section class="panel evaluation-panel"><div class="evaluation-intro"><span class="shield">◇</span><p><?= setting('evaluation_anonymous','1')==='1' ? 'Your identity is kept separate from feedback shown to the teacher. Please be candid and respectful.' : 'Your feedback is associated with your account. Please be candid and respectful.' ?></p></div><?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post" id="evaluation-form"><?= csrf_field() ?><?php foreach ($groups as $category=>$items): ?><fieldset class="question-group"><legend><?= e($category) ?></legend><?php foreach ($items as $question): ?><div class="question-item"><label class="question-prompt" for="q-<?= (int)$question['id'] ?>"><?= e($question['prompt']) ?><?= $question['is_required']?'<span class="required-mark">*</span>':'' ?></label><?php if ($question['question_type']==='rating'): ?><div class="rating-options"><?php for($value=1;$value<=5;$value++): ?><label class="rating-option"><input type="radio" id="q-<?= (int)$question['id'] ?>-<?= $value ?>" name="ratings[<?= (int)$question['id'] ?>]" value="<?= $value ?>" <?= isset($_POST['ratings'][$question['id']]) && (int)$_POST['ratings'][$question['id']]===$value?'checked':'' ?> <?= $question['is_required']?'required':'' ?>><span><?= $value ?></span><small><?= ['','Poor','Needs improvement','Good','Very good','Excellent'][$value] ?></small></label><?php endfor; ?></div><?php else: ?><textarea id="q-<?= (int)$question['id'] ?>" name="answers[<?= (int)$question['id'] ?>]" maxlength="5000" <?= $question['is_required']?'required':'' ?>><?= e($_POST['answers'][$question['id']]??'') ?></textarea><?php endif; ?></div><?php endforeach; ?></fieldset><?php endforeach; ?><div class="question-group"><label class="question-prompt" for="comment">Additional comments <span class="muted">(optional)</span></label><textarea id="comment" name="comment" maxlength="5000" placeholder="Share constructive suggestions or a specific example…"><?= e($_POST['comment']??'') ?></textarea></div><div class="form-actions"><a class="button button-quiet" href="<?= e(url('student/index.php')) ?>">Save for later by leaving</a><button class="button button-primary" type="submit">Submit evaluation <span>→</span></button></div></form></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>