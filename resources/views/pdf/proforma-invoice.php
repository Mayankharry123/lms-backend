<?php

/**
 * Proforma Invoice PDF template.
 * Layout follows the official Mobiyoung Proforma Invoice format:
 * Letterhead, Client & Invoice Metadata, Items Grid, Totals, Amount in Words,
 * Standard Notes & Terms, Bank Details, and Authorised Signatory with stamp.
 *
 * @var array<string, mixed> $document
 */

$e = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$issuer = $document['issuer'];
$client = $document['client'];
$items = $document['items'];
$minRows = 6;
$emptyRowsNeeded = max(0, $minRows - count($items));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: dejavusans, sans-serif;
        font-size: 7.5pt;
        color: #000;
        margin: 0;
        padding: 0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
    }
    td, th {
        vertical-align: top;
        word-wrap: break-word;
    }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    
    .company {
        font-size: 10.5pt;
        font-weight: bold;
        letter-spacing: 0.2px;
    }
    .company-sub {
        font-size: 7pt;
        line-height: 1.35;
        margin-top: 1px;
    }
    .sheet {
        border: 1pt solid #000;
    }
    .head-td {
        padding: 3px 6px 4px;
        text-align: center;
    }
    .title-bar {
        font-size: 10pt;
        font-weight: bold;
        text-align: center;
        padding: 3px;
        border-top: 0.8pt solid #000;
        border-bottom: 0.8pt solid #000;
        letter-spacing: 0.5px;
    }
    .meta-box {
        border-bottom: 0.8pt solid #000;
    }
    .meta-box td {
        padding: 2.5px 5px;
        font-size: 7.2pt;
        line-height: 1.35;
    }
    .meta-left {
        width: 54%;
        border-right: 0.8pt solid #000;
        vertical-align: top;
    }
    .meta-right {
        width: 46%;
        vertical-align: top;
    }
    .meta-table td {
        padding: 1.5px 2px;
        border: none;
    }
    .items-table {
        border-collapse: collapse;
    }
    .items-table th {
        font-size: 7.2pt;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        border: 0.7pt solid #000;
        padding: 3px 2px;
        background-color: #fcfcfc;
    }
    .items-table td {
        border: 0.7pt solid #000;
        padding: 3px 4px;
        font-size: 7.2pt;
        vertical-align: middle;
    }
    .num { text-align: right; white-space: nowrap; }
    .words-box td {
        border-top: none;
        border-bottom: 0.8pt solid #000;
        padding: 3.5px 6px;
        font-size: 7.5pt;
    }
    .note-box {
        border-bottom: 0.8pt solid #000;
        padding: 4px 6px 3px;
        font-size: 6.8pt;
        line-height: 1.32;
    }
    .note-title {
        font-weight: bold;
        text-decoration: underline;
        margin-bottom: 2px;
        font-size: 7pt;
    }
    .note-table {
        width: 100%;
        border: none;
    }
    .note-table td {
        border: none;
        padding: 0.8px 1px;
        font-size: 6.8pt;
        line-height: 1.25;
        vertical-align: top;
    }
    .sign-box {
        padding: 3px 6px;
        font-size: 7pt;
    }
    .sign-left {
        width: 65%;
        vertical-align: top;
    }
    .sign-right {
        width: 35%;
        text-align: center;
        vertical-align: top;
    }
    .stamp-img {
        height: 48px;
        margin: 2px 0;
    }
</style>
</head>
<body>

