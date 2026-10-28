<?php

namespace App\Exceptions;

use RuntimeException;

class FirebasePushException extends RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?string $firebaseErrorCode,
        public readonly string $providerMessage,
        string $notificationType,
    ) {
        $detail = $providerMessage !== '' ? ': '.mb_substr($providerMessage, 0, 350) : '';
        $code = $firebaseErrorCode ? ', FCM '.$firebaseErrorCode : '';

        parent::__construct(
            'Firebase rejected the '.$notificationType.' notification (HTTP '.$httpStatus.$code.')'.$detail,
        );
    }

    public function hasInvalidToken(): bool
    {
        return in_array($this->firebaseErrorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'], true);
    }
}
