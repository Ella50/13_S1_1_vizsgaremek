<?php
// app/Http/Controllers/Api/InvoiceController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Felhasználó számláinak listázása
     */
    public function userInvoices(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = Invoice::where('user_id', $user->id)
                ->orderBy('billingMonth', 'desc')
                ->orderBy('created_at', 'desc');
            
            // Év szerinti szűrés
            if ($request->has('year')) {
                $query->whereYear('billingMonth', $request->year);
            }
            
            // Státusz szerinti szűrés
            if ($request->has('status') && $request->status !== '') {
                $query->where('invoiceStatus', $request->status);
            }
            
            // Keresés számlaszám alapján
            if ($request->has('search') && $request->search !== '') {
                $query->where('invoiceNumber', 'like', '%' . $request->search . '%');
            }
            
            // Lapozás
            $perPage = $request->get('per_page', 10);
            $invoices = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $invoices->items(),
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
                'from' => $invoices->firstItem(),
                'to' => $invoices->lastItem(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a számlák betöltése során.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Számlához tartozó rendelések
     */
    public function invoiceOrders(Request $request, Invoice $invoice)
    {
        try {
            $user = Auth::user();
            
            // Ellenőrizzük, hogy a számla a felhasználóhoz tartozik-e
            if ($invoice->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hozzáférés megtagadva.'
                ], 403);
            }
            
            $orders = Order::where('invoice_id', $invoice->id)
                ->with(['menuItem', 'price'])
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'orderDate' => $order->orderDate,
                        'menu_item_name' => $order->menuItem ? $order->menuItem->name : 'Ismeretlen',
                        'selectedOption' => $order->selectedOption,
                        'price_category' => $order->price ? $order->price->priceCategory : 'Normál',
                        'price_amount' => $order->price ? $order->price->amount : 0,
                        'orderStatus' => $order->orderStatus
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $orders
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a rendelések betöltése során.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Számla PDF előnézet
     */
    public function previewInvoice(Request $request, Invoice $invoice)
    {
        try {
            $user = Auth::user();
            
            // Ellenőrizzük, hogy a számla a felhasználóhoz tartozik-e
            if ($invoice->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hozzáférés megtagadva.'
                ], 403);
            }
            
            // Csak generált vagy fizetett számla tekinthető meg
            if ($invoice->invoiceStatus === 'Függőben lévő') {
                return response()->json([
                    'success' => false,
                    'message' => 'Csak generált számla tekinthető meg.'
                ], 400);
            }
            
            // Betöltjük a kapcsolódó adatokat
            $invoice->load(['user', 'orders.menuItem', 'orders.price']);
            
            $data = [
                'invoice' => $invoice,
                'user' => $invoice->user,
                'company' => [
                    'name' => 'Étkeztető Rendszer',
                    'address' => '1234 Budapest, Példa utca 1.',
                    'tax_number' => '12345678-1-23',
                    'bank_account' => '12345678-12345678-12345678',
                    'phone' => '+36 1 234 5678',
                    'email' => 'info@etkezteto.hu'
                ]
            ];
            
            // PDF generálása
            $pdf = Pdf::loadView('invoices.pdf', $data);
            
            // PDF streamelése
            return $pdf->stream('szamla_' . $invoice->invoiceNumber . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a számla előnézetének betöltése során.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Számla letöltése PDF-ként
     */
    public function downloadInvoice(Request $request, Invoice $invoice)
    {
        try {
            $user = Auth::user();
            
            // Ellenőrizzük, hogy a számla a felhasználóhoz tartozik-e
            if ($invoice->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hozzáférés megtagadva.'
                ], 403);
            }
            
            // Csak generált vagy fizetett számla tölthető le
            if ($invoice->invoiceStatus === 'Függőben lévő') {
                return response()->json([
                    'success' => false,
                    'message' => 'Csak generált számla tölthető le.'
                ], 400);
            }
            
            // Betöltjük a kapcsolódó adatokat
            $invoice->load(['user', 'orders.menuItem', 'orders.price']);
            
            $data = [
                'invoice' => $invoice,
                'user' => $invoice->user,
                'company' => [
                    'name' => 'Étkeztető Rendszer',
                    'address' => '1234 Budapest, Példa utca 1.',
                    'tax_number' => '12345678-1-23',
                    'bank_account' => '12345678-12345678-12345678',
                    'phone' => '+36 1 234 5678',
                    'email' => 'info@etkezteto.hu'
                ]
            ];
            
            // PDF generálása
            $pdf = Pdf::loadView('invoices.pdf', $data);
            
            // PDF letöltése
            return $pdf->download('szamla_' . $invoice->invoiceNumber . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a számla letöltése során.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}