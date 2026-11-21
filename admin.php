<?php
require __DIR__ . '/db.php';
session_start();
if (isset($_POST['pw']) && hash_equals($cfg['admin_password'], $_POST['pw'])) $_SESSION['ok'] = 1;
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if (empty($_SESSION['ok'])) { ?>
<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin</title>
<form method="post" style="max-width:320px;margin:80px auto;font-family:system-ui"><h3>Admin</h3>
<input type="password" name="pw" placeholder="Password" style="width:100%;padding:10px"><button style="margin-top:10px;padding:10px">Masuk</button></form>
<?php exit; }
if (isset($_POST['del'])) {
    $id = $_POST['del'];
    store_tx(function (&$rows) use ($id) { $rows = array_filter($rows, fn($r) => $r['id'] !== $id); });
    header('Location: admin.php'); exit;
}
$rows = store_all();
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=peserta.csv');
    $o = fopen('php://output', 'w'); fputcsv($o, ['Nama','WhatsApp','Sesi','Status','Usia','Daftar']);
    foreach ($rows as $r) fputcsv($o, [$r['name'],$r['whatsapp'],$cfg['sessions'][$r['session']] ?? $r['session'],$r['status'],$r['age'],$r['created_at']]);
    exit;
}
$counts = counts_of($rows);
?><!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Peserta</title>
<style>body{font-family:system-ui;max-width:900px;margin:20px auto;padding:0 16px}table{width:100%;border-collapse:collapse;margin-bottom:24px}td,th{border-bottom:1px solid #ddd;padding:8px;text-align:left}</style>
<h2>Peserta <?= h($cfg['title']) ?></h2><p><a href="?csv=1">Download CSV</a> · <a href="?logout=1">Keluar</a> · <a href="index.php">Form</a></p>
<?php foreach ($cfg['sessions'] as $k => $label): ?>
<h3><?= h($label) ?> — <?= $counts[$k] ?? 0 ?>/<?= $cfg['capacity'] ?></h3>
<table><tr><th>#</th><th>Nama</th><th>WhatsApp</th><th>Usia</th><th>Status</th><th></th></tr>
<?php $i = 0; foreach ($rows as $r): if ($r['session'] !== $k) continue; ?>
<tr><td><?= ++$i ?></td><td><?= h($r['name']) ?></td><td><a href="https://wa.me/<?= h(preg_replace('/^0/', '62', ltrim($r['whatsapp'], '+'))) ?>"><?= h($r['whatsapp']) ?></a></td><td><?= h($r['age']) ?></td><td><?= h($r['status']) ?></td>
<td><form method="post" onsubmit="return confirm('Hapus?')"><button name="del" value="<?= h($r['id']) ?>">Hapus</button></form></td></tr>
<?php endforeach; ?></table>
<?php endforeach; ?>
