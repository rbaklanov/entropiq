<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Free = 'free';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
