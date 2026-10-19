<?php

namespace App\Http\Controllers\Payments;

use App\Actions\CompletePayment;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FakeCheckoutController extends Controller
{
    public function __invoke(Request $request, Payment $payment, CompletePayment $completePayment): RedirectResponse
    {
        abort_unless(config('services.payments.driver') === 'fake', 404);
        abort_unless($payment->user_id === $request->user()?->id, 403);

        $completePayment->execute($payment);

        return redirect()->route('payments.success', ['InvId' => $payment->id]);
    }
}
