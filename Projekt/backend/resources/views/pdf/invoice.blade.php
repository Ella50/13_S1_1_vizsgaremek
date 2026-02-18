<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width:100%; border-collapse: collapse; }
    th, td { border:1px solid #ddd; padding:8px; }
    th { background:#f3f3f3; }
  </style>
</head>
<body>
  <h2>Számla: {{ $invoice->invoiceNumber }}</h2>
  <p>
    Név: {{ $invoice->user->name ?? '---' }}<br>
    Hónap: {{ \Carbon\Carbon::parse($invoice->billingMonth)->format('Y-m') }}<br>
    Kiállítva: {{ $invoice->issueDate }}<br>
    Fizetési határidő: {{ $invoice->dueDate }}<br>
    Státusz: {{ $invoice->invoiceStatus }}
  </p>

  <table>
    <thead>
      <tr>
        <th>Dátum</th>
        <th>Menü tétel ID</th>
        <th>Opció</th>
        <th>Ár</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->orders as $o)
        <tr>
          <td>{{ $o->orderDate }}</td>
          <td>{{ $o->menuItems_id }}</td>
          <td>{{ $o->selectedOption }}</td>
          <td>{{ number_format((float)($o->price->amount ?? 0), 0, ',', ' ') }} Ft</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <h3>Összesen: {{ number_format((float)$invoice->totalAmount, 0, ',', ' ') }} Ft</h3>
</body>
</html>
