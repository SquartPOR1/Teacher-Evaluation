<?php
require_once __DIR__.'/../includes/auth.php';require_login();
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
$query=mb_substr(trim((string)($_GET['q']??'')),0,80);$results=[];
if(mb_strlen($query)>=2){$like='%'.$query.'%';$role=current_user()['role'];
    if($role==='admin'){$stmt=db()->prepare('SELECT u.full_name label,u.email detail,r.name type FROM users u JOIN roles r ON r.id=u.role_id WHERE u.full_name LIKE ? OR u.email LIKE ? ORDER BY u.full_name LIMIT 10');$stmt->execute([$like,$like]);$results=$stmt->fetchAll();}
    elseif($role==='teacher'){$stmt=db()->prepare('SELECT s.name label,CONCAT(c.name," · ",c.school_year) detail,"subject" type FROM teacher_subjects ts JOIN subjects s ON s.id=ts.subject_id JOIN classes c ON c.id=ts.class_id JOIN teachers t ON t.id=ts.teacher_id WHERE t.user_id=? AND (s.name LIKE ? OR s.code LIKE ? OR c.name LIKE ?) ORDER BY s.name LIMIT 10');$stmt->execute([current_user()['id'],$like,$like,$like]);$results=$stmt->fetchAll();}
    else{$stmt=db()->prepare('SELECT u.full_name label,s.name detail,"teacher" type FROM class_students cs JOIN teacher_subjects ts ON ts.class_id=cs.class_id JOIN teachers t ON t.id=ts.teacher_id JOIN users u ON u.id=t.user_id JOIN subjects s ON s.id=ts.subject_id JOIN students st ON st.id=cs.student_id WHERE st.user_id=? AND (u.full_name LIKE ? OR s.name LIKE ?) ORDER BY u.full_name LIMIT 10');$stmt->execute([current_user()['id'],$like,$like]);$results=$stmt->fetchAll();}
}
echo json_encode(['results'=>$results],JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);