<?php
require_once 'includes/bootstrap.php';

class LinkChecker {
    private $startUrl;
    private $crawled = [];
    private $brokenLinks = [];

    public function __construct($startUrl) {
        $this->startUrl = $startUrl;
    }

    public function run() {
        echo "<h1>Link Checker Report</h1>";
        echo "<p>Starting crawl from: {$this->startUrl}</p>";
        $this->crawl($this->startUrl);
        $this->displayReport();
    }

    private function crawl($url) {
        if (isset($this->crawled[$url])) {
            return;
        }

        echo "<p>Crawling: $url</p>";
        flush();

        $this->crawled[$url] = true;
        $html = @file_get_contents($url);

        if ($html === false) {
            $this->brokenLinks[$url] = 'Could not fetch';
            return;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (empty($href) || $href[0] === '#') {
                continue;
            }

            $nextUrl = $this->resolveUrl($href, $url);

            if ($this->isInternal($nextUrl)) {
                $this->crawl($nextUrl);
            } else {
                $this->checkExternalLink($nextUrl);
            }
        }
    }

    private function resolveUrl($href, $base) {
        if (parse_url($href, PHP_URL_SCHEME) !== null) {
            return $href;
        }

        $baseInfo = parse_url($base);
        $baseUrl = $baseInfo['scheme'] . '://' . $baseInfo['host'] . (isset($baseInfo['port']) ? ':' . $baseInfo['port'] : '');

        if (substr($href, 0, 1) === '/') {
            return $baseUrl . $href;
        }

        $path = dirname($baseInfo['path'] ?? '/');
        if ($path === '.' || $path === DIRECTORY_SEPARATOR) {
            $path = '/';
        }

        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        return $baseUrl . $path . $href;
    }

    private function isInternal($url) {
        return strpos($url, $this->startUrl) === 0;
    }

    private function checkExternalLink($url) {
        if (isset($this->crawled[$url])) {
            return;
        }
        $this->crawled[$url] = true;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOBODY, true); // We only need the headers
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            $this->brokenLinks[$url] = $httpCode;
        }
    }

    private function displayReport() {
        echo "<h2>Broken Links Report</h2>";
        if (empty($this->brokenLinks)) {
            echo "<p style='color: green;'>No broken links found!</p>";
        } else {
            echo "<ul>";
            foreach ($this->brokenLinks as $link => $status) {
                echo "<li><strong style='color: red;'>$status</strong> - $link</li>";
            }
            echo "</ul>";
        }
    }
}

$checker = new LinkChecker(SITE_URL);
$checker->run();
?>
