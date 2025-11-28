<?php
/**
 * scan_all_in_one.php
 * Comprehensive static scanner for PHP/HTML/JS/CSS projects.
 *
 * Drop this file in your project root and open it in a browser.
 *
 * Limits:
 * - Static analysis only (no runtime/Playwright testing).
 * - Will attempt to run optional tools (eslint) if present.
 *
 * Config: adjust below to suit your environment.
 */

set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =======================
   CONFIG
   ======================= */
$ROOT = __DIR__;                          // project root (leave as-is)
$LOG_PATHS = [                            // server log files to analyze (set absolute paths if needed)
    '/var/log/apache2/error.log',
    '/var/log/apache2/access.log',
    'C:\xamppp\apache\logs\access.log',
    'C:\xamppp\apache\logs\error.log'
];

$MAX_IMAGE_WARN_BYTES = 500000;          // image size threshold for warning (500KB)
$MAX_JS_WARN_BYTES = 800000;            // js size threshold
$MAX_CSS_WARN_BYTES = 500000;            // css size threshold
$CHECK_ESLINT = true;                    // if true, script will try to run `npx eslint` if available
$ESLINT_PATTERNS = ['**/*.js','**/*.mjs','**/*.cjs']; // patterns for eslint (if used)
$ALLOWED_EXTS = ['php','html','htm','js','mjs','cjs','css','scss','png','jpg','jpeg','gif','svg','webp','json','xml','txt','log'];

/* =======================
   UTILITIES
   ======================= */

function human_filesize($bytes, $decimals = 2) {
    $sz = ['B','KB','MB','GB','TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $sz[$factor]);
}

function get_all_files($dir, $allowed_exts = []) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $ext = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));
        if (!empty($allowed_exts) && !in_array($ext, $allowed_exts)) continue;
        $files[] = $file->getPathname();
    }
    return $files;
}

function read_file_safe($path) {
    if (!is_readable($path)) return false;
    return @file_get_contents($path);
}

/* =======================
   COLLECT FILES
   ======================= */
$allFiles = get_all_files($ROOT, $ALLOWED_EXTS);

/* quick maps */
$byExt = [];
foreach ($allFiles as $f) {
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $byExt[$ext][] = $f;
}

/* results */
$errors = [];
$warnings = [];
$info = [];

/* =======================
   PHP SYNTAX CHECK
   ======================= */
if (!empty($byExt['php'])) {
    foreach ($byExt['php'] as $phpFile) {
        // php -l "file"
        $escaped = escapeshellarg($phpFile);
        $out = shell_exec("php -l $escaped 2>&1");
        if ($out === null) {
            $warnings[] = "Could not run php -l for syntax check on $phpFile (shell_exec returned null)";
        } else {
            if (stripos($out, 'No syntax errors detected') === false) {
                $errors[] = "PHP Syntax problem → $phpFile :: " . trim($out);
            } else {
                $info[] = "PHP OK → $phpFile";
            }
        }
    }
}

/* =======================
   SCAN HTML/PHP FILES FOR REFERENCES
   ======================= */

