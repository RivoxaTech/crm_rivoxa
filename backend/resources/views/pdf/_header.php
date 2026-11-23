<?php /** Black brand band shared by invoice and voucher. Expects $logo, $title, $accent. */ ?>
<table style="background:#000000">
<tr>
<td style="padding:40px 0 36px 48px; vertical-align:middle"><img src="<?= $logo ?>" style="height:68px"></td>
<td style="padding:40px 48px 36px 0; text-align:right; vertical-align:middle">
<div style="font-family:'Montserrat'; font-weight:700; font-size:34px; letter-spacing:8px; color:#ffffff; line-height:40px"><?= e($title) ?></div>
<table style="width:56px; margin-left:auto; margin-top:6px"><tr><td style="height:3px; background:<?= $accent ?>; font-size:1px; line-height:1px">&nbsp;</td></tr></table>
</td>
</tr>
</table>
