<?php
/**
 * TSU Student ID Generator – Remote Deploy Script
 * Place this file in the web root of sig.tsuniversity.ng
 * Called by deploy.bat via curl after every git push.
 *
 * Security: protected by a secret key in the query string.
 */

define('DEPLOY_KEY',    'DEPLOY_SIG_2026');
define('REPO_DIR',      '/home/tsuniversity/repositories/TSU-Student-ID-Generator');
define('WEB_ROOT',      '/home/tsuniversity/public_html/sig');
define('CONFIG_BACKUP', WEB_ROOT . '/config.php');   // never overwritten
define('GIT_BIN',       '/usr/bin/git');
define('LOG_FILE',      __DIR__ . '/deploy.log');

// ── Auth ──────────────────────────────────────────────────────────────────────
if (($_GET['key'] ?? '') !== DEPLOY_KEY) {
    http_response_code(403);
    die('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

function run(string $cmd): string {
    $output = [];
    $code   = 0;
    exec($cmd . ' 2>&1', $output, $code);
    $out = implode("\n", $output);
    echo $out . "\n";
    return $out;
}

function log_msg(string $msg): void {
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

echo "=== TSU Student ID Generator Deploy ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// ── Step 1: Pull latest code into repo dir ────────────────────────────────────
echo "--- Step 1: git pull ---\n";
$pull = run(GIT_BIN . ' -C ' . escapeshellarg(REPO_DIR) . ' pull origin main');
log_msg("git pull: " . trim($pull));

// ── Step 2: Sync repo files to web root (exclude config.php) ─────────────────
echo "\n--- Step 2: rsync to web root ---\n";
$rsync = run(
    'rsync -a --delete'
    . ' --exclude=config.php'          // keep live config intact
    . ' --exclude=git_pull.php'        // keep this deploy script
    . ' --exclude=deploy.log'          // keep log
    . ' --exclude=logs/'               // keep log directory
    . ' --exclude=typescript-version/' // skip TS source
    . ' --exclude=.git/'
    . ' ' . escapeshellarg(REPO_DIR . '/')
    . ' ' . escapeshellarg(WEB_ROOT . '/')
);
log_msg("rsync: " . trim($rsync));

// ── Step 2b: First-time setup – copy production config if config.php missing ──
$liveConfig = WEB_ROOT . '/config.php';
$prodConfig = WEB_ROOT . '/config.production.php';
if (!file_exists($liveConfig) && file_exists($prodConfig)) {
    copy($prodConfig, $liveConfig);
    echo "First-time setup: config.production.php copied to config.php\n";
    log_msg("First-time setup: config.php created from config.production.php");
}

// ── Step 3: Fix permissions ───────────────────────────────────────────────────
echo "\n--- Step 3: permissions ---\n";
run('find ' . escapeshellarg(WEB_ROOT) . ' -type f -exec chmod 644 {} \;');
run('find ' . escapeshellarg(WEB_ROOT) . ' -type d -exec chmod 755 {} \;');
echo "Permissions set.\n";

echo "\n=== Deploy complete ===\n";
log_msg("Deploy complete.");
