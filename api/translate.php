<?php
/**
 * SpaCart Translation Proxy
 * Uses Google Translate gtx API with DB caching
 * Endpoint: /custom/spacart/api/translate.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']);
    exit;
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['texts']) || empty($input['target'])) {
    echo json_encode(['error' => 'Missing texts[] or target parameter']);
    exit;
}

$texts = array_slice($input['texts'], 0, 50); // Max 50 texts per request
$source = isset($input['source']) ? substr(preg_replace('/[^a-z]/', '', strtolower($input['source'])), 0, 5) : 'fr';
$target = substr(preg_replace('/[^a-z]/', '', strtolower($input['target'])), 0, 5);

if ($source === $target) {
    echo json_encode(['translations' => $texts]);
    exit;
}

// Database connection
$db_config = [];
$config_file = dirname(__DIR__) . '/config.php';
if (file_exists($config_file)) {
    include $config_file;
}

// Try SpaCart's own DB connection
$boot_file = dirname(__DIR__) . '/includes/boot_db.php';
$mysqli = null;

// Direct DB connection using SpaCart config
$sc_config_file = dirname(__DIR__) . '/includes/config.php';
if (file_exists($sc_config_file)) {
    $sc_config = parse_ini_file($sc_config_file, true);
    if ($sc_config && isset($sc_config['mysql'])) {
        $mysqli = new mysqli(
            $sc_config['mysql']['host'] ?? 'localhost',
            $sc_config['mysql']['user'] ?? '',
            $sc_config['mysql']['pass'] ?? '',
            $sc_config['mysql']['db'] ?? ''
        );
    }
}

// Fallback: try reading from SpaCart's config table approach
if (!$mysqli || $mysqli->connect_error) {
    // Hardcoded fallback for this installation
    $mysqli = new mysqli('localhost', 'spacart_user', 'SpAcArT2026xCoex', 'erp_main');
}

if ($mysqli->connect_error) {
    // No DB = no cache, translate without caching
    $mysqli = null;
}

if ($mysqli) {
    $mysqli->set_charset('utf8mb4');
}

$translations = [];
$to_translate = []; // Indexes that need translation

// Check cache first
foreach ($texts as $i => $text) {
    $text = trim($text);
    if (empty($text) || mb_strlen($text) > 500) {
        $translations[$i] = $text; // Skip empty or too long
        continue;
    }
    
    $cached = null;
    if ($mysqli) {
        $escaped = $mysqli->real_escape_string($text);
        $result = $mysqli->query("SELECT translated_text FROM spacart_translation_cache WHERE source_lang='$source' AND target_lang='$target' AND source_text='$escaped' LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $cached = $row['translated_text'];
        }
    }
    
    if ($cached !== null) {
        $translations[$i] = $cached;
    } else {
        $to_translate[$i] = $text;
    }
}

// Translate uncached texts via Google Translate gtx API
foreach ($to_translate as $i => $text) {
    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx'
         . '&sl=' . urlencode($source)
         . '&tl=' . urlencode($target)
         . '&dt=t'
         . '&q=' . urlencode($text);
    
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => "User-Agent: Mozilla/5.0\r\n"
        ]
    ]);
    
    $response = @file_get_contents($url, false, $ctx);
    $translated = $text; // Fallback to original
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data[0]) && is_array($data[0])) {
            $parts = [];
            foreach ($data[0] as $part) {
                if (isset($part[0])) {
                    $parts[] = $part[0];
                }
            }
            if (!empty($parts)) {
                $translated = implode('', $parts);
            }
        }
    }
    
    $translations[$i] = $translated;
    
    // Cache the translation
    if ($mysqli && $translated !== $text) {
        $escaped_src = $mysqli->real_escape_string($text);
        $escaped_tgt = $mysqli->real_escape_string($translated);
        $mysqli->query("INSERT IGNORE INTO spacart_translation_cache (source_lang, target_lang, source_text, translated_text) VALUES ('$source', '$target', '$escaped_src', '$escaped_tgt')");
    }
    
    // Small delay to avoid rate limiting
    usleep(50000); // 50ms
}

if ($mysqli) {
    $mysqli->close();
}

// Return translations in same order as input
$result = [];
for ($i = 0; $i < count($texts); $i++) {
    $result[] = isset($translations[$i]) ? $translations[$i] : $texts[$i];
}

echo json_encode(['translations' => $result], JSON_UNESCAPED_UNICODE);
