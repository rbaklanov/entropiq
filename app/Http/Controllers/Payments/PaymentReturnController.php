<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where the payment page sends the buyer back. It only shows a message: Premium is granted by the payment
 * notification, never by this redirect.
 */
class PaymentReturnController extends Controller
{
    public function success(Request $request): RedirectResponse
    {
        $payment = Payment::find((int) $request->input('InvId'));

        $message = $payment?->status === PaymentStatus::Completed
            ? __('subscription.upgraded')
            : __('payments.processing');

        return redirect()->route('settings.subscription')->with('success', $message);
    }

    public function fail(): RedirectResponse
    {
        return redirect()->route('settings.subscription')->with('error', __('payments.failed'));
    }
}
