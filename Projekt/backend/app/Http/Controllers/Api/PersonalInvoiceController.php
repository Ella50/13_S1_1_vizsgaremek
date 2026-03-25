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
            if ($request->has('year') && $request->year) {
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
            
            // Adatok formázása a frontend elvárásai szerint
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
            \Log::error('Számlák betöltési hiba: ' . $e->getMessage());
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
            \Log::error('Rendelések betöltési hiba: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a rendelések betöltése során.',
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
            $invoice->load(['user', 'orders.price']);
            
            // Bruttó összeg számítása (27% ÁFA)
            $grossAmount = $invoice->totalAmount * 1.27;
            
            $data = [
                'invoice' => $invoice,
                'user' => $invoice->user,
                'grossAmount' => $grossAmount,
                'company' => [
                    'name' => 'Étkeztető Rendszer',
                    'address' => '1234 Budapest, Példa utca 1.',
                    'tax_number' => '12345678-1-23',
                    'bank_account' => '12345678-12345678-12345678',
                    'phone' => '+36 1 234 5678',
                    'email' => 'info@etkezteto.hu'
                ]
            ];
            
            // PDF generálása - először ellenőrizzük, hogy létezik-e a view
            if (!view()->exists('invoices.pdf')) {
                // Ha nincs meg a view, készítsünk egy egyszerűbb verziót
                $html = $this->generateSimplePdfHtml($data);
                $pdf = Pdf::loadHTML($html);
            } else {
                $pdf = Pdf::loadView('invoices.pdf', $data);
            }
            
            // PDF letöltése
            return $pdf->download('szamla_' . $invoice->invoiceNumber . '.pdf');
            
        } catch (\Exception $e) {
            \Log::error('PDF letöltési hiba: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a számla letöltése során.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Egyszerű PDF HTML generálása, ha nincs meg a view
     */
    private function generateSimplePdfHtml($data)
    {
        $invoice = $data['invoice'];
        $user = $data['user'];
        $grossAmount = $data['grossAmount'];
        $company = $data['company'];
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>SZÁMLA - ' . $invoice->invoiceNumber . '</title>
            <style>
                body {
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 12px;
                    margin: 40px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .company-info {
                    margin-bottom: 20px;
                }
                .invoice-title {
                    font-size: 24px;
                    font-weight: bold;
                    text-align: center;
                    margin: 20px 0;
                }
                .invoice-details {
                    margin-bottom: 30px;
                    border: 1px solid #ddd;
                    padding: 15px;
                }
                .invoice-details table {
                    width: 100%;
                }
                .invoice-details td {
                    padding: 5px;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .items-table th, .items-table td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                .items-table th {
                    background-color: #f5f5f5;
                }
                .total {
                    text-align: right;
                    font-size: 14px;
                    font-weight: bold;
                    margin-top: 20px;
                }
                .footer {
                    margin-top: 50px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>' . $company['name'] . '</h2>
                <p>' . $company['address'] . '<br>
                Adószám: ' . $company['tax_number'] . '<br>
                Bankszámlaszám: ' . $company['bank_account'] . '</p>
            </div>
            
            <div class="invoice-title">
                SZÁMLA
            </div>
            
            <div class="invoice-details">
                <table>
                    <tr>
                        <td><strong>Számlaszám:</strong></td>
                        <td>' . $invoice->invoiceNumber . '</td>
                        <td><strong>Kelt:</strong></td>
                        <td>' . $invoice->issueDate . '</td>
                    </tr>
                    <tr>
                        <td><strong>Teljesítés:</strong></td>
                        <td>' . $invoice->billingMonth . '</td>
                        <td><strong>Fizetési határidő:</strong></td>
                        <td>' . $invoice->dueDate . '</td>
                    </tr>
                    <tr>
                        <td><strong>Fizetés módja:</strong></td>
                        <td>' . ($invoice->paymentMethod ?? 'Bankkártya') . '</td>
                        <td><strong>Státusz:</strong></td>
                        <td>' . $invoice->invoiceStatus . '</td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h3>Számla címe:</h3>
                <p>
                    ' . $user->firstName . ' ' . $user->lastName . '<br>
                    ' . ($user->address ?? '') . '
                </p>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Megnevezés</th>
                        <th>Mennyiség</th>
                        <th>Egységár</th>
                        <th>Nettó érték</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Étkezési szolgáltatás - ' . date('Y.m', strtotime($invoice->billingMonth)) . ' hónap</td>
                        <td>1</td>
                        <td>' . number_format($invoice->totalAmount, 0, ',', ' ') . ' Ft</td>
                        <td>' . number_format($invoice->totalAmount, 0, ',', ' ') . ' Ft</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="total">
                <p>Nettó összeg: ' . number_format($invoice->totalAmount, 0, ',', ' ') . ' Ft</p>
                <p>ÁFA (27%): ' . number_format($invoice->totalAmount * 0.27, 0, ',', ' ') . ' Ft</p>
                <p><strong>Bruttó összeg: ' . number_format($grossAmount, 0, ',', ' ') . ' Ft</strong></p>
            </div>
            
            <div class="footer">
                <p>Kelt: ' . date('Y.m.d') . '</p>
                <p>A számla elektronikus úton készült, aláírás nélkül is érvényes.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}