<?php
$accent = '#e3161e';
$accentText = '#b3121b';
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Payout <?= e($voucher_no) ?></title>
<style>
<?= $fontCss ?>
@page { margin: 0; }
body { margin: 0; font-family: 'DM Sans'; font-size: 15px; line-height: 19px; color: #141414; }
table { border-collapse: collapse; border-spacing: 0; width: 100%; }
td { padding: 0; vertical-align: top; }
.lab { line-height: 16px; font-family: 'Montserrat'; font-weight: 600; font-size: 12px; letter-spacing: 2px; color: <?= $accentText ?>; }
.row td { padding: 14px 0; border-bottom: 1px solid #d9d9d9; }
</style>
</head>
<body>

<?php include $viewsDir.'/_header.php'; ?>

<div style="padding:36px 48px 0 48px">
<table>
<tr>
<td>
<div class="lab">PAID TO</div>
<div style="font-family:'Montserrat'; font-weight:700; font-size:24px; line-height:30px; margin-top:8px"><?= e($partner) ?></div>
</td>
<td style="text-align:right">
<div class="lab">VOUCHER NO.</div>
<div style="font-weight:700; font-size:18px; line-height:24px; margin-top:8px"><?= e($voucher_no) ?></div>
</td>
</tr>
</table>

<table style="margin-top:28px; font-size:15px">
<tr class="row"><td style="color:#666666; width:160px">Date</td><td style="font-weight:700"><?= e($date) ?></td></tr>
<tr class="row"><td style="color:#666666">Project</td><td style="font-weight:700"><?= e($project) ?></td></tr>
<tr class="row"><td style="color:#666666">Method</td><td style="font-weight:700"><?= e($method) ?></td></tr>
<tr class="row"><td style="color:#666666">Paid from</td><td style="font-weight:700">Company account</td></tr>
<?php if ($note !== ''): ?><tr class="row"><td style="color:#666666">Note</td><td><?= e($note) ?></td></tr><?php endif; ?>
</table>

<div style="margin-top:28px; background:#000000; color:#ffffff; border-radius:6px; padding:18px 22px">
<table><tr>
<td style="vertical-align:middle"><span style="font-family:'Montserrat'; font-weight:600; font-size:12px; letter-spacing:2px; color:#ffffff">AMOUNT PAID</span></td>
<td style="text-align:right; vertical-align:middle"><span style="font-family:'Montserrat'; font-weight:700; font-size:24px; color:#ffffff"><?= e($amount) ?></span></td>
</tr></table>
</div>

<table style="margin-top:70px">
<tr>
<td style="width:44%; border-top:1px solid #141414; padding-top:8px; font-size:13px; color:#666666">Paid by (<?= e($company) ?>)</td>
<td style="width:12%">&nbsp;</td>
<td style="width:44%; border-top:1px solid #141414; padding-top:8px; font-size:13px; color:#666666">Received by (<?= e($partner) ?>)</td>
</tr>
</table>
</div>

<div style="position:fixed; left:0; right:0; bottom:0; padding:0 48px 32px 48px">
<table><tr><td style="width:48px; height:2px; background:<?= $accent ?>; font-size:1px; line-height:1px">&nbsp;</td><td style="border-top:1px solid #d9d9d9; font-size:1px; line-height:1px; padding-top:1px">&nbsp;</td></tr></table>
<table style="margin-top:14px; font-family:'Montserrat'; font-weight:600; font-size:13px; letter-spacing:1px">
<tr><td><?= e($company) ?></td><td style="text-align:right"><?= e($site) ?></td></tr>
</table>
</div>

</body>
</html>
