<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice #<?= e($invoice->invoice_number ?: $invoice->id) ?></title>
    <style type="text/css">
        @font-face {
            font-family: 'Open Sans';
            font-style: normal;
            font-weight: 400;
            /* Asegúrate de que esta ruta sea accesible para DomPDF, idealmente una ruta absoluta */
            src: url('<?= storage_path('app/davox/company/fonts/OpenSans-Regular.ttf') ?>');
        }

        @font-face {
            font-family: 'Open Sans';
            font-style: italic;
            font-weight: 400;
            src: url('<?= storage_path('app/davox/company/fonts/OpenSans-Italic.ttf') ?>');
        }

        @font-face {
            font-family: 'Open Sans';
            font-style: normal;
            font-weight: 700;
            src: url('<?= storage_path('app/davox/company/fonts/OpenSans-Bold.ttf') ?>');
        }

        @font-face {
            font-family: 'Open Sans';
            font-style: italic;
            font-weight: 700;
            src: url('<?= storage_path('app/davox/company/fonts/OpenSans-BoldItalic.ttf') ?>');
        }

        @font-face {
            font-family: 'Open Sans';
            font-style: normal;
            font-weight: 800;
            /* ExtraBold */
            src: url('<?= storage_path('app/davox/company/fonts/OpenSans-ExtraBold.ttf') ?>');
        }

        @font-face {
            font-family: 'Open Sans';
            font-style: italic;
            font-weight: 800;
            /* ExtraBold Italic */
            src: url('<?= storage_path('app/davox/company/fonts/OpenSans-ExtraBoldItalic.ttf') ?>');
        }

        @page {
            margin: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            font-size: 10pt;
            color: #333132;
            margin: 0;
            padding: 0;
            line-height: 1.2;
            /* Ligero ajuste para legibilidad general */
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1;
        }

        strong,
        b {
            font-weight: 700;
        }

        em,
        i {
            font-style: italic;
        }

        /* Header */
        .header {
            margin-top: 1cm;
            margin-bottom: 0.5cm;
            text-align: center;
        }

        .header__image {
            width: 7cm;
            height: auto;
        }

        /* Fin Header */

        /* Invoice */
        .invoice-container {
            background-color: #eff8fd;
            padding-top: 0.01cm;
            padding-bottom: 0.01cm;
        }

        /* Fin Invoice */

        /* Details Section*/
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table td {
            padding: 0;
        }

        .details-cell-content {
            width: 30%;
            vertical-align: top;
            border-bottom: 0.1cm solid #333132;
            padding-bottom: 0.5cm;
        }

        .details-cell-billto {
            width: 40%;
            vertical-align: top;
            padding-left: 0.5cm;
            padding-right: 0.5cm;
            padding-bottom: 0.5cm;
            border-bottom: 0.1cm solid #00aeef;
        }

        .details-cell-date {
            width: 30%;
            vertical-align: bottom;
            padding-left: 0.5cm;
            border-bottom: 0.1cm solid #00aeef;
            padding-bottom: 0.5cm;
            text-align: left;
        }

        .invoice-title {
            font-size: 28pt;
            font-weight: 800;
            color: #333132;
            line-height: 1;
            margin: 0 0 0.1cm 1.5cm;
        }

        .invoice-number {
            font-size: 10pt;
            line-height: 1;
            font-weight: 400;
            color: #333132;
            margin: 0 0 0 1.5cm;
            padding: 0;
        }

        .bill-to__title {
            font-size: 10pt;
            font-weight: 800;
            color: #231f20;
            margin-bottom: 0.2cm;
            padding-top: 0.2cm;
        }

        .bill-to__list {
            margin: 0 0 0.5cm 0;
            padding: 0;
            list-style: none;
            font-size: 9pt;
        }

        .bill-to__item {
            margin: 0;
            padding: 0;
            line-height: 1;
        }

        .bill-to__item strong {
            font-weight: 700;
        }


        .date__content {
            font-size: 9pt;
            text-transform: uppercase;
            color: #333132;
            margin: 0 0 0.5cm 0;
            padding: 0;
            line-height: 1;
        }

        .date__content strong {
            font-weight: 800;
            color: #333132;
        }

        /* Fin Details Section */

        /* Services Table */
        .services {
            width: 70%;
            margin: 0 0 0 30%;
            border-collapse: collapse;
            border-bottom: #00aeef solid 0.1cm;
        }

        .services-header__row th {
            text-align: left;
            padding: 0.5cm 0.25cm 0.25cm;
            border-bottom: 0.05cm solid #231f20;
            font-size: 10pt;
            font-weight: 700;
            color: #333132;
            text-transform: uppercase;
        }

        .services-header__item.qty,
        .services-header__item.price,
        .services-header__item.total {
            text-align: right;
        }

        .services-header__item.total {
            padding-right: 1.5cm;
        }

        .services-body__row td {
            padding: 0.2cm;
            border-bottom: 0.01cm solid #a2bdcc;
            vertical-align: top;
        }

        .services-body__item h3 {
            font-size: 10pt;
            font-weight: 700;
            margin-bottom: 0.05cm;
            line-height: 1;
        }

        .services-body__item p {
            font-size: 9pt;
            color: #333132;
            line-height: 1;
            margin: 0;
        }

        .services-body__row td.qty,
        .services-body__row td.price,
        .services-body__row td.total {
            text-align: right;
        }

        .services-body__item.total {
            padding-right: 1.5cm;
        }

        .services-body__row.empty {
            height: 0.5cm;
        }

        /* Fin Services Table */

        /* Totals Table */
        .totals {
            width: auto;
            float: right;
            margin-top: 0.5cm;
            border-collapse: collapse;
            clear: both;
        }

        .totals__row td {
            padding: 0.15cm 1.5cm;
            font-size: 10pt;
            font-weight: 700;
        }

        .totals__row td:first-child {
            text-align: right;
            color: #333132;
        }

        .totals__row td:last-child {
            text-align: right;
            color: #333132;
            padding-right: 1.5cm;
        }

        .totals__row.total-final td {
            font-size: 11pt;
            font-weight: 800;
            color: #ffffff;
            background-color: #00aeef;
            padding-top: 0.2cm;
            padding-bottom: 0.2cm;
            font-weight: 800;
            line-height: 1;
        }

        /* Fin Totals Table */

        /* Clearfix para después de los totales si es necesario */
        .clearfix {
            clear: both;
            height: 0;
            line-height: 0;
            font-size: 0;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            padding: 0;
            padding-bottom: 1cm;
            font-size: 8pt;
            color: #333132;
            line-height: 1;
        }

        .footer-content {
            width: 100%;
            text-align: center;
        }

        .company__name-footer {
            font-size: 10pt;
            font-weight: 800;
            margin-bottom: 0.25cm;
            text-align: center;
        }

        .footer-details-table {
            width: auto;
            margin-left: auto;
            margin-right: auto;
            border-collapse: collapse;
        }

        .footer-details-table td {
            padding-top: 0.05cm;
            padding-bottom: 0.05cm;
            font-size: 7pt;
            line-height: 1.3;
            color: #333132;
        }

        .footer-label-cell {
            font-weight: 700;
            text-align: right;
            padding-right: 8px;
            white-space: nowrap;
        }

        .footer-value-cell {
            text-align: left;
        }
        .company-final-text {
            margin-top: 0.5cm;
            font-size: 9pt;
            font-weight: 700;
            text-align: center;
        }

        /* Fin Footer */
    </style>
