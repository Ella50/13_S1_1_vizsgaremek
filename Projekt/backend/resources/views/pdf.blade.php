<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SZÁMLA - {{ $invoice->invoiceNumber }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-info {
            width: 50%;
        }
        .invoice-info {
            width: 40%;
            text-align: right;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 18px;
            font-weight: bold;
        }
        .content {
            margin-bottom: 30px;
        }
        .billing-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .from, .to {
            width: 45%;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .totals {
            margin-left: auto;
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .total-row.grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 50px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
            font-size: 10px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-generated {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Fejléc -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">{{ $company['name'] }}</div>
                <div>Cím: {{ $company['address'] }}</div>
                <div>Adószám: {{ $company['tax_number'] }}</div>
                <div>Bankszámla: {{ $company['bank_account'] }}</div>
                <div>Telefon: {{ $company['phone'] }}</div>
                <div>Email: {{ $company['email'] }}</div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">SZÁMLA</div>
                <div class="invoice-number">{{ $invoice->invoiceNumber }}</div>
                <div>Kiállítás dátuma: {{ $invoice->issueDate->format('Y.m.d.') }}</div>
                <div>Fizetési határidő: {{ $invoice->dueDate->format('Y.m.d.') }}</div>
                <div>Státusz: 
                    <span class="status-badge status-{{ strtolower($invoice->invoiceStatus) }}">
                        {{ $invoice->invoiceStatus }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Számlázási információk -->
        <div class="billing-info">
            <div class="to">
                <div class="section-title">Vevő adatai</div>
                <div><strong>{{ $user->lastName }} {{ $user->firstName }}</strong></div>
                @if($user->address)
                    <div>Cím: {{ $user->address }}</div>
                @endif
                @if($user->email)
                    <div>Email: {{ $user->email }}</div>
                @endif
                @if($user->userType)
                    <div>Felhasználó típusa: {{ $user->userType }}</div>
                @endif
            </div>
        </div>

        <!-- Számlázási időszak -->
        <div class="content">
            <div class="section-title">Számlázási időszak</div>
            <div><strong>{{ $invoice->billingMonth->format('Y. F') }}</strong></div>
        </div>

        <!-- Rendelések listája -->
        <div class="content">
            <div class="section-title">Megrendelt étkezések</div>
            <table>
                <thead>
                    <tr>
                        <th width="15%">Dátum</th>
                        <th width="35%">Étkezés</th>
                        <th width="15%">Menü</th>
                        <th width="15%">Ár kategória</th>
                        <th width="20%">Ár</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->orders as $order)
                    <tr>
                        <td>{{ $order->orderDate->format('Y.m.d.') }}</td>
                        <td>{{ $order->menuItem->name ?? 'Ismeretlen' }}</td>
                        <td>{{ $order->selectedOption }}</td>
                        <td>{{ $order->price->priceCategory ?? 'Normál' }}</td>
                        <td>{{ number_format($order->price->amount ?? 0, 0, ',', ' ') }} Ft</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Összesítés -->
        <div class="totals">
            <div class="total-row">
                <span>Összesen:</span>
                <span>{{ number_format($invoice->totalAmount, 0, ',', ' ') }} Ft</span>
            </div>
            
            @if($invoice->invoiceStatus === 'Fizetve')
            <div class="total-row">
                <span>Fizetési mód:</span>
                <span>{{ $invoice->paymentMethod }}</span>
            </div>
            <div class="total-row">
                <span>Fizetés dátuma:</span>
                <span>{{ $invoice->paidAt ? $invoice->paidAt->format('Y.m.d. H:i') : '-' }}</span>
            </div>
            @endif
            
            <div class="total-row grand-total">
                <span>Fizetendő összeg:</span>
                <span>{{ number_format($invoice->totalAmount, 0, ',', ' ') }} Ft</span>
            </div>
        </div>

        <!-- Lábjegyzet -->
        <div class="footer">
            <p><strong>Fizetési információk:</strong></p>
            <p>Bankszámla szám: {{ $company['bank_account'] }}</p>
            <p>Kedvezményezett: {{ $company['name'] }}</p>
            <p>Közlemény: {{ $invoice->invoiceNumber }}</p>
            <p style="margin-top: 20px;">
                Ez a számla elektronikus úton lett kiállítva, papír alapon történő nyomtatása nem szükséges.
                <br>A számla az Áfa tv. 169. § (2) bekezdése alapján nem tartalmaz áfát.
            </p>
        </div>
    </div>
</body>
</html>