$ref_css = []; $ref_js = []; $ref_img = []; $ref_links = []; $ref_anchors = [];
$htmlSources = array_merge($byExt['html'] ?? [], $byExt['htm'] ?? [], $byExt['php'] ?? []);
foreach ($htmlSources as $file) {
    $content = read_file_safe($file);
    if ($content === false) {
        $errors[] = "Unreadable file (permissions?) → $file";
        continue;
    }

    // <link href=... rel=stylesheet
    preg_match_all('/<link[^>]+href=["\']([^"\']+)["\']/i', $content, $m);
    foreach ($m[1] as $href) {
        $ref_css[] = ['ref'=>$href, 'file'=>$file];
    }

    // <script src=...>
    preg_match_all('/<script[^>]+src=["\']([^"\']+)["\']/i', $content, $m2);
    foreach ($m2[1] as $src) {
        $ref_js[] = ['ref'=>$src, 'file'=>$file];
    }

    // <img src=...>
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m3);
    foreach ($m3[1] as $src) {
        $ref_img[] = ['ref'=>$src, 'file'=>$file];
    }

    // <a href=...>
    preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $content, $m4);
    foreach ($m4[1] as $href) {
        $ref_links[] = ['ref'=>$href, 'file'=>$file];
    }

    // anchors only (for internal # anchors)
    preg_match_all('/id=["\']([^"\']+)["\']/i', $content, $m5);
    foreach ($m5[1] as $id) {
        $ref_anchors[] = ['id'=>$id, 'file'=>$file];
    }

    // missing title, meta, viewport
    if (!preg_match('/<title>.*<\/title>/is', $content)) {
        $warnings[] = "Missing <title> in $file";
    }
    if (!preg_match('/<meta[^>]+name=["\']description["\']/i', $content)) {
        $warnings[] = "Missing meta description in $file";
    }
    if (!preg_match('/<meta[^>]+charset=["\']?utf-8/i', $content)) {
        $warnings[] = "Missing charset meta tag (utf-8) in $file";
    }
    if (!preg_match('/<meta[^>]+name=["\']viewport["\']/i', $content)) {
        $warnings[] = "Missing viewport meta tag in $file";
    }

    // OG tags check
    $ogNeed = ['og:title','og:description','og:image','og:url'];
    foreach ($ogNeed as $og) {
        if (!preg_match('/property=["\']' . preg_quote($og, '/') . '["\']/i', $content)) {
            $warnings[] = "Missing OpenGraph ($og) in $file";
        }
    }

    // missing favicon
    if (!preg_match('/<link[^>]+rel=["\']icon|shortcut icon/i', $content)) {
        $warnings[] = "Missing favicon link in $file";
    }

    // missing alt attributes on images
    if (preg_match_all('/<img[^>]+>/i', $content, $imgTags)) {
        foreach ($imgTags[0] as $imgTag) {
            if (!preg_match('/alt=["\'].*?["\']/i', $imgTag)) {
                $warnings[] = "Image tag missing alt attribute in $file: " . substr(strip_tags($imgTag),0,80);
            }
        }
    }
}

/* =======================
   CHECK REFS: resolve relative paths and check file existence
   ======================= */

function resolve_local_path($ref, $sourceFile, $root) {
    // ignore absolute URLs (http, https, //cdn)
    $refTrim = trim($ref);
    if (preg_match('#^(https?:)?//#i', $refTrim) || preg_match('#^https?://#i',$refTrim)) return null;
    if (strpos($refTrim, 'data:') === 0) return null; // base64 inline
    $refTrim = preg_replace('/^\//','', $refTrim); // remove leading slash — treat as relative to root
    // join with root
    $candidate = realpath($root . '/' . $refTrim);
    if ($candidate !== false) return $candidate;
    // try relative to source directory
    $candidate2 = realpath(dirname($sourceFile) . '/' . $refTrim);
    if ($candidate2 !== false) return $candidate2;
    // maybe query string present
    $refNoQuery = preg_replace('/\?.*$/','',$refTrim);
    $candidate3 = realpath($root . '/' . $refNoQuery);
    if ($candidate3 !== false) return $candidate3;
    $candidate4 = realpath(dirname($sourceFile) . '/' . $refNoQuery);
    if ($candidate4 !== false) return $candidate4;
    return null;
}

// CSS
$seenCss = [];
foreach ($ref_css as $r) {
    if (empty($r['ref'])) continue;
    $path = resolve_local_path($r['ref'], $r['file'], $ROOT);
    if ($path === null) {
        // might be external or missing
        if (preg_match('#^(https?:)?//#i', $r['ref'])) {
            $info[] = "External CSS: {$r['ref']} referenced in {$r['file']}";
        } else {
            $errors[] = "Missing CSS file: {$r['ref']} referenced in {$r['file']}";
        }
    } else {
        if (!file_exists($path)) {
            $errors[] = "Missing CSS file path resolved but not found: {$r['ref']} referenced in {$r['file']}";
        } else {
            $size = filesize($path);
            if ($size > $MAX_CSS_WARN_BYTES) $warnings[] = "Large CSS file ({human}): {$r['ref']} ({$size} bytes) referenced in {$r['file']}";
            $seenCss[$path][] = $r['file'];
        }
    }
}

// JS
$seenJs = [];
foreach ($ref_js as $r) {
    if (empty($r['ref'])) continue;
    $path = resolve_local_path($r['ref'], $r['file'], $ROOT);
    if ($path === null) {
        if (preg_match('#^(https?:)?//#i', $r['ref'])) {
            $info[] = "External JS: {$r['ref']} referenced in {$r['file']}";
        } else {
            $errors[] = "Missing JS file: {$r['ref']} referenced in {$r['file']}";
        }
    } else {
        if (!file_exists($path)) {
            $errors[] = "Missing JS file path resolved but not found: {$r['ref']} referenced in {$r['file']}";
        } else {
            $size = filesize($path);
            if ($size > $MAX_JS_WARN_BYTES) $warnings[] = "Large JS file ({$r['ref']}) referenced in {$r['file']} (size: " . human_filesize($size) . ")";
            $seenJs[$path][] = $r['file'];
        }
    }
}

