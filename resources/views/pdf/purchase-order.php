<?php

/**
 * Purchase order PDF template.
 * Layout follows the Mobiyoung purchase order: letterhead, consignee, items, totals, and signatory.
 * Every printed value comes from $document.
 *
 * @var array<string, mixed> $document
 */

$e = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$issuer = $document['issuer'];
$itemCount = count($document['items']);
$rowHeight = '';

if ($itemCount === 1) {
    $rowHeight = 'height: 52mm;';
} elseif ($itemCount <= 3) {
    $rowHeight = 'height: 14mm;';
}

$addressLines = $document['consignee_address'] === ''
    ? 0
    : substr_count((string) $document['consignee_address'], "\n") + 1;
$metaRowHeight = max(8, (int) ceil((26 + ($addressLines * 4)) / 4)) . 'mm';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: dejavusans, sans-serif; font-size: 8pt; color: #000; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin: 0; padding: 0; }
    td, th { vertical-align: top; word-wrap: break-word; }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    .company { font-size: 11pt; font-weight: bold; }
    .small { font-size: 7.5pt; line-height: 1.45; }
    .title { font-size: 11pt; font-weight: bold; letter-spacing: 0.4px; text-align: center; padding: 3px 4px; border-top: 0.8pt solid #000; border-bottom: 0.8pt solid #000; }
    .sheet { border: 1pt solid #000; }
    .head td { padding: 5px 10px 3px; text-align: center; }
    .meta td { border: 0.7pt solid #000; padding: 2px 5px; font-size: 8pt; vertical-align: middle; }
    .consignee { vertical-align: top; padding: 4px 6px 5px; }
    .items { page-break-inside: auto; }
    .items thead { display: table-header-group; }
    .items tr { page-break-inside: avoid; }
    .items th { font-size: 7.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 0.7pt solid #000; padding: 3px 3px; }
    .items td { border: 0.7pt solid #000; padding: 3px 4px; vertical-align: top; }
    .num { text-align: right; white-space: nowrap; }
    .qty { text-align: center; }
    .total-label { vertical-align: middle; }
    .words td { border: 0.7pt solid #000; border-top: none; padding: 4px 6px; font-size: 8pt; }
    .footer td { border: 0.7pt solid #000; border-top: none; padding: 5px 7px 4px; font-size: 7.5pt; vertical-align: top; }
    .term { margin: 0 0 1px; line-height: 1.35; }
    .sign { text-align: center; font-size: 7pt; font-weight: bold; }
    .note td { border: 0.7pt solid #000; border-top: none; text-align: center; font-size: 7.5pt; font-weight: bold; padding: 3px 6px; }
</style>
</head>
<body>
<table class="sheet head">
    <tr>
        <td>
            <div class="company"><?= $e($issuer['name']) ?></div>
            <div class="small"><?= $e($issuer['registered_office']) ?></div>
            <div class="small"><?= $e($issuer['billing_address']) ?></div>
            <div class="small">Email-id-<?= $e($issuer['email']) ?> CONTACT:<?= $e($issuer['contact']) ?></div>
            <div class="small">CIN: <?= $e($issuer['cin']) ?></div>
        </td>
    </tr>
    <tr>
        <td class="title">PURCHASE ORDER</td>
    </tr>
</table>
<table class="sheet meta">
    <tr>
        <td class="consignee" style="width: 58%;">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 48%; border: none; padding: 0;" class="bold">PAN NO.- <?= $e($issuer['pan']) ?></td>
                    <td style="width: 52%; border: none; padding: 0; text-align: right;" class="bold">GST NO:- <?= $e($issuer['gst']) ?></td>
                </tr>
            </table>
            <div class="bold" style="margin-top: 2px;">Consignee</div>
            <div class="bold"><?= $e($document['consignee_name']) ?></div>
            <?php if ($document['consignee_address'] !== ''): ?>
                <div><?= nl2br($e($document['consignee_address'])) ?></div>
            <?php endif; ?>
            <div style="margin-top: 2px;">GST NO:- <?= $e($document['consignee_gst']) ?></div>
            <div>Place of Supply : <?= $e($document['place_of_supply']) ?></div>
        </td>
        <td style="width: 42%; padding: 0; border-left: 0.7pt solid #000;">
            <table style="width: 100%; border: none;">
                <tr>
                    <td class="bold" style="width: 42%; height: <?= $metaRowHeight ?>; border-top: none; border-left: none;">Purchase Order No.</td>
                    <td style="width: 58%; height: <?= $metaRowHeight ?>; border-top: none; border-right: none;"><?= $e($document['po_number']) ?></td>
                </tr>
                <tr>
                    <td class="bold" style="height: <?= $metaRowHeight ?>; border-left: none;">Date</td>
                    <td style="height: <?= $metaRowHeight ?>; border-right: none;"><?= $e($document['po_date']) ?></td>
                </tr>
                <tr>
                    <td class="bold" style="height: <?= $metaRowHeight ?>; border-left: none;">Campaign</td>
                    <td style="height: <?= $metaRowHeight ?>; border-right: none;"><?= $e($document['campaign']) ?></td>
                </tr>
                <tr>
                    <td class="bold" style="height: <?= $metaRowHeight ?>; border-left: none; border-bottom: none;">Period</td>
                    <td style="height: <?= $metaRowHeight ?>; border-right: none; border-bottom: none;"><?= $e($document['period']) ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<table class="sheet items" repeat_header="1">
    <thead>
        <tr>
            <th style="width: 40%;">PARTICULARS</th>
            <th style="width: 14%;">HSN/SAC</th>
            <th style="width: 12%;">City</th>
            <th style="width: 8%;">Qty</th>
            <th style="width: 13%;">RATE (Rs.)</th>
            <th style="width: 13%;">Amount<br>(Rs.)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($document['items'] as $item): ?>
            <tr>
                <td style="<?= $rowHeight ?>"><?= nl2br($e($item['description'])) ?></td>
                <td class="center" style="<?= $rowHeight ?>"><?= $e($item['hsn_sac']) ?></td>
                <td class="center" style="<?= $rowHeight ?>"><?= $e($item['city']) ?></td>
                <td class="qty" style="<?= $rowHeight ?>"><?= $e($item['qty']) ?></td>
                <td class="num" style="<?= $rowHeight ?>"><?= $e($item['rate']) ?></td>
                <td class="num" style="<?= $rowHeight ?>"><?= $e($item['amount']) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="4" style="border-bottom: none;"></td>
            <td class="bold total-label">Sub Total</td>
            <td class="num total-label"><?= $e($document['subtotal']) ?></td>
        </tr>
        <?php foreach ($document['taxes'] as $tax): ?>
            <tr>
                <td colspan="4" style="border-top: none; border-bottom: none;"></td>
                <td class="total-label"><?= $e($tax['label']) ?></td>
                <td class="num total-label"><?= $e($tax['amount']) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="4" style="border-top: none;"></td>
            <td class="bold total-label">Total</td>
            <td class="num bold total-label"><?= $e($document['total']) ?></td>
        </tr>
    </tbody>
</table>
<table class="sheet words">
    <tr>
        <td><span class="bold">Amount (in word) :</span> <?= $e($document['amount_in_words']) ?></td>
    </tr>
</table>
<table class="sheet footer">
    <tr>
        <td style="width: 58%; height: 28mm;">
            <?php foreach ($issuer['terms'] as $index => $term): ?>
                <div class="term"><?= ($index + 1) . ') ' . $e($term) ?></div>
            <?php endforeach; ?>
        </td>
        <td style="width: 42%; height: 28mm;" class="sign">
            <div><?= $e($issuer['signatory']) ?></div>
            <div style="font-size: 16mm; line-height: 16mm;">&nbsp;</div>
            <div>AUTHORISED SIGNATORY</div>
        </td>
    </tr>
</table>
<table class="sheet note">
    <tr>
        <td>NOTE : <?= $e($issuer['note']) ?></td>
    </tr>
</table>
</body>
</html>
