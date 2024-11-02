<?php 

namespace NtechServices\SubscriptionSystem\Enums;

enum BillingCycle: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';

    public function isValid(): bool
    {
        return in_array($this->value, self::cases());
    }
}
