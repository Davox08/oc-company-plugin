<?php
// plugins/davox/company/controllers/invoices/_invoice_pdf.htm

// Las variables pasadas al makePartial están disponibles directamente
// $invoice
// $company

// Asegúrate de que las relaciones estén cargadas, si no lo están en el controlador:
// if (!isset($invoice->client)) $invoice->load('client');
// if (!isset($invoice->services)) $invoice->load('services');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= e($invoice->invoice_number) ?></title>
    <style>
        /* Global Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20mm; /* A4 margins */
            font-size: 10pt;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24pt;
            color: #333;
        }
        .invoice-details, .bill-to {
            margin-bottom: 20px;
            overflow: hidden; /* Clearfix */
        }
        .invoice-details .left, .invoice-details .right,
        .bill-to .left, .bill-to .right {
            float: left;
            width: 50%;
        }
        .invoice-details .right, .bill-to .right {
            text-align: right;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .totals-table {
            width: 40%; /* Adjust as needed */
            float: right; /* To align to the right */
            border: none;
        }
        .totals-table td {
            border: none;
            padding: 4px 8px;
        }
        .totals-table tr.total-row td {
            font-weight: bold;
            border-top: 1px solid #ccc;
        }
        .company-info {
            clear: both; /* Ensure it's below floats */
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .footer-contact {
            margin-top: 20px;
            text-align: center;
            font-size: 10pt;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice</h1>
            <h2>#<?= e($invoice->invoice_number) ?></h2>
        </div>

        <div class="invoice-details">
            <div class="left">
                <p class="section-title">Bill to</p>
                <p><strong><?= e($invoice->client->name) ?></strong></p>
                <p><?= e($invoice->client->email) ?></p>
                <p><?= e($invoice->client->address) ?></p>
            </div>
            <div class="right">
                <p class="section-title">DATE:</p>
                <p><?= e(date('M jS, Y', strtotime($invoice->issue_date))) ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ITEM DESCRIPTION</th>
                    <th>QTY</th>
                    <th>PRICE</th>
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice->services as $service): ?>
                    <tr>
                        <td>
                            <strong><?= e($service->name) ?></strong><br>
                            <?php if (isset($service->pivot->description) && $service->pivot->description): ?>
                                <?= e($service->pivot->description) ?>
                            <?php elseif (isset($service->description) && $service->description): ?>
                                <?= e($service->description) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= e($service->pivot->quantity) ?></td>
                        <td>$<?= e(number_format($service->pivot->price, 2)) ?></td>
                        <td class="text-right">$<?= e(number_format($service->pivot->quantity * $service->pivot->price, 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="totals-table">
            <tbody>
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">$<?= e(number_format($invoice->subtotal, 2)) ?></td>
                </tr>
                <tr>
                    <td>GST (5%)</td>
                    <td class="text-right">$<?= e(number_format($invoice->tax, 2)) ?></td>
                </tr>
                <tr class="total-row">
                    <td>Total</td>
                    <td class="text-right">$<?= e(number_format($invoice->total, 2)) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="company-info">
            <h3><?= e($company['name']) ?></h3>
            <p>ADDRESS <?= e($company['address']) ?></p>
            <p>GST <?= e($company['gst']) ?></p>
            <p>PHONE <?= e($company['phone']) ?></p>
            <p>EMAIL <?= e($company['email']) ?></p>
        </div>

        <div class="footer-contact">
            <p>If you have any questions, please contact us!</p>
        </div>
    </div>
</body>
</html>