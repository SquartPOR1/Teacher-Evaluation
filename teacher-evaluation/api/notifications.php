<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/csrf.php';require_login();
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
    if($id){$stmt=db()->prepare('UPDATE notifications SET read_at=NOW() WHERE id=? AND user_id=?');$stmt->execute([$id,current_user()['id']]);}
}elseif($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);echo json_encode(['error'=>'Method not allowed']);exit;}
$stmt=db()->prepare('SELECT id,title,message,created_at,read_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 25');$stmt->execute([current_user()['id']]);
echo json_encode(['notifications'=>$stmt->fetchAll()],JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);