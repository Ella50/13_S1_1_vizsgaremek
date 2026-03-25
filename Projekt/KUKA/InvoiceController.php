<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->orderByDesc('billingMonth')
            ->get();

        return view('invoices.index', compact('invoices'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        $invoice->load([
            'user',
            'orders.menuItem',
            'orders.price',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function downloadPdf(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        $invoice->load([
            'user',
            'orders.menuItem',
            'orders.price',
        ]);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
        ])->setPaper('a4');

        $fileName = ($invoice->invoiceNumber ?? 'szamla') . '.pdf';

        return $pdf->download($fileName);
    }

    public function download($id)
    {
        $invoice = Invoice::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $pdf = PDF::loadView('pdf.invoice', compact('invoice'));

        return $pdf->download("szamla-{$invoice->invoiceNumber}.pdf");
    }

    private function authorizeInvoice(Request $request, Invoice $invoice): void
    {
        // User csak a saját számláját lássa
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
