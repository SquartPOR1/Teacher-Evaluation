<?php
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_role('admin');
$pdo=db();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $school=post_string('school_name',160);
    if($school===''){$error='School name is required.';}else{
        $values=['school_name'=>$school,'contact_email'=>mb_strtolower(post_string('contact_email',190)),'evaluation_anonymous'=>isset($_POST['evaluation_anonymous'])?'1':'0','comments_enabled'=>isset($_POST['comments_enabled'])?'1':'0','results_visible_to_teachers'=>isset($_POST['results_visible_to_teachers'])?'1':'0'];
        if($values['contact_email']!==''&&!filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL)){$error='Enter a valid contact email.';}else{
            $stmt=$pdo->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
            foreach($values as $key=>$value)$stmt->execute([$key,$value]);
            audit('update','settings');flash('Settings saved.');redirect('admin/settings.php');
        }
    }
}
$pageTitle='Settings';require __DIR__.'/../includes/header.php';
?>
<div class="page-heading"><div><div class="eyebrow">SYSTEM CONFIGURATION</div><h1>School settings</h1><p>Control the portal identity and evaluation privacy behavior.</p></div></div>
<section class="panel settings-panel"><?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?><form method="post" class="form-stack"><?=csrf_field()?><label>School name<input name="school_name" required maxlength="160" value="<?=e($_POST['school_name']??setting('school_name',APP_NAME))?>"></label><label>Contact email <span class="muted">(optional)</span><input type="email" name="contact_email" maxlength="190" value="<?=e($_POST['contact_email']??setting('contact_email'))?>"></label><div class="settings-options"><label><input type="checkbox" name="evaluation_anonymous" value="1" <?=setting('evaluation_anonymous','1')==='1'?'checked':''?>> Keep student identity confidential from teachers</label><label><input type="checkbox" name="comments_enabled" value="1" <?=setting('comments_enabled','1')==='1'?'checked':''?>> Allow optional written comments</label><label><input type="checkbox" name="results_visible_to_teachers" value="1" <?=setting('results_visible_to_teachers','1')==='1'?'checked':''?>> Show aggregate results to teachers</label></div><p class="muted">Anonymous responses still retain a restricted student reference for eligibility checks and duplicate prevention. Reports never include student names.</p><button class="button button-primary">Save settings</button></form></section>
<?php require __DIR__.'/../includes/footer.php';?>