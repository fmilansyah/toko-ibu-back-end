<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../sql_engine.php';

$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-d');
$endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : date('Y-m-d');

$order = new Order();
$data = $order->getReportOrderSQL($startDate, $endDate, true);

$tableBody = '';
$total = 0;

foreach ($data as $i => $o) {
    $total += (int) $o['total_harga'];
    $tableBody .= '
    <tr>
        <td>'.($i + 1).'</td>
        <td>'.$o['nama'].' - '.$o['varian'].'</td>
        <td style="text-align: right;">Rp. '.number_format($o['harga']).'</td>
        <td style="text-align: right;">'.$o['total_qty'].' pcs</td>
        <td style="text-align: right;">Rp. '.number_format($o['total_harga']).'</td>
    </tr>
    ';
}

$tableBody .= '
<tr>
    <td style="font-weight: bold;" colSpan="4">Total</td>
    <td style="text-align: right; font-weigh: bold;">Rp. '.number_format($o['total_harga']).'</td>
</tr>
';

$mpdf = new \Mpdf\Mpdf();

$mpdf->WriteHTML('
<style>
table, th, td {
    border: 1px solid black;
    border-collapse: collapse;
}
th {
    font-weight: bold;
    text-align: left;
}
</style>
<h1 style="text-align: center; font-size: 20px;">Laporan Penjualan Toko Ibu</h1>
<h2 style="text-align: center; font-size: 18px; font-weight: normal;">Periode : '.$startDate.' - '.$endDate.'</h2>
<table style="width: 100%;">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Produk</th>
            <th>Harga Jual</th>
            <th>Jml. Terjual</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>'.$tableBody.'</tbody>
</table>
');

$mpdf->Output('Laporan Penjualan '.$startDate.' - '.$endDate.'.pdf', 'I');
