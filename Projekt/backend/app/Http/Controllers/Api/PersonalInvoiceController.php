<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PersonalInvoiceController extends Controller
{

    public function userInvoices(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = Invoice::where('user_id', $user->id)
                ->orderBy('billingMonth', 'desc')
                ->orderBy('created_at', 'desc');
            
            if ($request->has('year') && $request->year) {
                $query->whereYear('billingMonth', $request->year);
            }
            
            if ($request->has('status') && $request->status !== '') {
                $query->where('invoiceStatus', $request->status);
            }
            
            if ($request->has('search') && $request->search !== '') {
                $query->where('invoiceNumber', 'like', '%' . $request->search . '%');
            }
            
            $perPage = $request->get('per_page', 10);
            $invoices = $query->paginate($perPage);
            
            $formattedInvoices = $invoices->items();
            
            return response()->json([
                'success' => true,
                'data' => $formattedInvoices,
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
    

    public function invoiceOrders(Request $request, Invoice $invoice)
    {
        try {
            $user = Auth::user();
            
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
                        'selectedOption' => $order->selectedOption,
                        'orderStatus' => $order->orderStatus,
                        'amount' => (float) ($order->price?->amount ?? 0),
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
    

    public function downloadInvoice(Request $request, Invoice $invoice)
    {
        try {
            $user = Auth::user();
            
            if ($invoice->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hozzáférés megtagadva.'
                ], 403);
            }
            
     
            if ($invoice->invoiceStatus === 'Függőben lévő') {
                return response()->json([
                    'success' => false,
                    'message' => 'Csak generált számla tölthető le.'
                ], 400);
            }
            
            
            $invoice->load(['user', 'orders.price']);
            
        
            $pdf = Pdf::loadView('pdf.invoice', [
                'invoice' => $invoice
            ]);
            
    
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