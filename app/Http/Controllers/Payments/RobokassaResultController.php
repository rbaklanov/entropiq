<?php

namespace App\Http\Controllers\Payments;

use App\Actions\CompletePayment;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Integrations\Robokassa\RobokassaCredentials;
use App\Integrations\Robokassa\RobokassaSignature;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class RobokassaResultController extends Controller
{
    public function __invoke(Request $request, RobokassaCredentials $credentials, CompletePayment $completePayment): Response
    {
        $outSum = (string) $request->input('OutSum', '');
        $invoiceId = (string) $request->input('InvId', '');
        $signature = (string) $request->input('SignatureValue', '');

        if ($outSum === '' || ! ctype_digit($invoiceId) || $signature === '') {
            return response('bad request', 400);
        }

        if (! $this->signatureMatches($credentials, $signature, $outSum, $invoiceId)) {
            Log::warning('Robokassa ResultURL with an invalid signature', ['inv_id' => $invoiceId]);

            return response('bad sign', 400);
        }

        $payment = Payment::query()->where('provider', 'robokassa')->find((int) $invoiceId);

        if ($payment === null) {
            return response('unknown invoice', 404);
        }

        if (RobokassaSignature::kopecks($outSum) !== $payment->amount) {
            Log::error('Robokassa ResultURL amount does not match the payment', [
                'payment_id' => $payment->id,
                'expected' => $payment->amount,
                'received' => $outSum,
            ]);

            return response('bad amount', 400);
        }

        if ($payment->status !== PaymentStatus::Refunded) {
            $completePayment->execute($payment);
        }

        return response("OK{$payment->id}", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Test payments are signed with the test password #2, live ones and recurring charges with the live one.
     */
    private function signatureMatches(RobokassaCredentials $credentials, string $signature, string $outSum, string $invoiceId): bool
    {
        $robokassaSignature = new RobokassaSignature($credentials->hashAlgorithm);

        $passwords = array_filter([
            $credentials->password2,
            $credentials->testMode ? $credentials->testPassword2 : '',
        ], fn (string $password) => $password !== '');

        foreach ($passwords as $password) {
            if ($robokassaSignature->matches($signature, $outSum, $invoiceId, $password)) {
                return true;
            }
        }

        return false;
    }
}
