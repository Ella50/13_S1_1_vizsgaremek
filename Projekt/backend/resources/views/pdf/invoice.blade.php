<!doctype html>
<html>

@php
    \Carbon\Carbon::setLocale('hu');
    setlocale(LC_TIME, 'hu_HU.UTF-8');

    $path = public_path('images/eMenza.png');
    $logo = base64_encode(file_get_contents($path));
    $imagePath = __DIR__ . '/../../public/images/eMenza.png';

@endphp

<head>
<meta charset="utf-8">
<style>
body{
    font-family: DejaVu Sans, sans-serif;
    font-size:12px;
    margin:40px;
}

.header{
    width:100%;
    margin-bottom:40px;
}

.header td{
    vertical-align:top;
}

.logo{
    width:120px;
    height:120px;
    border-radius:60px;
    background:#000;
    color:#fff;
    text-align:center;
    line-height:120px;
    font-weight:bold;
    float:right;
}

.invoice-info{
    width:100%;
    margin-bottom:30px;
}

.invoice-info td{
    vertical-align:top;
    padding:5px;
}

table.items{
    width:100%;
    border-collapse:collapse;
    margin:30px 0;
}

.items th{
    background:#eee;
    border:1px solid #ccc;
    padding:12px;
    text-align:center;
}

.items td{
    border:1px solid #ccc;
    padding:12px;
    text-align:right;
}

.items td:first-child{
    text-align:center;
    font-weight:bold;
}

.right{
    text-align:right;
}

.summary{
    width:100%;
    margin-top:20px;
}

.summary td{
    padding:8px;
}

.total-row{
    border-top:2px solid #000;
    font-weight:bold;
}

.footer{
    margin-top:40px;
}
</style>
</head>
<body>

<table class="header">
<tr>
<td>
<strong>Kiskunfélegyházi Szent Benedek PG Két Tanítási Nyelvű Technikum és Kollégium</strong><br>
Cím: 6100 Kiskunfélegyháza, Kossuth Lajos utca 24.<br>
Email: info@szbi-pg.hu<br>
Telefon: +36 30 123 4567<br>
Adószám: 12345678-1-23
</td>
<td style="text-align:right;">
<!--
<div>
    <img src="{{ $imagePath }}" /> 
</div>
-->
</td>
</tr>
</table>

<table class="invoice-info">
<tr>
<td width="50%">
<strong>VEVŐ</strong><br>
{{ $invoice->user->firstName ?? '' }} {{ $invoice->user->lastName ?? '' }}<br>
{{ $invoice->user->address ?? '' }}
</td>
<td width="50%" class="right">
<strong>ÖSSZESÍTŐ SZÁMLA</strong><br>
Számlaszám: {{ $invoice->invoiceNumber }}<br>
Keltezés: {{ \Carbon\Carbon::parse($invoice->issueDate)->format('Y-m-d') }}<br>
Teljesítés időszaka: {{ \Carbon\Carbon::parse($invoice->billingMonth)->translatedFormat('Y. F') }}<br>
Fizetési határidő: {{ \Carbon\Carbon::parse($invoice->dueDate)->format('Y-m-d') }}
</td>
</tr>
</table>

@php
    $totalQuantity = $invoice->orders->count();
    $unitPrice = (float)($invoice->orders->first()->price->amount ?? 0);
    
    $netTotal = $unitPrice * $totalQuantity;
    $vatTotal = $netTotal * 0.27;
    $grossTotal = $netTotal + $vatTotal;
@endphp

<table class="items">
<thead>
<tr>
<th>Mennyiség</th>
<th>Nettó egységár</th>
<th>Nettó összesen</th>
<th>ÁFA (27%)</th>
<th>Bruttó összesen</th>
</tr>
</thead>
<tbody>
<tr>
<td>{{ $totalQuantity }} db</td>
<td>{{ number_format($unitPrice, 0, ',', ' ') }} Ft</td>
<td>{{ number_format($netTotal, 0, ',', ' ') }} Ft</td>
<td>{{ number_format($vatTotal, 0, ',', ' ') }} Ft</td>
<td>{{ number_format($grossTotal, 0, ',', ' ') }} Ft</td>
</tr>
</tbody>
</table>

<table class="summary">
<tr>
<td width="70%"></td>
<td width="30%"></td>
</tr>
<tr>
<td>Nettó összesen:</td>
<td class="right">{{ number_format($netTotal, 0, ',', ' ') }} Ft</td>
</tr>
<tr>
<td>ÁFA (27%):</td>
<td class="right">{{ number_format($vatTotal, 0, ',', ' ') }} Ft</td>
</tr>
<tr class="total-row">
<td>Bruttó végösszeg:</td>
<td class="right">{{ number_format($grossTotal, 0, ',', ' ') }} Ft</td>
</tr>
</table>

<div class="footer">
<strong>FIZETÉSI MÓD</strong><br>
{{ $invoice->paymentMethod ?? 'Banki átutalás' }}<br>
Fizetendő: {{ number_format($grossTotal, 0, ',', ' ') }} Ft

<br><br>

<strong>BANKSZÁMLA</strong><br>
OTP Bank: 12345678-12345678<br>
Közlemény: {{ $invoice->invoiceNumber }}

<br><br>

<small>
Összesítő számla a {{ \Carbon\Carbon::parse($invoice->billingMonth)->translatedFormat('Y. F') }} havi menürendelésekről.<br>
Összes rendelés: {{ $totalQuantity }} db<br>
A számla ÁFA tartalmát 27% mértékkel számítottuk.
</small>
</div>

</body>
</html>