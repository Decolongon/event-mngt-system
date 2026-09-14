<?php

namespace App\Services;

use App\Enums\PaymentMethodEnum;
use Illuminate\Support\Facades\Auth;
use Luigel\Paymongo\Facades\Paymongo;

class PaymentIntentService
{
    /**
     * Map local enum value to PayMongo API value.
     */
    public static function toPaymongoType(string $type): string
    {
        return $type === PaymentMethodEnum::GRABPAY->value ? 'grab_pay' : $type;
    }

    public static function toLocalType(string $paymongoType): string
    {
        return $paymongoType === 'grab_pay' ? PaymentMethodEnum::GRABPAY->value : $paymongoType;
    }

    public function paymentCreateIntent(float $amount, ?array $allowedMethods = null)
    {
        $allowed = $allowedMethods ?? PaymentMethodEnum::values();
        $paymongoAllowed = array_map(fn (string $v) => self::toPaymongoType($v), $allowed);

        return Paymongo::paymentIntent()->create([
            'amount' => $amount,
            'currency' => 'PHP',
            'payment_method_allowed' => $paymongoAllowed,
            'payment_method_options' => [
                'card' => ['request_three_d_secure' => 'any'],
            ],
            'description' => 'Event management payment.',
            'statement_descriptor' => config('app.name', 'Event Payment'),
        ]);
    }

    public function paymentMethod(array $data)
    {
        if (($data['type'] ?? null) === PaymentMethodEnum::CARD->value) {
            return Paymongo::paymentMethod()->create([
                'type' => 'card',
                'details' => [
                    'card_number' => $data['card_number'],
                    'exp_month' => (int) $data['exp_month'],
                    'exp_year' => (int) $data['exp_year'],
                    'cvc' => $data['cvc'],
                ],
                'billing' => $this->billingPayload($data['billing'] ?? null),
            ]);
        }

        if (in_array($data['type'] ?? null, [PaymentMethodEnum::GCASH->value, PaymentMethodEnum::PAYMAYA->value, PaymentMethodEnum::GRABPAY->value], true)) {
            $type = self::toPaymongoType($data['type']);

            return Paymongo::paymentMethod()->create([
                'type' => $type,
                'billing' => $this->billingPayload($data['billing'] ?? null),
            ]);
        }

        throw new \InvalidArgumentException('Unsupported payment method type: '.($data['type'] ?? 'null'));
    }

    public function attachIntent($intent, string $paymentMethodId, ?string $returnUrl = null)
    {
        // $intent is a PaymentIntent model; ->attach() is defined on the model
        return $intent->attach($paymentMethodId, $returnUrl);
    }

    /**
     * Create intent, method and attach in one flow.
     * Returns attached intent. Throws on PayMongo error.
     */
    public function createAndAttach(float $amount, string $type, array $cardDetails = [], ?string $returnUrl = null)
    {
        $intent = $this->paymentCreateIntent($amount);

        $methodData = ['type' => $type, 'billing' => $this->defaultBilling()];

        if ($type === PaymentMethodEnum::CARD->value) {
            $methodData = array_merge($methodData, [
                'card_number' => $cardDetails['card_number'],
                'exp_month' => $cardDetails['exp_month'],
                'exp_year' => $cardDetails['exp_year'],
                'cvc' => $cardDetails['cvc'],
            ]);
        }

        $paymentMethod = $this->paymentMethod($methodData);

        // E-wallets require return_url for redirect
        $needsReturnUrl = in_array($type, [PaymentMethodEnum::GCASH->value, PaymentMethodEnum::PAYMAYA->value, PaymentMethodEnum::GRABPAY->value], true);
        $url = $needsReturnUrl ? ($returnUrl ?? route('attendee.dashboard')) : null;

        return $this->attachIntent($intent, $paymentMethod->id, $url);
    }

    protected function billingPayload(?array $override = null): array
    {
        if ($override !== null) {
            return $override;
        }

        return $this->defaultBilling();
    }

    protected function defaultBilling(): array
    {
        $user = Auth::user();

        return [
            'name' => $user?->name ?? 'Test Customer',
            'email' => $user?->email ?? 'test@example.com',
            'phone' => $user?->phone ?? '9000000000',
            'address' => [
                'line1' => 'Test Address',
                'city' => 'Manila',
                'state' => 'NCR',
                'postal_code' => '1000',
                'country' => 'PH',
            ],
        ];
    }
}
