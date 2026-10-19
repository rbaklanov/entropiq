<?php

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use App\Exceptions\PaymentGatewayException;
use App\Integrations\Robokassa\Requests\CreateRefundRequest;
use App\Integrations\Robokassa\Requests\OperationStateRequest;
use App\Integrations\Robokassa\Requests\RecurringPaymentRequest;
use App\Integrations\Robokassa\RobokassaConnector;
use App\Integrations\Robokassa\RobokassaCredentials;
use App\Integrations\Robokassa\RobokassaRefundConnector;
use App\Integrations\Robokassa\RobokassaSignature;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\RobokassaGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(RefreshDatabase::class);

function robokassaCredentials(bool $testMode = false, bool $sendReceipt = false): RobokassaCredentials
{
    return new RobokassaCredentials(
        merchantLogin: 'entropiq',
        password1: 'live-pass-1',
        password2: 'live-pass-2',
        password3: 'refund-pass-3',
        testPassword1: 'test-pass-1',
        testPassword2: 'test-pass-2',
        testMode: $testMode,
        sendReceipt: $sendReceipt,
    );
}

function robokassaGateway(?MockClient $mockClient = null, bool $testMode = false, bool $sendReceipt = false): RobokassaGateway
{
    $connector = new RobokassaConnector;
    $refundConnector = new RobokassaRefundConnector;

    if ($mockClient !== null) {
        $connector->withMockClient($mockClient);
        $refundConnector->withMockClient($mockClient);
    }

    return new RobokassaGateway(robokassaCredentials($testMode, $sendReceipt), $connector, $refundConnector);
}

/** @return array<string, string> */
function checkoutQuery(string $url): array
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return $query;
}

function operationStateXml(int $resultCode = 0, string $opKey = 'op-key-123'): string
{
    return <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <OperationStateResponse xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://merchant.roboxchange.com/WebService/">
          <Result><Code>{$resultCode}</Code></Result>
          <State><Code>100</Code><RequestDate>2026-10-19T12:00:00+03:00</RequestDate><StateDate>2026-10-19T11:00:00+03:00</StateDate></State>
          <Info><IncCurrLabel>BankCardPSR</IncCurrLabel><IncSum>99.00</IncSum><IncAccount>220000******0000</IncAccount><PaymentMethod><Code>BankCard</Code><Description>Банковская карта</Description></PaymentMethod><OutCurrLabel>BankCardPSR</OutCurrLabel><OutSum>96.00</OutSum><OpKey>{$opKey}</OpKey><BankCardRRN>123456789012</BankCardRRN></Info>
        </OperationStateResponse>
        XML;
}

describe('signature helpers', function () {
    it('hashes colon separated parts in upper case and compares case insensitively', function () {
        $signature = new RobokassaSignature;

        expect($signature->make('demo', '100.00', '1', 'pass'))->toBe(strtoupper(md5('demo:100.00:1:pass')))
            ->and($signature->matches(md5('demo:100.00:1:pass'), 'demo', '100.00', '1', 'pass'))->toBeTrue()
            ->and($signature->matches('wrong', 'demo', '100.00', '1', 'pass'))->toBeFalse();
    });

    it('converts kopecks to the Robokassa amount format and back', function () {
        expect(RobokassaSignature::amount(9900))->toBe('99.00')
            ->and(RobokassaSignature::amount(59000))->toBe('590.00')
            ->and(RobokassaSignature::kopecks('99.000000'))->toBe(9900)
            ->and(RobokassaSignature::kopecks('590'))->toBe(59000);
    });
});

