<?php 

namespace NtechServices\SubscriptionSystem\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case CANCELED = 'canceled';
    case TRIALING = 'trialing';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
    case PENDING = 'pending';

    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'));
    }
}
