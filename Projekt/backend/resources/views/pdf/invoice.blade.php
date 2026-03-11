<!doctype html>
<html>
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

.section{
    margin-bottom:30px;
}

.invoice-info{
    width:100%;
}

.invoice-info td{
    vertical-align:top;
    padding:5px;
}

table.items{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

.items th{
    background:#eee;
    border:1px solid #ccc;
    padding:8px;
}

.items td{
    border:1px solid #ccc;
    padding:8px;
}

.right{
    text-align:right;
}

.summary{
    width:40%;
    margin-left:auto;
    margin-top:20px;
}

.summary td{
    padding:6px;
}

.summary .total{
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

<strong>YOUR COMPANY</strong><br>
Cím: 1234 Város, Utca 1.<br>
Email: info@ceg.hu<br>
Telefon: +36 30 123 4567

</td>

<td style="text-align:right;">
<div class="logo">LOGO</div>
</td>
</tr>
</table>

<table class="invoice-info">
<tr>

<td width="50%">
<strong>BILL TO</strong><br>

{{ $invoice->user->firstName ?? '' }}
{{ $invoice->user->lastName ?? '' }}<br>

{{ $invoice->user->address ?? '' }}

</td>

<td width="50%" class="right">

<strong>INVOICE</strong><br>

Invoice No: {{ $invoice->invoiceNumber }}<br>

Issue Date:
{{ \Carbon\Carbon::parse($invoice->issueDate)->format('Y-m-d') }}<br>

Due Date:
{{ \Carbon\Carbon::parse($invoice->dueDate)->format('Y-m-d') }}

</td>

</tr>
</table>

<table class="items">
<thead>
<tr>
<th>Description</th>
<th>Quantity</th>
<th>Unit Price</th>
<th>Amount</th>
</tr>
</thead>

<tbody>

@foreach($invoice->orders as $o)

<tr>

<td>
{{ \Carbon\Carbon::parse($o->orderDate)->format('Y-m-d') }}
- {{ $o->selectedOption }}
</td>

<td class="right">1</td>

<td class="right">
{{ number_format((float)($o->price->amount ?? 0),0,","," ") }} Ft
</td>

<td class="right">
{{ number_format((float)($o->price->amount ?? 0),0,","," ") }} Ft
</td>

</tr>

@endforeach

</tbody>
</table>

<table class="summary">

<tr>
<td>Subtotal</td>
<td class="right">
{{ number_format((float)$invoice->totalAmount,0,","," ") }} Ft
</td>
</tr>

<tr>
<td>VAT</td>
<td class="right">0 Ft</td>
</tr>

<tr class="total">
<td>Total</td>
<td class="right">
{{ number_format((float)$invoice->totalAmount,0,","," ") }} Ft
</td>
</tr>

</table>

<div class="footer">

<strong>PAY BY BANK TRANSFER</strong><br>
Bank: OTP Bank<br>
Számlaszám: 12345678-12345678<br>
Közlemény: {{ $invoice->invoiceNumber }}

<br><br>

<strong>TERMS</strong><br>
Fizetési határidőn belül kérjük rendezni.

</div>

</body>
</html>