describe('checkout url', function () {
    it('builds a signed payment link with the payment id as invoice number', function () {
        $payment = Payment::factory()->for(User::factory())->pending()->create(['amount' => 9900, 'plan' => SubscriptionPlan::Monthly, 'provider' => 'robokassa']);

        $url = robokassaGateway()->checkoutUrl($payment);
        $query = checkoutQuery($url);

        expect($url)->toStartWith(RobokassaGateway::PAYMENT_URL.'?')
            ->and($query['MerchantLogin'])->toBe('entropiq')
            ->and($query['OutSum'])->toBe('99.00')
            ->and($query['InvId'])->toBe((string) $payment->id)
            ->and($query['SignatureValue'])->toBe(strtoupper(md5("entropiq:99.00:{$payment->id}:live-pass-1")))
            ->and($query['Description'])->toBe('Подписка Entropiq Premium, 1 месяц')
            ->and($query)->not->toHaveKeys(['IsTest', 'Recurring', 'Receipt', 'Email']);
    });

    it('signs test payments with the test password and marks them as test', function () {
        $payment = Payment::factory()->for(User::factory())->pending()->create(['amount' => 59000, 'plan' => SubscriptionPlan::Yearly, 'provider' => 'robokassa']);

        $query = checkoutQuery(robokassaGateway(testMode: true)->checkoutUrl($payment));

        expect($query['IsTest'])->toBe('1')
            ->and($query['OutSum'])->toBe('590.00')
            ->and($query['SignatureValue'])->toBe(strtoupper(md5("entropiq:590.00:{$payment->id}:test-pass-1")));
    });

    it('asks for a recurring parent payment and passes the confirmed email', function () {
        $user = User::factory()->withVerifiedEmail('buyer@example.com')->create();
        $payment = Payment::factory()->for($user)->pending()->create(['amount' => 9900, 'plan' => SubscriptionPlan::Monthly, 'is_recurring' => true]);

        $query = checkoutQuery(robokassaGateway()->checkoutUrl($payment));

        expect($query['Recurring'])->toBe('true')
            ->and($query['Email'])->toBe('buyer@example.com')
            ->and($query['SignatureValue'])->toBe(strtoupper(md5("entropiq:99.00:{$payment->id}:live-pass-1")));
    });

    it('adds the receipt to the link and to the signature when enabled', function () {
        $payment = Payment::factory()->for(User::factory())->pending()->create(['amount' => 9900, 'plan' => SubscriptionPlan::Monthly]);

        $query = checkoutQuery(robokassaGateway(sendReceipt: true)->checkoutUrl($payment));
        $receipt = json_decode(urldecode($query['Receipt']), true);

        expect($receipt['items'][0])->toMatchArray(['quantity' => 1, 'sum' => 99, 'tax' => 'none', 'payment_object' => 'service'])
            ->and($query['SignatureValue'])->toBe(strtoupper(md5("entropiq:99.00:{$payment->id}:{$query['Receipt']}:live-pass-1")));
    });
});

describe('recurring charge', function () {
    it('posts the child invoice with the parent invoice and accepts OK with the invoice number', function () {
        $user = User::factory()->create();
        $parent = Payment::factory()->for($user)->create(['amount' => 9900, 'is_recurring' => true]);
        $child = Payment::factory()->for($user)->pending()->create(['amount' => 9900, 'plan' => SubscriptionPlan::Monthly, 'parent_payment_id' => $parent->id]);

        $mockClient = new MockClient([RecurringPaymentRequest::class => MockResponse::make("OK{$child->id}", 200, ['Content-Type' => 'text/plain'])]);

        robokassaGateway($mockClient)->chargeRecurring($child);

        $mockClient->assertSent(function (RecurringPaymentRequest $request, $response) use ($child, $parent) {
            $body = $response->getPendingRequest()->body()->all();

            return $response->getPendingRequest()->getUrl() === 'https://auth.robokassa.ru/Merchant/Recurring'
                && $body['InvoiceID'] === (string) $child->id
                && $body['PreviousInvoiceID'] === (string) $parent->id
                && $body['OutSum'] === '99.00'
                && $body['SignatureValue'] === strtoupper(md5("entropiq:99.00:{$child->id}:live-pass-1"));
        });
    });

    it('signs recurring charges with the live password even in test mode', function () {
        $user = User::factory()->create();
        $parent = Payment::factory()->for($user)->create();
        $child = Payment::factory()->for($user)->pending()->create(['amount' => 9900, 'parent_payment_id' => $parent->id]);

        $mockClient = new MockClient([RecurringPaymentRequest::class => MockResponse::make("OK{$child->id}")]);

        robokassaGateway($mockClient, testMode: true)->chargeRecurring($child);

        $mockClient->assertSent(fn (RecurringPaymentRequest $request) => $request->signature === strtoupper(md5("entropiq:99.00:{$child->id}:live-pass-1")));
    });

    it('fails when Robokassa answers with an error', function () {
        $user = User::factory()->create();
        $parent = Payment::factory()->for($user)->create();
        $child = Payment::factory()->for($user)->pending()->create(['amount' => 9900, 'parent_payment_id' => $parent->id]);

        $mockClient = new MockClient([RecurringPaymentRequest::class => MockResponse::make([
            'type' => 'https://tools.ietf.org/html/rfc9110#section-15.6.1',
            'title' => 'Internal error',
            'status' => 500,
            'detail' => 'Recurring: Internal ERROR occurred',
            'instance' => 'Recurring',
        ], 500, ['Content-Type' => 'application/problem+json'])]);

        robokassaGateway($mockClient)->chargeRecurring($child);
    })->throws(PaymentGatewayException::class);

    it('refuses a renewal without a parent payment', function () {
        $child = Payment::factory()->for(User::factory())->pending()->create(['amount' => 9900]);

        robokassaGateway(new MockClient([]))->chargeRecurring($child);
    })->throws(PaymentGatewayException::class, 'has no parent payment');
});

