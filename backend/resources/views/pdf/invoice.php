<?php
$accent = '#e3161e';
$accentText = '#b3121b';
$rowPad = count($lines) > 7 ? '10px' : '16px';
$title = 'INVOICE';
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice <?= e($invoice_no) ?></title>
<style>
<?= $fontCss ?>
@page { margin: 0 0 128px 0; }
body { margin: 0; font-family: 'DM Sans'; font-size: 15px; line-height: 19px; color: #141414; }
table { border-collapse: collapse; border-spacing: 0; width: 100%; }
td { padding: 0; vertical-align: top; }
.m { font-family: 'Montserrat'; }
.lab { line-height: 16px; font-family: 'Montserrat'; font-weight: 600; font-size: 12px; letter-spacing: 2px; color: <?= $accentText ?>; }
.th { line-height: 16px; font-family: 'Montserrat'; font-weight: 600; font-size: 12px; letter-spacing: 1.5px; padding: 12px 0; border-bottom: 2px solid #141414; }
.td { padding: <?= $rowPad ?> 0; border-bottom: 1px solid #d9d9d9; }
</style>
</head>
<body>

<?php include $viewsDir.'/_header.php'; ?>

<table style="margin-top:36px">
<tr>
<td style="padding-left:48px; width:56%">
<div class="lab">BILLED TO</div>
<div class="m" style="font-weight:700; font-size:24px; margin-top:8px; line-height:30px"><?= e($client_name) ?></div>
<div style="font-size:15px; color:#555555; margin-top:8px"><?= e($client_desc) ?></div>
</td>
<td style="padding-right:48px">
<table style="width:auto; margin-left:auto; font-size:15px">
<?php foreach ([['Invoice No.', $invoice_no], ['Issue Date', $issue_date], ['Payment Terms', $payment_terms], ['Currency', 'PKR (Rs)']] as [$k, $v]): ?>
<tr><td style="color:#666666; padding:0 0 10px 0; white-space:nowrap"><?= e($k) ?></td><td style="font-weight:700; text-align:right; padding:0 0 10px 24px; white-space:nowrap"><?= e($v) ?></td></tr>
<?php endforeach; ?>
</table>
</td>
</tr>
</table>

<div style="padding:22px 48px 0 48px">
<table>
<tr>
<td class="th" style="width:36px">#</td>
<td class="th">DESCRIPTION</td>
<td class="th" style="width:136px; text-align:right; padding-right:16px">RATE</td>
<td class="th" style="width:120px; text-align:right">AMOUNT</td>
</tr>
<?php foreach ($lines as $l): ?>
<tr>
<td class="td" style="color:#777777"><?= e($l['no']) ?></td>
<td class="td" style="padding-right:16px">
<div style="font-size:16px; line-height:21px; font-weight:700"><?= e($l['name']) ?></div>
<?php if ($l['description'] !== ''): ?><div style="font-size:14px; line-height:18px; color:#666666; margin-top:3px"><?= e($l['description']) ?></div><?php endif; ?>
</td>
<td class="td" style="font-size:16px; line-height:21px; text-align:right; padding-right:16px"><?= e($l['rate']) ?></td>
<?php if ($l['pending']): ?>
<td class="td" style="font-size:14px; line-height:21px; font-weight:700; text-align:right; color:<?= $accentText ?>"><?= e($l['amount_text']) ?></td>
<?php else: ?>
<td class="td" style="font-size:16px; line-height:21px; font-weight:700; text-align:right"><?= e($l['amount_text']) ?></td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</table>
</div>

<div style="padding:28px 48px 0 48px; page-break-inside:avoid">
<table>
<tr>
<td style="padding:8px 40px 0 0">
<?php if ($note !== ''): ?>
<div class="lab">NOTE</div>
<div style="font-size:14px; line-height:21px; color:#444444; margin-top:6px"><?= e($note) ?></div>
<?php endif; ?>
<?php if ($payment_record !== ''): ?>
<div class="lab" style="margin-top:20px">PAYMENT RECORD</div>
<div style="font-size:14px; line-height:21px; color:#444444; margin-top:6px"><?= e($payment_record) ?></div>
<?php endif; ?>
</td>
<td style="width:330px">
<table style="font-size:15px">
<tr><td style="color:#555555; padding:8px 0">Subtotal</td><td style="text-align:right; font-weight:700; padding:8px 0">Rs <?= e($subtotal) ?></td></tr>
<?php foreach ($pending_labels as $pl): ?>
<tr><td style="color:#555555; padding:8px 0"><?= e($pl) ?></td><td style="text-align:right; font-weight:700; padding:8px 0">On delivery</td></tr>
<?php endforeach; ?>
<?php foreach ($payments as $i => $p): $last = $i === count($payments) - 1; ?>
<tr>
<td style="color:#555555; padding:8px 0 <?= $last ? '12px' : '8px' ?> 0; <?= $last ? 'border-bottom:1px solid #d9d9d9' : '' ?>"><?= e($p['label']) ?></td>
<td style="text-align:right; font-weight:700; color:#1d6b3a; padding:8px 0 <?= $last ? '12px' : '8px' ?> 0; <?= $last ? 'border-bottom:1px solid #d9d9d9' : '' ?>">&minus; Rs <?= e($p['amount_text']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<div style="margin-top:14px; background:#000000; color:#ffffff; border-radius:6px; padding:16px 18px">
<table>
<tr>
<td style="vertical-align:middle"><span style="font-family:'Montserrat'; font-weight:600; font-size:12px; letter-spacing:2px; color:#ffffff">BALANCE DUE</span></td>
<td style="text-align:right; vertical-align:middle">
<div style="font-family:'Montserrat'; font-weight:700; font-size:22px; color:#ffffff; line-height:26px">Rs <?= e($balance) ?></div>
<?php if ($pending_sub !== ''): ?><div style="font-size:12px; color:#d4d4d4; margin-top:2px"><?= e($pending_sub) ?></div><?php endif; ?>
</td>
</tr>
</table>
</div>
</td>
</tr>
</table>
</div>

<div style="position:fixed; left:0; right:0; bottom:-128px; height:128px">
<div style="padding:0 48px; font-size:15px; color:#444444">Thank you for choosing <?= e($company) ?>.</div>
<div style="padding:24px 48px 0 48px">
<table><tr><td style="width:48px; height:2px; background:<?= $accent ?>; font-size:1px; line-height:1px">&nbsp;</td><td style="border-top:1px solid #d9d9d9; font-size:1px; line-height:1px; padding-top:1px">&nbsp;</td></tr></table>
<table style="margin-top:14px; font-family:'Montserrat'; font-weight:600; font-size:13px; letter-spacing:1px">
<tr><td><?= e($company) ?> <span style="color:<?= $accentText ?>">·</span> <?= e($client_name) ?></td><td style="text-align:right"><?= e($site) ?></td></tr>
</table>
</div>
</div>

</body>
</html>
