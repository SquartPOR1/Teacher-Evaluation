<?php
require_once __DIR__ . '/includes/auth.php';

$role = $_GET['role'] ?? 'student';
$previews = [
    'student' => [
        'label' => 'Student workspace',
        'eyebrow' => 'YOUR FEEDBACK MATTERS',
        'title' => 'Evaluations that are easy to complete.',
        'description' => 'Students can review assigned classes, share confidential feedback, and track which evaluations are complete.',
        'metric' => '2',
        'metric_label' => 'evaluations to complete',
        'items' => ['See evaluations for enrolled classes', 'Rate teaching and leave optional comments', 'Submit one response for each assignment'],
    ],
    'teacher' => [
        'label' => 'Teacher workspace',
        'eyebrow' => 'INSIGHT FOR EVERY CLASS',
        'title' => 'See feedback designed to support growth.',
        'description' => 'Teachers can review private, aggregated feedback for their classes once the response privacy threshold is reached.',
        'metric' => '4.3',
        'metric_label' => 'sample average rating',
        'items' => ['Review aggregate results by period', 'Read comments only when privacy rules allow', 'Use class feedback to plan improvements'],
    ],
    'admin' => [
        'label' => 'Administrator workspace',
        'eyebrow' => 'A CLEAR VIEW OF THE PORTAL',
        'title' => 'Set up evaluations from one workspace.',
        'description' => 'Administrators manage people, departments, classes, questions, evaluation periods, and privacy-aware reports.',
        'metric' => '12',
        'metric_label' => 'sample active classes',
        'items' => ['Manage users and academic assignments', 'Configure questionnaires and open periods', 'Review reports and export anonymous results'],
    ],
];
if (!isset($previews[$role])) {
    $role = 'student';
}
$preview = $previews[$role];
$pageTitle = 'Portal demo';
require __DIR__ . '/includes/header.php';
?>
<section class="portal-demo">
  <div class="demo-intro">
    <div class="eyebrow">A QUICK LOOK INSIDE</div>
    <h1>One portal. A better feedback cycle.</h1>
    <p class="muted">Explore a sample workspace before signing in. This preview uses fictional information and does not create an account or submit data.</p>
    <div class="demo-role-tabs" aria-label="Choose a sample workspace">
      <?php foreach ($previews as $key => $item): ?>
        <a class="demo-role-tab<?= $role === $key ? ' is-active' : '' ?>" href="<?= e(url('demo.php?role=' . $key)) ?>"<?= $role === $key ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="demo-preview">
    <div class="demo-preview-copy">
      <div class="eyebrow"><?= e($preview['eyebrow']) ?></div>
      <h2><?= e($preview['title']) ?></h2>
      <p><?= e($preview['description']) ?></p>
      <ul><?php foreach ($preview['items'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul>
      <a class="button button-primary" href="<?= e(url('login.php')) ?>">Go to sign in <span aria-hidden="true">→</span></a>
    </div>
    <aside class="demo-sample-card" aria-label="Fictional workspace sample">
      <div class="demo-card-top"><span class="status-dot"></span> SAMPLE OVERVIEW <span class="demo-period">Spring term</span></div>
      <div class="demo-metric"><?= e($preview['metric']) ?></div>
      <p><?= e($preview['metric_label']) ?></p>
      <div class="demo-progress"><span></span></div>
      <div class="demo-sample-foot"><span>Preview data</span><span>Not a real account</span></div>
    </aside>
  </div>
</section>
<style>
.portal-demo{max-width:1050px;margin:28px auto;padding:0 8px}.demo-intro{max-width:700px;margin:0 auto 28px;text-align:center}.demo-intro h1{margin:10px 0;font-size:clamp(2rem,5vw,3.2rem);line-height:1.08;color:#183353}.demo-intro>p{max-width:610px;margin:0 auto;color:#687b91;line-height:1.7}.demo-role-tabs{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-top:24px}.demo-role-tab{border:1px solid #dce5ef;border-radius:999px;padding:9px 15px;color:#445872;text-decoration:none;font-size:.9rem;font-weight:650}.demo-role-tab:hover,.demo-role-tab.is-active{background:#153e75;border-color:#153e75;color:#fff}.demo-preview{display:grid;grid-template-columns:1.1fr .9fr;gap:35px;align-items:center;border:1px solid #e4ebf2;border-radius:22px;padding:clamp(24px,5vw,52px);background:linear-gradient(135deg,#fff,#f5f8fc);box-shadow:0 18px 45px #1833530d}.demo-preview h2{margin:10px 0 12px;color:#183353;font-size:clamp(1.7rem,4vw,2.4rem);line-height:1.15}.demo-preview-copy>p{color:#687b91;line-height:1.7}.demo-preview-copy ul{list-style:none;padding:0;margin:22px 0 27px}.demo-preview-copy li{position:relative;padding:0 0 12px 27px;color:#445872}.demo-preview-copy li:before{content:'✓';position:absolute;left:0;color:#24836d;font-weight:800}.demo-sample-card{border:1px solid #e3ebf3;border-radius:18px;padding:24px;background:#fff;box-shadow:0 12px 30px #1833530b}.demo-card-top,.demo-sample-foot{display:flex;justify-content:space-between;align-items:center;gap:8px;color:#71839a;font-size:.72rem;font-weight:750;letter-spacing:.06em}.demo-card-top .status-dot{margin-right:-2px}.demo-period{margin-left:auto;padding:6px 9px;border-radius:20px;background:#f1f5f9;letter-spacing:0;font-weight:600}.demo-metric{margin-top:30px;color:#153e75;font-size:4.3rem;font-weight:750;line-height:1}.demo-sample-card>p{color:#687b91}.demo-progress{height:9px;margin:22px 0;border-radius:9px;background:#eaf0f6;overflow:hidden}.demo-progress span{display:block;width:68%;height:100%;border-radius:inherit;background:linear-gradient(90deg,#2e7eaf,#59b9a1)}.demo-sample-foot{padding-top:15px;border-top:1px solid #edf1f5;letter-spacing:0;font-weight:600}.demo-sample-foot span:last-child{color:#24836d}@media(max-width:760px){.portal-demo{margin:8px auto}.demo-preview{grid-template-columns:1fr;gap:24px}.demo-intro{margin-bottom:20px}}
</style>
<?php require __DIR__ . '/includes/footer.php'; ?>