describe('refund', function () {
    it('gets the operation key, sends a JWT signed with password 3 and returns the request id', function () {
        $payment = Payment::factory()->for(User::factory())->create(['amount' => 9900, 'provider_payment_id' => null]);

        $mockClient = new MockClient([
            OperationStateRequest::class => MockResponse::make(operationStateXml(), 200, ['Content-Type' => 'text/xml']),
            CreateRefundRequest::class => MockResponse::make(['success' => true, 'message' => null, 'requestId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6']),
        ]);

        $requestId = robokassaGateway($mockClient)->refund($payment, 5000);

        expect($requestId)->toBe('3fa85f64-5717-4562-b3fc-2c963f66afa6')
            ->and($payment->fresh()->provider_payment_id)->toBe('op-key-123');

        $stateRequest = $mockClient->findResponseByRequest(OperationStateRequest::class)->getPendingRequest()->getRequest();

        expect($stateRequest->signature)->toBe(strtoupper(md5("entropiq:{$payment->id}:live-pass-2")));

        $mockClient->assertSent(function (CreateRefundRequest $request, $response) {
            [$header, $payload, $signature] = explode('.', $request->jwt);
            $decode = fn (string $part) => json_decode(base64_decode(strtr($part, '-_', '+/')), true);
            $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', "{$header}.{$payload}", 'refund-pass-3', true)), '+/', '-_'), '=');

            return $decode($header) === ['typ' => 'JWT', 'alg' => 'HS256']
                && $decode($payload) === ['OpKey' => 'op-key-123', 'RefundSum' => 50]
                && $signature === $expected
                && $response->getPendingRequest()->body()->all() === json_encode($request->jwt)
                && $response->getPendingRequest()->getUrl() === 'https://services.robokassa.ru/RefundService/Refund/Create';
        });
    });

    it('reuses a stored operation key', function () {
        $payment = Payment::factory()->for(User::factory())->create(['amount' => 9900, 'provider_payment_id' => 'stored-key']);

        $mockClient = new MockClient([
            CreateRefundRequest::class => MockResponse::make(['success' => true, 'message' => null, 'requestId' => 'req-1']),
        ]);

        robokassaGateway($mockClient)->refund($payment, 9900);

        $mockClient->assertNotSent(OperationStateRequest::class);
        $mockClient->assertSentCount(1);
    });

    it('fails when the refund API rejects the request', function () {
        $payment = Payment::factory()->for(User::factory())->create(['provider_payment_id' => 'stored-key']);

        $mockClient = new MockClient([
            CreateRefundRequest::class => MockResponse::make(['success' => false, 'message' => 'NotEnoughOperationFunds', 'requestId' => null], 200),
        ]);

        robokassaGateway($mockClient)->refund($payment, 9900);
    })->throws(PaymentGatewayException::class, 'NotEnoughOperationFunds');

    it('fails when Robokassa has no operation for the invoice', function () {
        $payment = Payment::factory()->for(User::factory())->create(['provider_payment_id' => null]);

        $mockClient = new MockClient([
            OperationStateRequest::class => MockResponse::make(operationStateXml(resultCode: 3, opKey: ''), 200),
        ]);

        robokassaGateway($mockClient)->refund($payment, 9900);
    })->throws(PaymentGatewayException::class, 'result code 3');

    it('keeps payment status untouched on a gateway failure', function () {
        $payment = Payment::factory()->for(User::factory())->create(['provider_payment_id' => 'stored-key']);

        $mockClient = new MockClient([CreateRefundRequest::class => MockResponse::make('not json', 502)]);

        try {
            robokassaGateway($mockClient)->refund($payment, 9900);
        } catch (PaymentGatewayException) {
        }

        expect($payment->fresh()->status)->toBe(PaymentStatus::Completed);
    });
});