// Images
$seenImg = [];
foreach ($ref_img as $r) {
    if (empty($r['ref'])) continue;
    $path = resolve_local_path($r['ref'], $r['file'], $ROOT);
    if ($path === null) {
        if (strpos($r['ref'], 'data:') === 0) {
            $info[] = "Inline base64 image referenced in {$r['file']}";
            continue;
        }
        if (preg_match('#^(https?:)?//#i', $r['ref'])) {
            $info[] = "External image: {$r['ref']} referenced in {$r['file']}";
        } else {
            $errors[] = "Missing image: {$r['ref']} referenced in {$r['file']}";
        }
    } else {
        if (!file_exists($path)) {
            $errors[] = "Missing image (resolved) : {$r['ref']} referenced in {$r['file']}";
        } else {
            $size = filesize($path);
            if ($size > $MAX_IMAGE_WARN_BYTES) $warnings[] = "Large image: {$r['ref']} referenced in {$r['file']} (size: " . human_filesize($size) . ")";
            $seenImg[$path][] = $r['file'];
        }
    }
}

// Internal links
foreach ($ref_links as $r) {
    $href = $r['ref'];
    if (empty($href)) continue;
    if (strpos($href, '#') === 0) {
        // anchor on same page - check id exists?
        $anchorId = substr($href,1);
        $found = false;
        foreach ($ref_anchors as $a) {
            if ($a['id'] === $anchorId) { $found = true; break; }
        }
        if (!$found) $warnings[] = "Anchor link to #$anchorId possibly missing (referenced in {$r['file']})";
        continue;
    }
    if (preg_match('#^(https?:)?//#i', $href)) continue; // external
    if (strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0) continue;

    $path = resolve_local_path($href, $r['file'], $ROOT);
    if ($path === null || !file_exists($path)) {
        $errors[] = "Broken internal link: {$href} referenced in {$r['file']}";
    }
}

/* =======================
   INCLUDE / REQUIRE CHECKS FOR PHP
   ======================= */
if (!empty($byExt['php'])) {
    foreach ($byExt['php'] as $pfile) {
        $content = read_file_safe($pfile);
        if ($content === false) { $errors[] = "Unreadable PHP file: $pfile"; continue; }
        preg_match_all('/(include|require)(_once)?\s*\(?\s*[\'"]([^\'"]+)[\'"]/i', $content, $m);
        foreach ($m[3] as $incPath) {
            $resolved = resolve_local_path($incPath, $pfile, $ROOT);
            if ($resolved === null || !file_exists($resolved)) {
                $errors[] = "Missing include/require: {$incPath} referenced in {$pfile}";
            } else {
                $info[] = "Include OK: {$incPath} in {$pfile}";
            }
        }
    }
}

/* =======================
   CSS: parse selectors and find unused ones (static)
   ======================= */

function parse_css_selectors($cssContent) {
    // basic extraction: get selector groups before '{'
    $selectors = [];
    // remove comments
    $cssContent = preg_replace('#/\*.*?\*/#s','',$cssContent);
    // find selectors
    preg_match_all('/([^{]+)\s*\{/',$cssContent,$m);
    foreach ($m[1] as $selGroup) {
        // split by comma
        $parts = explode(',',$selGroup);
        foreach ($parts as $p) {
            $p = trim($p);
            // only care about simple selectors: .class, #id, tag.class
            if (preg_match('/(^|\s|>|\+|~)\.([A-Za-z0-9_-]+)/',$p,$mm)) {
                $selectors[] = '.' . $mm[2];
            } elseif (preg_match('/(^|\s|>|\+|~)#([A-Za-z0-9_-]+)/',$p,$mm2)) {
                $selectors[] = '#' . $mm2[2];
            } elseif (preg_match('/^([a-zA-Z0-9_-]+)(\b|$)/',$p,$mm3)) {
                $selectors[] = $mm3[1];
            }
        }
    }
    return array_unique($selectors);
}

