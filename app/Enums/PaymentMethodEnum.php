<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum PaymentMethodEnum: string
{
    use HasEnumOptions;
    
    case CARD = 'card';
    case GCASH = 'gcash';
    case PAYMAYA = 'paymaya';
    case GRABPAY = 'grabpay';
}