</head>
<body>
    <div class="header">
        <?php if (!empty($company['logo_image'])): ?>
            <img class="header__image" src="<?= e($company['logo_image']) ?>" alt="<?= e($company['name']) ?>">
        <?php endif; ?>
    </div>
    <div class="invoice-container">
        <div class="details">
            <table class="details-table">
                <tr>
                    <td class="details-cell-content">
                        <h1 class="invoice-title">Invoice</h1>
                        <p class="invoice-number">#<?= e($invoice->invoice_number ?: $invoice->id) ?></p>
                    </td>
                    <td class="details-cell-billto">
                        <h2 class="bill-to__title">Bill to</h2>
                        <ul class="bill-to__list">
                            <li class="bill-to__item">
                                <strong><?= e($invoice->client->name) ?></strong>
                            </li>
                            <?php if (!empty($invoice->client->phone)): ?>
                                <li class="bill-to__item"><?= e($invoice->client->phone) ?></li>
                            <?php endif; ?>
                            <?php if (!empty($invoice->client->email)): ?>
                                <li class="bill-to__item"><?= e($invoice->client->email) ?></li>
                            <?php endif; ?>
                            <?php if (!empty($invoice->client->address)): ?>
                                <li class="bill-to__item"><?= e($invoice->client->address) ?></li>
                            <?php endif; ?>
                            <?php if (!empty($invoice->client->gst)): ?>
                                <li class="bill-to__item">GST: <?= e($invoice->client->gst) ?></li>
                            <?php endif; ?>
                        </ul>
                    </td>
                    <td class="details-cell-date">
                        <p class="date__content">
                            <strong>DATE</strong> : <?= e($invoice->issue_date->format('F jS, Y')) ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <table class="services">
            <thead class="services-header">
                <tr class="services-header__row">
                    <th class="services-header__item">Service</th>
                    <th class="services-header__item qty">Qty</th>
                    <th class="services-header__item price">Price</th>
                    <th class="services-header__item total">Total</th>
                </tr>
            </thead>
            <tbody class="services-body">
                <?php foreach ($invoice->services as $service): ?>
                    <tr class="services-body__row">
                        <td class="services-body__item">
                            <h3><?= e($service->name) ?></h3>
                            <?php if (!empty($service->description)): ?>
                                <p><?= nl2br(e($service->description)) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="services-body__item qty">
                            <?= e($service->pivot->quantity) ?>
                        </td>
                        <td class="services-body__item price">$<?= number_format($service->pivot->price, 2) ?></td>
                        <td class="services-body__item total">$<?= number_format($service->pivot->price * $service->pivot->quantity, 2) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php
                $numberOfServices = count($invoice->services);
                $minRowsInTable = 5;
                $emptyRowsToAdd = max(0, $minRowsInTable - $numberOfServices);
                ?>
                <?php for ($i = 0; $i < $emptyRowsToAdd; $i++): ?>
                    <tr class="services-body__row empty">
                        <td class="service-body__item">&nbsp;</td>
                        <td class="service-body__item qty">&nbsp;</td>
                        <td class="service-body__item price">&nbsp;</td>
                        <td class="service-body__item total">&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <table class="totals">
            <tbody>
                <tr class="totals__row">
                    <td>Subtotal</td>
                    <td>$<?= number_format($invoice->subtotal, 2) ?></td>
                </tr>
                <tr class="totals__row">
                    <td>GST (<?= e($company['tax_rate']) ?>%)</td>
                    <td>$<?= number_format($invoice->tax, 2) ?></td>
                </tr>
                <tr class="totals__row total-final">
                    <td>Total</td>
                    <td>$<?= number_format($invoice->total, 2) ?></td>
                </tr>
            </tbody>
        </table>
        <div class="clearfix"></div>
    </div>
    </div>
    <div class="footer">
        <div class="footer-content">
            <h5 class="company__name-footer"><?= e($company['name']) ?></h5>
            <div class="company-details-footer">
                <table class="footer-details-table">
                    <tr>
                        <td class="footer-label-cell">ADDRESS</td>
                        <td class="footer-value-cell"><?= e($company['address']) ?></td>
                    </tr>
                    <tr>
                        <td class="footer-label-cell">GST</td>
                        <td class="footer-value-cell"><?= e($company['gst']) ?></td>
                    </tr>
                    <tr>
                        <td class="footer-label-cell">PHONE</td>
                        <td class="footer-value-cell"><?= e($company['phone']) ?></td>
                    </tr>
                    <tr>
                        <td class="footer-label-cell">EMAIL</td>
                        <td class="footer-value-cell"><?= e($company['email']) ?></td>
                    </tr>
                </table>
            </div>
            <p class="company-final-text"><?= e($company['final_text']) ?></p>
        </div>
    </div>
</body>
</html>