// gather DOM text of all HTML/PHP to search for classes/ids/usages
$domText = '';
foreach ($htmlSources as $f) {
    $c = read_file_safe($f);
    if ($c !== false) $domText .= "\n" . strip_tags($c);
}

$unusedSelectors = [];
if (!empty($byExt['css'])) {
    foreach ($byExt['css'] as $cssFile) {
        $c = read_file_safe($cssFile);
        if ($c === false) { $warnings[] = "Cannot read CSS file: $cssFile"; continue; }
        $selectors = parse_css_selectors($c);
        foreach ($selectors as $sel) {
            $selPlain = ltrim($sel, '.#');
            // simple check: search in DOM text for occurrences of class or id
            if ($sel[0] === '.') {
                // class search - look for class="... classPlain ..." or .classPlain in code
                if (!preg_match('/class=["\'][^"\']*\b' . preg_quote($selPlain,'/') . '\b/i', $domText)) {
                    $unusedSelectors[] = ['selector'=>$sel, 'file'=>$cssFile];
                }
            } elseif ($sel[0] === '#') {
                if (!preg_match('/id=["\']' . preg_quote($selPlain,'/') . '["\']/i', $domText)) {
                    $unusedSelectors[] = ['selector'=>$sel, 'file'=>$cssFile];
                }
            } else {
                // tag selector - check for tag occurrence
                if (!preg_match('/\b' . preg_quote($selPlain,'/') . '\b/i', $domText)) {
                    $unusedSelectors[] = ['selector'=>$sel, 'file'=>$cssFile];
                }
            }
        }
    }
}

/* =======================
   JS static checks: find fetch/ajax and verify referenced files exist.
   Also optionally run eslint if available.
   ======================= */

$jsFetchIssues = [];
$jsFilesToLint = $byExt['js'] ?? [];
foreach ($jsFilesToLint as $jsf) {
    $c = read_file_safe($jsf);
    if ($c === false) { $warnings[] = "Unreadable JS file: $jsf"; continue; }
    // fetch('url' or fetch("url")
    preg_match_all('/fetch\(\s*[\'"]([^\'"]+)[\'"]/i',$c,$m);
    foreach ($m[1] as $url) {
        if (preg_match('#^(https?:)?//#i', $url)) { $info[] = "JS fetch to external URL {$url} in {$jsf}"; continue; }
        $resolved = resolve_local_path($url, $jsf, $ROOT);
        if ($resolved === null || !file_exists($resolved)) {
            $jsFetchIssues[] = "JS fetch/XHR references missing file: {$url} in {$jsf}";
        }
    }
    // XMLHttpRequest open("GET","url")
    preg_match_all('/open\([^,]+,\s*[\'"]([^\'"]+)[\'"]/i',$c,$m2);
    foreach ($m2[1] as $url2) {
        if (preg_match('#^(https?:)?//#i', $url2)) { $info[] = "XHR to external URL {$url2} in {$jsf}"; continue; }
        $resolved2 = resolve_local_path($url2, $jsf, $ROOT);
        if ($resolved2 === null || !file_exists($resolved2)) {
            $jsFetchIssues[] = "XHR references missing file: {$url2} in {$jsf}";
        }
    }
}

// attempt eslint if enabled and available
$eslintResults = [];
if ($CHECK_ESLINT && !empty($jsFilesToLint)) {
    // detect if npx eslint available
    $which = shell_exec('which npx 2>/dev/null') ?? shell_exec('which eslint 2>/dev/null');
    if ($which) {
        // build temp file list
        $tmpList = sys_get_temp_dir() . '/scan_js_files.txt';
        file_put_contents($tmpList, implode("\n", $jsFilesToLint));
        // try npx eslint --no-eslintrc --ext .js <files> (this may fail depending on env)
        $cmd = "npx eslint --no-eslintrc --max-warnings=1000 " . escapeshellarg(implode(' ', array_map('escapeshellarg',$jsFilesToLint))) . " 2>&1";
        $out = shell_exec($cmd);
        if ($out === null || $out === '') {
            // maybe eslint not installed
            $info[] = "ESLint attempt returned no output or not available.";
        } else {
            $eslintResults[] = $out;
        }
        @unlink($tmpList);
    } else {
        $info[] = "ESLint not detected on server; skipping deep JS linting. To enable, install node and eslint or run locally.";
    }
}

/* =======================
   FILE HEALTH: empty files, permission issues
   ======================= */
