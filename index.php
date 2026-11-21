<?php
require __DIR__ . '/db.php';
$errors = []; $done = null; $old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $name = trim($_POST['name'] ?? '');
    $wa = preg_replace('/[^0-9+]/', '', $_POST['whatsapp'] ?? '');
    $session = (int)($_POST['session'] ?? 0);
    $status = $_POST['status'] ?? '';
    $age = (int)($_POST['age'] ?? 0);
    if ($name === '') $errors[] = 'Nama wajib diisi.';
    if (strlen(preg_replace('/\D/', '', $wa)) < 9) $errors[] = 'Nomor WhatsApp tidak valid.';
    if (!isset($cfg['sessions'][$session])) $errors[] = 'Pilih sesi.';
    if (!in_array($status, ['Umum', 'Pelajar'], true)) $errors[] = 'Pilih status.';
    if ($age < 5 || $age > 100) $errors[] = 'Usia tidak valid.';
    if (!$errors) {
        $result = store_tx(function (&$rows) use ($cfg, $name, $wa, $session, $status, $age) {
            if ((counts_of($rows)[$session] ?? 0) >= $cfg['capacity']) return 'full';
            foreach ($rows as $r) if ($r['whatsapp'] === $wa && $r['session'] === $session) return 'dup';
            $rows[] = ['id' => uniqid(), 'name' => $name, 'whatsapp' => $wa, 'session' => $session,
                       'status' => $status, 'age' => $age, 'created_at' => date('c')];
            return 'ok';
        });
        if ($result === 'full') $errors[] = 'Maaf, ' . $cfg['sessions'][$session] . ' sudah penuh. Silakan pilih sesi lain.';
        elseif ($result === 'dup') $errors[] = 'Nomor ini sudah terdaftar di sesi tersebut.';
        else $done = ['name' => $name, 'session' => $cfg['sessions'][$session]];
    }
}
$counts = counts_of(store_all());
?><!doctype html>
<html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar <?= h($cfg['title']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--teal:#0b6e7a;--teal-d:#064a54;--sand:#f6efe6;--ink:#1c2b2e;--mut:#6b7b7e;--line:#dfe6e4;--acc:#e9a23b;--err:#b3261e}
*{box-sizing:border-box}
body{margin:0;background:var(--sand);color:var(--ink);font:15px/1.5 Poppins,system-ui,sans-serif}
.hero{background:linear-gradient(160deg,var(--teal-d),var(--teal) 70%,#2a9aa6);color:#fff;padding:36px 20px 80px;text-align:center}
.brand{font-weight:300;letter-spacing:.32em;font-size:15px;opacity:.95}.brand b{font-weight:700}
.tag{display:inline-block;margin-top:26px;background:var(--acc);color:#3a2600;font-weight:700;font-size:12px;letter-spacing:.12em;padding:4px 14px;border-radius:99px}
h1{margin:12px 0 6px;font-size:34px;line-height:1.15;font-weight:800}
.sub{opacity:.9;font-size:16px}
.wrap{max-width:520px;margin:-52px auto 0;padding:0 16px 48px}
.card{background:#fff;border-radius:20px;padding:22px;box-shadow:0 10px 30px rgba(6,74,84,.12);margin-bottom:16px}
.info{display:grid;gap:14px}
.row{display:flex;gap:12px;align-items:flex-start}
.ic{flex:none;width:38px;height:38px;border-radius:12px;background:#e5f2f3;display:grid;place-items:center;font-size:18px}
.row small{display:block;color:var(--mut);font-size:12px}.row b{font-weight:600}
h2{margin:0 0 4px;font-size:18px}
label.f{display:block;font-weight:600;font-size:14px;margin:16px 0 6px}
input[type=text],input[type=tel],input[type=number]{width:100%;padding:13px 14px;border:1.5px solid var(--line);border-radius:12px;font:inherit;background:#fbfdfc;transition:.15s}
input:focus{outline:0;border-color:var(--teal);box-shadow:0 0 0 3px rgba(11,110,122,.15)}
.opts{display:grid;gap:10px}.opts.two{grid-template-columns:1fr 1fr}
.opt{position:relative;display:block;cursor:pointer}
.opt input{position:absolute;opacity:0}
.opt .box{display:flex;align-items:center;justify-content:space-between;gap:8px;border:1.5px solid var(--line);border-radius:12px;padding:13px 14px;transition:.15s;background:#fbfdfc}
.opt input:checked+.box{border-color:var(--teal);background:#e5f2f3;box-shadow:0 0 0 3px rgba(11,110,122,.12)}
.opt .box em{font-style:normal;font-size:12px;color:var(--teal);font-weight:600}
.opt.full{cursor:not-allowed}.opt.full .box{opacity:.55;background:#eee}.opt.full em{color:var(--err)}
button{width:100%;margin-top:22px;padding:15px;border:0;border-radius:14px;background:var(--teal);color:#fff;font:600 16px Poppins,sans-serif;cursor:pointer;transition:.15s}
button:hover{background:var(--teal-d)}button:disabled{background:#b9c4c3;cursor:not-allowed}
.err{background:#fdecea;border:1px solid #f5c2bd;color:var(--err);padding:10px 14px;border-radius:12px;margin-top:14px;font-size:14px}
.err ul{margin:0;padding-left:18px}
.ok{text-align:center;padding:10px 0}.ok .check{width:64px;height:64px;border-radius:50%;background:#e5f2f3;color:var(--teal);display:grid;place-items:center;font-size:32px;margin:0 auto 12px}
.foot{text-align:center;color:var(--mut);font-size:13px}.foot b{color:var(--teal)}.foot a{color:var(--teal);text-decoration:none}.foot a:hover{text-decoration:underline}
</style></head><body>
<header class="hero">
  <img src="logo-beautylatory.webp" alt="Beautylatory" style="height:40px;width:auto;display:block;margin:0 auto;filter:brightness(0) invert(1)">
  <span class="tag"><?= h($cfg['price']) ?> · POP-UP STORE BALI</span>
  <h1><?= h($cfg['title']) ?></h1>
  <div class="sub"><?= h(implode(' & ', $cfg['classes'])) ?></div>
</header>
<div class="wrap">
  <section class="card info">
    <div class="row"><div class="ic">📅</div><div><small>Tanggal</small><b><?= h($cfg['date']) ?></b></div></div>
    <div class="row"><div class="ic">⏰</div><div><small>Sesi</small><b><?= h(implode('  ·  ', array_map(fn($s) => preg_replace('/^.*— /u', '', $s), $cfg['sessions']))) ?></b> WITA</div></div>
    <div class="row"><div class="ic">📍</div><div><small>Lokasi</small><b><?= h($cfg['location']) ?></b><br><span style="color:var(--mut);font-size:13px"><?= h($cfg['address']) ?></span></div></div>
  </section>

<?php if ($done): ?>
  <section class="card ok">
    <div class="check">✓</div>
    <h2>Pendaftaran berhasil!</h2>
    <p>Terima kasih, <b><?= h($done['name']) ?></b>.<br>Kamu terdaftar di <b><?= h($done['session']) ?></b>, <?= h($cfg['date']) ?>.</p>
    <p class="foot">Jangan lupa come &amp; ❤️ IG <a href="https://www.instagram.com/beautylatory?utm_source=ig_web_button_share_sheet&amp;knvt=ZDNlZDc0MzIxNw==" target="_blank" rel="noopener"><b>@beautylatory</b></a></p>
  </section>
<?php else: ?>
  <form class="card" method="post" novalidate>
    <h2>Form Pendaftaran</h2>
    <div class="foot" style="text-align:left">Gratis · maksimal <?= (int)$cfg['capacity'] ?> peserta per sesi</div>
    <?php if ($errors): ?><div class="err"><ul><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div><?php endif; ?>

    <label class="f" for="name">Nama</label>
    <input type="text" id="name" name="name" required value="<?= h($old['name'] ?? '') ?>">

    <label class="f" for="wa">Nomor WhatsApp</label>
    <input type="tel" id="wa" name="whatsapp" placeholder="08xxxxxxxxxx" required value="<?= h($old['whatsapp'] ?? '') ?>">

    <label class="f">Pilih sesi</label>
    <div class="opts">
    <?php $anyOpen = false; foreach ($cfg['sessions'] as $k => $label):
        $left = $cfg['capacity'] - ($counts[$k] ?? 0); $full = $left <= 0; if (!$full) $anyOpen = true; ?>
      <label class="opt <?= $full ? 'full' : '' ?>">
        <input type="radio" name="session" value="<?= $k ?>" <?= $full ? 'disabled' : '' ?> <?= ($old['session'] ?? '') == $k && !$full ? 'checked' : '' ?>>
        <span class="box"><span><?= h($label) ?></span><em><?= $full ? 'PENUH' : "sisa $left kursi" ?></em></span>
      </label>
    <?php endforeach; ?>
    </div>

    <label class="f" for="age">Usia</label>
    <input type="number" id="age" name="age" min="5" max="100" required value="<?= h($old['age'] ?? '') ?>">

    <label class="f">Status</label>
    <div class="opts two">
    <?php foreach (['Umum', 'Pelajar'] as $s): ?>
      <label class="opt"><input type="radio" name="status" value="<?= $s ?>" <?= ($old['status'] ?? '') === $s ? 'checked' : '' ?>><span class="box"><?= $s ?></span></label>
    <?php endforeach; ?>
    </div>

    <button type="submit" <?= $anyOpen ? '' : 'disabled' ?>><?= $anyOpen ? 'Daftar Sekarang' : 'Pendaftaran Ditutup — Semua Sesi Penuh' ?></button>
  </form>
  <p class="foot">Come &amp; ❤️ IG <a href="https://www.instagram.com/beautylatory?utm_source=ig_web_button_share_sheet&amp;knvt=ZDNlZDc0MzIxNw==" target="_blank" rel="noopener"><b>@beautylatory</b></a></p>
<?php endif; ?>
</div>
</body></html>
