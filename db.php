<?php
// Penyimpanan JSON dengan file lock (aman untuk pendaftaran bersamaan)
$cfg = require __DIR__ . '/config.php';
define('STORE', __DIR__ . '/data/registrations.json');
if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0775, true);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Jalankan $fn(array &$rows) dalam lock eksklusif; perubahan $rows otomatis disimpan.
function store_tx(callable $fn) {
    $f = fopen(STORE, 'c+');
    flock($f, LOCK_EX);
    $rows = json_decode(stream_get_contents($f), true) ?: [];
    $res = $fn($rows);
    ftruncate($f, 0); rewind($f);
    fwrite($f, json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($f); flock($f, LOCK_UN); fclose($f);
    return $res;
}
function store_all(): array {
    return is_file(STORE) ? (json_decode(file_get_contents(STORE), true) ?: []) : [];
}
function counts_of(array $rows): array {
    $r = [];
    foreach ($rows as $x) $r[$x['session']] = ($r[$x['session']] ?? 0) + 1;
    return $r;
}