foreach ($allFiles as $f) {
    if (!is_readable($f)) $errors[] = "Unreadable file (permission issue?) → $f";
    if (filesize($f) === 0) $warnings[] = "Empty file (0 bytes) → $f";
}

/* =======================
   DETECT DUPLICATE IMPORTS (CSS/JS)
   ======================= */
foreach ([$seenCss, $seenJs] as $map) {
    foreach ($map as $path => $usages) {
        if (count($usages) > 1) {
            $warnings[] = "Duplicate import: $path included in multiple pages (" . count($usages) . " times)";
        }
    }
}

/* =======================
   SUSPICIOUS PHP CODE (simple heuristics)
   ======================= */
$suspiciousPatterns = [
    'eval\(' => 'eval() used',
    'base64_decode\(' => 'base64_decode() used (hidden payload risk)',
    'shell_exec\(' => 'shell_exec() used (security risk)',
    'exec\(' => 'exec() used (security risk)',
    'preg_replace\(.*/e' => 'preg_replace with /e modifier (deprecated / risky)',
];

foreach ($byExt['php'] ?? [] as $phpFile) {
    $c = read_file_safe($phpFile);
    if ($c === false) continue;
    foreach ($suspiciousPatterns as $pat => $msg) {
        if (preg_match('#' . $pat . '#i', $c)) {
            $warnings[] = "Suspicious PHP pattern: {$msg} in {$phpFile}";
        }
    }
}

/* =======================
   SERVER LOG ANALYSIS
   ======================= */
$logSummary = [];
foreach ($LOG_PATHS as $logPath) {
    if (!file_exists($logPath)) { $info[] = "Log path not found: $logPath"; continue; }
    $content = read_file_safe($logPath);
    if ($content === false) { $warnings[] = "Cannot read log file: $logPath"; continue; }
    // simple parse: count 404/500 and top requested missing paths
    preg_match_all('/\s404\s|\" 404 /i', $content, $m404);
    preg_match_all('/\s500\s|\" 500 /i', $content, $m500);
    // extract lines with 404
    preg_match_all('/\"[A-Z]+\s([^"]+)\sHTTP\/[0-9\.]+\"\s404/i', $content, $mPaths);
    $pathCounts = array_count_values($mPaths[1] ?? []);
    arsort($pathCounts);
    $logSummary[$logPath] = [
        '404_count' => count($m404[0]),
        '500_count' => count($m500[0]),
        'top_missing' => array_slice($pathCounts,0,10,true)
    ];
}

/* =======================
   OUTPUT REPORT (HTML or CLI)
   ======================= */

function render_list($arr) {
    $out = '';
    foreach ($arr as $a) $out .= "<li>" . htmlspecialchars($a) . "</li>\n";
    return $out;
}

if (php_sapi_name() === 'cli') {
    // CLI output - plain
    echo "=== SCAN ALL IN ONE REPORT ===\n\n";
    echo "Errors (" . count($errors) . "):\n";
    foreach ($errors as $e) echo " - $e\n";
    echo "\nWarnings (" . count($warnings) . "):\n";
    foreach ($warnings as $w) echo " - $w\n";
    echo "\nInfo (" . count($info) . "):\n";
    foreach ($info as $i) echo " - $i\n";
    if (!empty($eslintResults)) {
        echo "\nESLint Results:\n";
        foreach ($eslintResults as $res) echo $res . "\n";
    }
    if (!empty($unusedSelectors)) {
        echo "\nUnused CSS selectors (sample " . min(50,count($unusedSelectors)) . "):\n";
        foreach (array_slice($unusedSelectors,0,50) as $us) {
            echo " - {$us['selector']} (in {$us['file']})\n";
        }
    }
    if (!empty($jsFetchIssues)) {
        echo "\nJS fetch/XHR issues:\n";
        foreach ($jsFetchIssues as $j) echo " - $j\n";
    }
    if (!empty($logSummary)) {
        echo "\nLog summary:\n";
        foreach ($logSummary as $lp => $data) {
            echo "Log: $lp\n  404s: {$data['404_count']}, 500s: {$data['500_count']}\n";
            if (!empty($data['top_missing'])) {
                echo "  Top missing: \n";
                foreach ($data['top_missing'] as $path => $cnt) echo "    $path ($cnt)\n";
            }
        }
    }
    echo "\nFiles scanned: " . count($allFiles) . "\n";
    exit;
}

