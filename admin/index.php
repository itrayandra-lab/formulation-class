<?php
require __DIR__ . '/../db.php';
session_start();
if (isset($_POST['pw']) && hash_equals($cfg['admin_password'], $_POST['pw'])) { $_SESSION['ok'] = 1; header('Location: ./'); exit; }
if (isset($_GET['logout'])) { session_destroy(); header('Location: ./'); exit; }
$loginFailed = isset($_POST['pw']);
$authed = !empty($_SESSION['ok']);

if ($authed && isset($_POST['del'])) {
    $id = $_POST['del'];
    store_tx(function (&$rows) use ($id) { $rows = array_filter($rows, fn($r) => $r['id'] !== $id); });
    header('Location: ./'); exit;
}
$rows = $authed ? store_all() : [];
if ($authed && isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=peserta.csv');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Nama','WhatsApp','Sesi','Status','Usia','Waktu Daftar']);
    foreach ($rows as $r) fputcsv($o, [$r['name'],$r['whatsapp'],$cfg['sessions'][$r['session']] ?? $r['session'],$r['status'],$r['age'],$r['created_at']]);
    exit;
}
$counts = counts_of($rows);
$total = count($rows);
$wa = fn($n) => preg_replace('/^0/', '62', ltrim($n, '+'));
?><!doctype html>
<html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>Admin — <?= h($cfg['title']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--teal:#0b6e7a;--teal-d:#064a54;--sand:#f6efe6;--ink:#1c2b2e;--mut:#6b7b7e;--line:#e4eae8;--err:#b3261e}
*{box-sizing:border-box}
body{margin:0;background:var(--sand);color:var(--ink);font:14px/1.5 Poppins,system-ui,sans-serif}
.top{background:linear-gradient(160deg,var(--teal-d),var(--teal));color:#fff;padding:18px 20px}
.top .in{max-width:1000px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.top h1{margin:0;font-size:18px}.top small{opacity:.85}
.top a{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.5);padding:6px 14px;border-radius:99px;font-size:13px;margin-left:6px}
.top a:hover{background:rgba(255,255,255,.15)}
.wrap{max-width:1000px;margin:0 auto;padding:20px 16px 48px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px}
.stat{background:#fff;border-radius:16px;padding:16px;box-shadow:0 4px 14px rgba(6,74,84,.08)}
.stat small{color:var(--mut)}.stat b{display:block;font-size:26px;line-height:1.2}
.bar{height:6px;background:#e5f2f3;border-radius:9px;margin-top:8px;overflow:hidden}.bar i{display:block;height:100%;background:var(--teal)}
.bar.full i{background:var(--err)}
.card{background:#fff;border-radius:16px;box-shadow:0 4px 14px rgba(6,74,84,.08);margin-bottom:18px;overflow:hidden}
.card h2{margin:0;padding:14px 18px;font-size:16px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between}
.card h2 span{color:var(--mut);font-weight:500}
.tw{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:560px}
th,td{padding:10px 18px;text-align:left;border-bottom:1px solid var(--line);white-space:nowrap}
th{font-size:12px;color:var(--mut);font-weight:600;text-transform:uppercase;letter-spacing:.04em}
tr:last-child td{border-bottom:0}.empty{padding:20px 18px;color:var(--mut)}
a.wa{color:var(--teal);text-decoration:none;font-weight:500}
.pill{padding:2px 10px;border-radius:99px;font-size:12px;background:#e5f2f3;color:var(--teal);font-weight:600}
.pill.p{background:#fff3dc;color:#8a5a00}
.del{border:0;background:none;color:var(--err);cursor:pointer;font:inherit}
#q{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;font:inherit;margin-bottom:16px;background:#fff}
.login{max-width:340px;margin:12vh auto;background:#fff;border-radius:20px;padding:26px;box-shadow:0 10px 30px rgba(6,74,84,.12)}
.login input{width:100%;padding:12px;border:1.5px solid var(--line);border-radius:12px;font:inherit}
.login button{width:100%;margin-top:12px;padding:12px;border:0;border-radius:12px;background:var(--teal);color:#fff;font:600 15px Poppins,sans-serif;cursor:pointer}
</style></head><body>
<?php if (!$authed): ?>
<form class="login" method="post">
  <h2 style="margin-top:0">Admin</h2>
  <?php if ($loginFailed): ?><p style="color:var(--err);margin:0 0 10px">Password salah.</p><?php endif; ?>
  <input type="password" name="pw" placeholder="Password" autofocus>
  <button>Masuk</button>
</form>
<?php else: ?>
<div class="top"><div class="in">
  <div><h1>Peserta <?= h($cfg['title']) ?></h1><small><?= h($cfg['date']) ?> · <?= h($cfg['location']) ?></small></div>
  <div><a href="?csv=1">⬇ CSV</a><a href="../" target="_blank">Lihat form</a><a href="?logout=1">Keluar</a></div>
</div></div>
<div class="wrap">
  <div class="stats">
    <div class="stat"><small>Total pendaftar</small><b><?= $total ?></b></div>
    <?php foreach ($cfg['sessions'] as $k => $label): $c = $counts[$k] ?? 0; $full = $c >= $cfg['capacity']; ?>
    <div class="stat"><small><?= h($label) ?></small><b><?= $c ?> / <?= (int)$cfg['capacity'] ?></b>
      <div class="bar <?= $full ? 'full' : '' ?>"><i style="width:<?= min(100, $c / $cfg['capacity'] * 100) ?>%"></i></div>
      <?php if ($full): ?><small style="color:var(--err)">PENUH</small><?php endif; ?></div>
    <?php endforeach; ?>
  </div>
  <input id="q" type="search" placeholder="Cari nama atau nomor WhatsApp…">
  <?php foreach ($cfg['sessions'] as $k => $label): ?>
  <section class="card"><h2><?= h($label) ?> <span><?= $counts[$k] ?? 0 ?>/<?= (int)$cfg['capacity'] ?></span></h2>
    <div class="tw"><table>
      <tr><th>#</th><th>Nama</th><th>WhatsApp</th><th>Usia</th><th>Status</th><th>Daftar</th><th></th></tr>
      <?php $i = 0; foreach ($rows as $r): if ($r['session'] !== $k) continue; ?>
      <tr class="r"><td><?= ++$i ?></td><td><?= h($r['name']) ?></td>
        <td><a class="wa" href="https://wa.me/<?= h($wa($r['whatsapp'])) ?>" target="_blank"><?= h($r['whatsapp']) ?></a></td>
        <td><?= h($r['age']) ?></td>
        <td><span class="pill <?= $r['status'] === 'Pelajar' ? 'p' : '' ?>"><?= h($r['status']) ?></span></td>
        <td><?= h(date('d M, H:i', strtotime($r['created_at']))) ?></td>
        <td><form method="post" onsubmit="return confirm('Hapus <?= h(addslashes($r['name'])) ?>?')"><button class="del" name="del" value="<?= h($r['id']) ?>">Hapus</button></form></td></tr>
      <?php endforeach; if ($i === 0) echo '<tr><td colspan="7" class="empty">Belum ada pendaftar.</td></tr>'; ?>
    </table></div>
  </section>
  <?php endforeach; ?>
</div>
<script>
document.getElementById('q').addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('tr.r').forEach(tr => tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none');
});
</script>
<?php endif; ?>
</body></html>
