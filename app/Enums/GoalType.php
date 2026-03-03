<?php

namespace App\Enums;

enum GoalType: string
{
    case SafetyNet = 'safety_net';
    case LargePurchase = 'large_purchase';
    case Travel = 'travel';
    case Car = 'car';
    case Apartment = 'apartment';
    case Education = 'education';
    case Other = 'other';
}