// HTML output
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>All-In-One Static Site Scan Report</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body{font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial;margin:20px;background:#f7fafc;color:#0f172a}
        .card{background:#fff;border-radius:8px;padding:16px;margin-bottom:16px;box-shadow:0 6px 18px rgba(2,6,23,.08)}
        h1{font-size:20px;margin-top:0}
        .counts{display:flex;gap:12px;margin-bottom:12px}
        .badge{padding:8px 12px;border-radius:6px;background:#eef2ff;color:#312e81}
        ul{margin:8px 0 0 18px}
        code{background:#0f172a;color:#fff;padding:2px 6px;border-radius:4px;font-size:13px}
        .small{font-size:13px;color:#475569}
    </style>
</head>
<body>
    <div class="card">
        <h1>All-In-One Static Site Scan Report</h1>
        <div class="small">Root: <code><?php echo htmlspecialchars($ROOT) ?></code></div>
        <div class="counts">
            <div class="badge">Files scanned: <?php echo count($allFiles) ?></div>
            <div class="badge" style="background:#fee2e2;color:#7f1d1d">Errors: <?php echo count($errors) ?></div>
            <div class="badge" style="background:#fff7ed;color:#92400e">Warnings: <?php echo count($warnings) ?></div>
            <div class="badge" style="background:#ecfccb;color:#365314">Info: <?php echo count($info) ?></div>
        </div>
    </div>

    <div class="card">
        <h2 style="color:#7f1d1d">Errors (<?php echo count($errors) ?>)</h2>
        <?php if (empty($errors)): ?>
            <p>No errors found.</p>
        <?php else: ?>
            <ul><?php echo render_list($errors) ?></ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="color:#92400e">Warnings (<?php echo count($warnings) ?>)</h2>
        <?php if (empty($warnings)): ?>
            <p>No warnings found.</p>
        <?php else: ?>
            <ul><?php echo render_list($warnings) ?></ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="color:#365314">Info (<?php echo count($info) ?></h2>
        <?php if (empty($info)): ?>
            <p>No additional info.</p>
        <?php else: ?>
            <ul><?php echo render_list($info) ?></ul>
        <?php endif; ?>
    </div>

    <?php if (!empty($eslintResults)): ?>
    <div class="card">
        <h2>ESLint Output</h2>
        <pre style="white-space:pre-wrap;"><?php echo htmlspecialchars(implode("\n\n",$eslintResults)) ?></pre>
    </div>
    <?php endif; ?>

    <?php if (!empty($unusedSelectors)): ?>
    <div class="card">
        <h2>Unused CSS Selectors (sample <?php echo min(200,count($unusedSelectors)) ?>)</h2>
        <ul>
            <?php foreach (array_slice($unusedSelectors,0,200) as $us): ?>
                <li><code><?php echo htmlspecialchars($us['selector']) ?></code> — <?php echo htmlspecialchars($us['file']) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="small">Static detection: may include selectors used dynamically by JS or server-side templates.</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($jsFetchIssues)): ?>
    <div class="card">
        <h2>JS fetch/XHR Issues</h2>
        <ul><?php echo render_list($jsFetchIssues) ?></ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($logSummary)): ?>
    <div class="card">
        <h2>Server Log Summary</h2>
        <?php foreach ($logSummary as $lp => $data): ?>
            <h3><?php echo htmlspecialchars($lp) ?></h3>
            <ul>
                <li>404 count: <?php echo (int)$data['404_count'] ?></li>
                <li>500 count: <?php echo (int)$data['500_count'] ?></li>
            </ul>
            <?php if (!empty($data['top_missing'])): ?>
                <strong>Top missing paths:</strong>
                <ul>
                    <?php foreach ($data['top_missing'] as $p => $c): ?>
                        <li><?php echo htmlspecialchars($p) ?> — <?php echo (int)$c ?> hits</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card small">
        <strong>Notes & next steps</strong>
        <ul>
            <li>This is a static analyzer — for interactive flow tests (add-to-cart, login, AJAX runtime errors) use Playwright/Puppeteer tests that open pages and exercise UI.</li>
            <li>ESLint is optional — install Node + eslint to enable deep JS linting. The script tries to run npx eslint if available.</li>
            <li>Unused CSS detection is static and can produce false positives for classes added by JS at runtime.</li>
            <li>For security, delete this scanner from your public root after use or protect it with basic auth.</li>
        </ul>
    </div>
</body>
</html>
