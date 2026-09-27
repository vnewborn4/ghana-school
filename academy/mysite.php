<?php
require_once __DIR__ . '/../includes/learner_auth.php';
require_once __DIR__ . '/../includes/academy_nav.php';
require_once __DIR__ . '/../includes/student_sites.php';
require_once __DIR__ . '/../includes/audit.php';

learner_session_start();
$learner = require_learner();

/* Every learner gets a site row at onboarding; create one lazily for any
   account that predates this feature. */
$stmt = db()->prepare('SELECT * FROM student_sites WHERE learner_id=?');
$stmt->execute([$learner['id']]);
$site = $stmt->fetch();
if (!$site) {
    $ins = db()->prepare('INSERT INTO student_sites (learner_id, slug, title) VALUES (?,?,?)');
    $ins->execute([$learner['id'], $learner['username'], $learner['display_name'] . "'s page"]);
    site_seed($learner['username'], $learner['display_name']);
    $stmt->execute([$learner['id']]);
    $site = $stmt->fetch();
}
$slug = $site['slug'];
site_ensure_dirs($slug);

$flash = null; $flashKind = 'ok'; $flashRaw = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_learner_csrf();
    $action = (string)($_POST['action'] ?? 'save');

    if ($action === 'save') {
        $files = [
            'index.html' => (string)($_POST['html'] ?? ''),
            'style.css'  => (string)($_POST['css']  ?? ''),
            'script.js'  => (string)($_POST['js']   ?? ''),
        ];
        foreach ($files as $name => $contents) {
            $error = site_write($slug, $name, $contents);
            if ($error !== null) { $flashRaw = $error; $flashKind = 'bad'; break; }
        }
        if ($flashKind !== 'bad') {
            $flash = 'site.saved';
            // Editing again after a review request puts the page back in draft.
            if ($site['status'] === 'pending_review') {
                db()->prepare('UPDATE student_sites SET status=? WHERE id=?')->execute(['draft', $site['id']]);
            }
        }
    } elseif ($action === 'upload') {
        $storedName = null;
        $error = site_store_image($slug, $_FILES['picture'] ?? [], $storedName);
        if ($error !== null) { $flashRaw = $error; $flashKind = 'bad'; }
        else { $flashRaw = 'Saved as ' . $storedName . '. Add it with: <img src="' . htmlspecialchars($storedName) . '">'; }
    } elseif ($action === 'delete_file') {
        $target = (string)($_POST['file'] ?? '');
        $flashRaw = site_delete_file($slug, $target) ? 'Deleted ' . htmlspecialchars($target) . '.' : 'That file could not be deleted.';
        if (!str_starts_with($flashRaw, 'Deleted')) $flashKind = 'bad';
    } elseif ($action === 'request_review') {
        db()->prepare('UPDATE student_sites SET status=? WHERE id=?')->execute(['pending_review', $site['id']]);
        audit('site.review_requested', [
            'actor_learner_id' => (int)$learner['id'],
            'subject_type' => 'student_site', 'subject_id' => (int)$site['id'],
        ]);
        $flash = 'site.requested';
    }

    $stmt->execute([$learner['id']]);
    $site = $stmt->fetch();
}

$html = site_read($slug, 'draft', 'index.html') ?? '';
$css  = site_read($slug, 'draft', 'style.css')  ?? '';
$js   = site_read($slug, 'draft', 'script.js')  ?? '';
$extraFiles = array_diff_key(site_files($slug, 'draft'), array_flip(['index.html','style.css','script.js']));
$used = site_bytes_used($slug, 'draft');
$pct  = min(100, (int)round($used / SITE_MAX_BYTES * 100));

$statusKey = match ($site['status']) {
    'pending_review' => 'site.status.pending',
    'published'      => 'site.status.published',
    'unpublished'    => 'site.status.unpublished',
    'suspended'      => 'site.status.suspended',
    default          => 'site.status.draft',
};

$publicUrl  = app_url('students/' . $slug . '/');
$previewBase = app_url('students/preview/' . $slug . '/');
$previewUrl  = $previewBase . '?t=' . time();

