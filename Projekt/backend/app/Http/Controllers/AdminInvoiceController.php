<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminInvoiceController extends Controller
{
    public function generateMonth(Request $request)
    {
        // pl. "2026-02" formátum
        $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $monthStart = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $monthEnd   = (clone $monthStart)->endOfMonth();

        // 1) Keresd meg azokat a usereket, akiknek van "Rendelve" rendelésük a hónapban
        $userIds = Order::query()
            ->whereBetween('orderDate', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->where('orderStatus', 'Rendelve')
            ->where(function ($q) {
                $q->whereNull('invoice_id')
                ->orWhereDoesntHave('invoice');
            })
            ->distinct()
            ->pluck('user_id');

        $created = [];

        DB::transaction(function () use ($userIds, $monthStart, $monthEnd, &$created) {
            foreach ($userIds as $userId) {
                // 2) Gyűjtsük a számlázandó rendeléseket (Rendelve + invoice_id null)
                $orders = Order::query()
                    ->with('price')
                    ->where('user_id', $userId)
                    ->whereBetween('orderDate', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->where('orderStatus', 'Rendelve')
                    ->where(function ($q) {
                        $q->whereNull('invoice_id')
                        ->orWhereDoesntHave('invoice');
                    })
                    ->lockForUpdate()
                    ->get();

                if ($orders->isEmpty()) continue;

                // 3) Total összeg (price.amount alapján)
                $total = $orders->sum(function ($o) {
                    return (float) ($o->price?->amount ?? 0);
                });

                // 4) Invoice létrehozás
                $invoice = Invoice::create([
                    'user_id'        => $userId,
                    'invoiceNumber'  => $this->generateInvoiceNumber($monthStart),
                    'billingMonth'   => $monthStart->toDateString(),  // első nap dátuma elég
                    'issueDate'      => now()->toDateString(),
                    'dueDate'        => now()->addDays(8)->toDateString(),
                    'totalAmount'    => $total,
                    'paymentMethod'  => 'Bankkártya',                 // vagy amit szeretnél defaultnak
                    'invoiceStatus'  => 'Generálva',
                ]);

                // 5) Rendelések hozzárendelése a számlához
                Order::whereIn('id', $orders->pluck('id'))
                    ->update(['invoice_id' => $invoice->id]);

                $created[] = $invoice->id;
            }
        });

        return response()->json([
            'success' => true,
            'createdCount' => count($created),
            'invoiceIds' => $created,
        ]);
    }

    public function downloadPdf(Invoice $invoice)
    {
        // Admin letöltés: ha csak adminnak, itt ellenőrizd a role-t
        $invoice->load(['user', 'orders.price']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice
        ]);

        return $pdf->download("szamla-{$invoice->invoiceNumber}.pdf");
    }

    public function index(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $q = Invoice::query()->with('user');

        if ($request->filled('month')) {
            $monthStart = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()->toDateString();
            $q->whereDate('billingMonth', $monthStart);
        }

        $invoices = $q->orderByDesc('issueDate')
            ->orderByDesc('id')
            ->paginate(25);

        return response()->json([
            'success' => true,
            'invoices' => $invoices,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'user',
            'orders.price',
            'orders.menuItem',
        ]);

        $orders = $invoice->orders->map(function ($o) {
            return [
                'id' => $o->id,
                'orderDate' => $o->orderDate,
                'selectedOption' => $o->selectedOption,
                'orderStatus' => $o->orderStatus,
                'amount' => (float) ($o->price?->amount ?? 0),
                'menuItemName' => $o->menuItem?->name
                    ?? $o->menuItem?->menuItemName
                    ?? null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'invoice' => [
                'id' => $invoice->id,
                'invoiceNumber' => $invoice->invoiceNumber,
                'billingMonth' => $invoice->billingMonth,
                'issueDate' => $invoice->issueDate,
                'dueDate' => $invoice->dueDate,
                'totalAmount' => (float) $invoice->totalAmount,
                'invoiceStatus' => $invoice->invoiceStatus,
                'paymentMethod' => $invoice->paymentMethod,
                'user' => [
                    'id' => $invoice->user?->id,
                    'name' => $invoice->user?->name ?? null,
                    'email' => $invoice->user?->email ?? null,
                ],
                'orders' => $orders,
            ],
        ]);
    }


    private function generateInvoiceNumber(Carbon $monthStart): string
    {
        // Példa: INV-202602-000123
        $prefix = 'INV-' . $monthStart->format('Ym') . '-';
        $last = Invoice::where('invoiceNumber', 'like', $prefix.'%')
            ->orderBy('invoiceNumber', 'desc')
            ->value('invoiceNumber');

        $next = 1;
        if ($last) {
            $lastSeq = (int) substr($last, -6);
            $next = $lastSeq + 1;
        }
        return $prefix . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }
}
