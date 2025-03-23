<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function storePayment(Request $request, Invoice $invoice)
    {
        $payment = $invoice->payments()->create([
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'source' => $request->source,
            'user_id' => auth()->id(),
        ]);

        $remainingAmount = $invoice->total - $invoice->payments->sum('amount');
        $hasWarning = $request->amount > $remainingAmount;

        return response()->json([
            'success' => true,
            'message' => __('Payment added successfully.'),
            'hasWarning' => $hasWarning,
            'warningMessage' => $hasWarning ? __('Warning: The payment amount is greater than the remaining amount to be paid.') : null,
            'remainingAmount' => $remainingAmount
        ]);
    }
} 