<table class="sheet">
    <!-- Header Letterhead -->
    <tr>
        <td class="head-td" colspan="2">
            <div class="company"><?= $e($issuer['name']) ?></div>
            <div class="company-sub"><?= $e($issuer['registered_office']) ?></div>
            <div class="company-sub">Email-id-<?= $e($issuer['email']) ?> CONTACT:<?= $e($issuer['contact']) ?></div>
            <div class="company-sub">CIN: <?= $e($issuer['cin']) ?></div>
        </td>
    </tr>

    <!-- Title Bar -->
    <tr>
        <td class="title-bar" colspan="2">PROFORMA INVOICE</td>
    </tr>

    <!-- Two-column Metadata -->
    <tr class="meta-box">
        <!-- Left Side: Client details -->
        <td class="meta-left">
            <table class="meta-table">
                <tr>
                    <td class="bold" style="width: 22%;">PAN NO.</td>
                    <td class="bold" colspan="2">: <?= $e($issuer['pan']) ?></td>
                </tr>
                <tr>
                    <td class="bold">Client</td>
                    <td class="bold" colspan="2">: <?= $e($client['name']) ?></td>
                </tr>
                <?php if (!empty($client['address'])): ?>
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="2"><?= nl2br($e($client['address'])) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="bold">State Code</td>
                    <td colspan="2">: <?= $e($client['state_code']) ?></td>
                </tr>
                <tr>
                    <td class="bold">GST NO:</td>
                    <td class="bold" colspan="2">: <?= $e($client['gst_no']) ?></td>
                </tr>
                <?php if (!empty($client['kind_attn'])): ?>
                <tr>
                    <td class="bold" style="padding-top: 4px;">Kind Attn.:</td>
                    <td colspan="2" style="padding-top: 4px;">: <?= $e($client['kind_attn']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </td>

        <!-- Right Side: Invoice & Reference details -->
        <td class="meta-right">
            <table class="meta-table">
                <tr>
                    <td class="bold" style="width: 32%;">GST NO.</td>
                    <td class="bold">: <?= $e($issuer['gst']) ?></td>
                </tr>
                <tr>
                    <td class="bold">P.Invoice No.</td>
                    <td class="bold">: <?= $e($document['pi_number']) ?></td>
                </tr>
                <tr>
                    <td class="bold">Date:</td>
                    <td>: <?= $e($document['date']) ?></td>
                </tr>
                <tr>
                    <td class="bold">PO No.</td>
                    <td>: <?= $e($document['po_no']) ?></td>
                </tr>
                <tr>
                    <td class="bold">PO Date</td>
                    <td>: <?= $e($document['po_date']) ?></td>
                </tr>
                <tr>
                    <td class="bold">Period</td>
                    <td>: <?= $e($document['period']) ?></td>
                </tr>
                <tr>
                    <td class="bold">Brand</td>
                    <td>: <?= $e($document['brand_name']) ?></td>
                </tr>
                <tr>
                    <td class="bold">Campaign</td>
                    <td>: <?= $e($document['campaign']) ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Line Items Table -->
    <tr>
        <td colspan="2" style="padding: 0;">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">Sr.No</th>
                        <th style="width: 48%;">Description</th>
                        <th style="width: 12%;">HSN Code</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 12%;">RATE (Rs.)</th>
                        <th style="width: 14%;">Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $idx => $item): ?>
                    <tr>
                        <td class="center"><?= $idx + 1 ?></td>
                        <td><?= nl2br($e($item['order_name'])) ?></td>
                        <td class="center"><?= $e($item['hsn_sac']) ?></td>
                        <td class="center"><?= $item['slot'] > 0 ? $e($item['slot']) : '' ?></td>
                        <td class="num"><?= $item['rate'] > 0 ? $e(number_format((float) $item['rate'], 2)) : '' ?></td>
                        <td class="num bold"><?= $e(number_format((float) $item['amount'], 2)) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php for ($i = 0; $i < $emptyRowsNeeded; $i++): ?>
                    <tr>
                        <td style="height: 12px;">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <?php endfor; ?>

                    <!-- Sub Total -->
                    <tr>
                        <td colspan="4" style="border-right: none; border-bottom: none;"></td>
                        <td class="bold right" style="border-left: none;">Sub Total</td>
                        <td class="num bold"><?= $e(number_format((float) $document['subtotal'], 2)) ?></td>
                    </tr>

                    <!-- Taxes -->
                    <?php if (!empty($document['taxes'])): ?>
                        <?php foreach ($document['taxes'] as $tax): ?>
                        <tr>
                            <td colspan="4" style="border-right: none; border-top: none; border-bottom: none;"></td>
                            <td class="bold right" style="border-left: none; color: #1a428a;"><?= $e($tax['label']) ?></td>
                            <td class="num bold"><?= $e(number_format((float) $tax['amount'], 2)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Grand Total -->
                    <tr>
                        <td colspan="4" class="bold center" style="border-right: none;">TOTAL :</td>
                        <td class="bold right" style="border-left: none;"></td>
                        <td class="num bold" style="background-color: #f7f7f7;"><?= $e(number_format((float) $document['total_amount'], 2)) ?></td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>

    <!-- Amount in Words -->
    <tr class="words-box">
        <td colspan="2">
            <span class="bold">Amount (IN WORDS) :</span> <?= $e($document['amount_in_words']) ?>
        </td>
    </tr>

    <!-- Notes & Terms -->
    <tr>
        <td colspan="2" class="note-box">
            <div class="note-title">Note:</div>
            <table class="note-table">
                <tr>
                    <td style="width: 2.5%;" class="bold">1</td>
                    <td>All applicable Taxes &amp; Levies will be charges as actual.</td>
                </tr>
                <tr>
                    <td class="bold">2</td>
                    <td>Failure to return a signed copy within 5 days will be taken as an acceptance of details and cost of this approval form.</td>
                </tr>
                <tr>
                    <td class="bold">3</td>
                    <td>M/s MOBIYOUNG DIGITAL AD AGENCY PVT. LTD. is not reponsible for any lossed or claims arising out of information supplied by the client or its creative agencies in furtherance of services provided under this estimate.</td>
                </tr>
                <tr>
                    <td class="bold">4</td>
                    <td>Any cost incurred in alteration or cancellation of approved estimate has be be borne by the client</td>
                </tr>
                <tr>
                    <td class="bold">5</td>
                    <td>All Disputes are subject to Haryana Jurisdiction only.</td>
                </tr>
                <tr>
                    <td class="bold">6</td>
                    <td><span class="bold">GSTN /Billing Address :</span> <?= $e($issuer['billing_address']) ?></td>
                </tr>
                <tr>
                    <td class="bold">7</td>
                    <td><span class="bold">Registered address :</span> <?= $e($issuer['registered_office']) ?></td>
                </tr>
                <tr>
                    <td class="bold">8</td>
                    <td><span class="bold">Bank Detail for Remittance :-</span> A/c Holder Name:- MOBIYOUNG DIGITAL AD AGENCY PVT. LTD., A/c no. 50200111740337 , IFSC Code :-HDFC0000280 , Bank name :-HDFC BANK, Branch :- First India Place,Gurugram -122001.</td>
                </tr>
                <tr>
                    <td class="bold">9</td>
                    <td>Please Deduct TDS @ 2% While Making Payments.</td>
                </tr>
                <tr>
                    <td class="bold">10</td>
                    <td><span class="bold">Payment terms :-</span> 100% Advance .</td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Signatory -->
    <tr>
        <td colspan="2" class="sign-box">
            <table style="width: 100%; border: none;">
                <tr>
                    <td class="sign-left">
                        <div class="bold"><?= $e($issuer['signatory']) ?></div>
                    </td>
                    <td class="sign-right">
                        <?php if (!empty($document['stamp_path']) && file_exists($document['stamp_path'])): ?>
                            <img src="<?= $document['stamp_path'] ?>" class="stamp-img" />
                        <?php else: ?>
                            <div style="height: 48px;">&nbsp;</div>
                        <?php endif; ?>
                        <div class="bold">AUTHORISED SIGNATORY</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
