<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            'search' => ['nullable', 'string', 'max:100'],  
        ]);

        $q = Invoice::query()->with('user');

        // Hónap szűrés
        if ($request->filled('month')) {
            $monthStart = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()->toDateString();
            $q->whereDate('billingMonth', $monthStart);
        }

        // Keresés név vagy email alapján
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $q->whereHas('user', function($query) use ($searchTerm) {
                $query->where('firstName', 'like', "%{$searchTerm}%")
                    ->orWhere('lastName', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
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
                    'firstName' => $invoice->user?->firstName ?? null,
                    'lastName' => $invoice->user?->lastName ?? null,
                    'email' => $invoice->user?->email ?? null,
                ],
                'orders' => $orders,
            ],
        ]);
    }

    /**
     * Számla fizetettre állítása
     */
    public function markAsPaid($invoice)
    {
        try {
            $invoice = Invoice::findOrFail($invoice);
            
            // Ellenőrizzük, hogy nem már fizetett-e
            if ($invoice->invoiceStatus === 'Fizetve') {
                return response()->json([
                    'success' => false,
                    'message' => 'A számla már fizetett státuszban van.'
                ], 400);
            }
            
            // Státusz frissítése - használd a helyes mezőneveket
            $invoice->invoiceStatus = 'Fizetve';
            $invoice->paidAt = now();  // paidAt, nem paid_at
            $invoice->save();
            
            // Naplózás
            Log::info('Számla fizetettre állítva', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoiceNumber,
                'admin_id' => auth()->id()
            ]);
            
            // Frissített számla visszaadása a kapcsolatokkal
            $invoice->load(['user', 'orders']);
            
            return response()->json([
                'success' => true,
                'message' => 'Számla sikeresen fizetettre állítva.',
                'invoice' => $invoice
            ]);
            
        } catch (\Exception $e) {
            Log::error('Hiba a számla fizetettre állítása közben', [
                'invoice_id' => $invoice ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a számla frissítése közben: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAsUnpaid($invoice)
    {
         try {
            // Távolítsd el az output buffert és a BOM-ot
            if (ob_get_length()) ob_clean();
            
            $invoice = Invoice::findOrFail($invoice);
            
            if ($invoice->invoiceStatus !== 'Fizetve') {
                return response()->json([
                    'success' => false,
                    'message' => 'Csak fizetett számla állítható vissza.'
                ], 400);
            }
            
            $invoice->invoiceStatus = 'Generálva';
            $invoice->paidAt = null;
            $invoice->save();
            
            Log::info('Számla visszaállítva Generálva státuszra', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoiceNumber,
                'admin_id' => auth()->id()
            ]);
            
            $invoice->load(['user', 'orders']);
            
            return response()->json([
                'success' => true,
                'message' => 'Számla sikeresen visszaállítva.',
                'invoice' => $invoice
            ]);
            
        } catch (\Exception $e) {
            Log::error('Hiba a számla visszaállítása közben', [
                'invoice_id' => $invoice ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt: ' . $e->getMessage()
            ], 500);
        }
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