$portalKind = 'student';
$portalName = t('app.academy');
$pageTitle  = t('site.title');
$navItems   = student_nav();
$offlineFor = $learner['username'];
$siteSlug   = $slug;
$signOut    = ['action' => app_url('academy/logout.php'), 'csrf' => learner_csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <h1><?= e('site.title') ?></h1>
    <p><?= e('site.intro') ?></p>
</div>

<?php if ($flash): ?><div class="notice notice-<?= htmlspecialchars($flashKind) ?>"><?= e($flash) ?></div><?php endif; ?>
<?php if ($flashRaw): ?><div class="notice notice-<?= htmlspecialchars($flashKind) ?>"><?= $flashRaw ?></div><?php endif; ?>

<div class="notice notice-warn">
    <strong><?= e('site.safety_title') ?>:</strong> <?= e('site.safety_reminder') ?>
</div>

<div class="academy-card">
    <div class="button-row-wrap">
        <span class="pill <?= $site['status'] === 'published' ? 'pill-live' : 'pill-off' ?>"><?= e($statusKey) ?></span>
        <?php if ($site['status'] === 'published'): ?>
            <span class="site-address"><?= htmlspecialchars($publicUrl) ?></span>
            <a class="button-big secondary" href="<?= htmlspecialchars($publicUrl) ?>" target="_blank" rel="noopener"><?= e('site.preview') ?></a>
        <?php endif; ?>
    </div>
    <?php if (!empty($site['review_note'])): ?>
        <p class="field-hint"><strong><?= e('asg.teacher_feedback') ?>:</strong> <?= htmlspecialchars($site['review_note']) ?></p>
    <?php endif; ?>
    <p class="field-hint"><?= e('site.space_used', ['used' => round($used / 1024) . ' KB', 'total' => round(SITE_MAX_BYTES / 1048576) . ' MB']) ?></p>
    <div class="quota-bar"><span style="width: <?= $pct ?>%"></span></div>
</div>

<form method="post" id="site-editor-form" data-offline-form="site" data-offline-label="Your page">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(learner_csrf_token()) ?>">
    <input type="hidden" name="action" value="save">

    <div class="editor-layout">
        <div>
            <div class="editor-tabs" role="tablist">
                <button class="editor-tab" type="button" role="tab" aria-selected="true"  aria-controls="pane-html" id="tab-html"><?= e('site.tab_html') ?></button>
                <button class="editor-tab" type="button" role="tab" aria-selected="false" aria-controls="pane-css"  id="tab-css"><?= e('site.tab_css') ?></button>
                <button class="editor-tab" type="button" role="tab" aria-selected="false" aria-controls="pane-js"   id="tab-js"><?= e('site.tab_js') ?></button>
            </div>

            <div class="editor-pane" id="pane-html" role="tabpanel" aria-labelledby="tab-html">
                <label class="sr-only" for="html"><?= e('site.tab_html') ?></label>
                <textarea id="html" name="html" spellcheck="false" wrap="off"><?= htmlspecialchars($html) ?></textarea>
            </div>
            <div class="editor-pane" id="pane-css" role="tabpanel" aria-labelledby="tab-css" hidden>
                <label class="sr-only" for="css"><?= e('site.tab_css') ?></label>
                <textarea id="css" name="css" spellcheck="false" wrap="off"><?= htmlspecialchars($css) ?></textarea>
            </div>
            <div class="editor-pane" id="pane-js" role="tabpanel" aria-labelledby="tab-js" hidden>
                <label class="sr-only" for="js"><?= e('site.tab_js') ?></label>
                <textarea id="js" name="js" spellcheck="false" wrap="off"><?= htmlspecialchars($js) ?></textarea>
            </div>

            <div class="button-row-wrap" style="margin-top:14px">
                <button class="button-big" type="submit"><?= e('site.save') ?></button>
            </div>
        </div>

        <div>
            <h2 style="font-family:Georgia,serif;color:var(--forest);margin:0 0 10px"><?= e('site.preview') ?></h2>
            <iframe class="preview-frame" id="site-preview" title="<?= e('site.preview') ?>"
                    data-base="<?= htmlspecialchars($previewBase) ?>"
                    src="<?= htmlspecialchars($previewUrl) ?>"></iframe>
            <div class="button-row-wrap" style="margin-top:10px">
                <button class="button-big secondary" type="button" id="refresh-preview"><?= e('site.refresh_preview') ?></button>
            </div>
        </div>
    </div>
</form>

<div class="academy-card" style="margin-top:20px">
    <h2><?= e('asg.upload') ?></h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(learner_csrf_token()) ?>">
        <input type="hidden" name="action" value="upload">
        <div class="field">
            <input type="file" name="picture" accept=".png,.jpg,.jpeg,.gif,.webp" required>
            <p class="field-hint">Pictures are saved as PNG. Location information from a camera is removed automatically.</p>
        </div>
        <button class="button-big secondary" type="submit"><?= e('common.save') ?></button>
    </form>

    <?php if ($extraFiles): ?>
        <div class="table-scroll" style="margin-top:16px">
        <table class="staff-table">
            <tbody>
            <?php foreach ($extraFiles as $name => $size): ?>
                <tr>
                    <td><code><?= htmlspecialchars($name) ?></code></td>
                    <td><?= round($size / 1024) ?> KB</td>
                    <td>
                        <form method="post" data-confirm="Delete this file?">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(learner_csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_file">
                            <input type="hidden" name="file" value="<?= htmlspecialchars($name) ?>">
                            <button type="submit" class="button-big secondary" style="padding:6px 14px;font-size:.85rem">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($site['status'] !== 'pending_review'): ?>
<div class="academy-card">
    <h2><?= e('site.request_review') ?></h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(learner_csrf_token()) ?>">
        <input type="hidden" name="action" value="request_review">
        <button class="button-big gold" type="submit"><?= e('site.request_review') ?></button>
    </form>
</div>
<?php endif; ?>

<script defer src="<?= asset_url('assets/js/academy.js') ?>"></script>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
