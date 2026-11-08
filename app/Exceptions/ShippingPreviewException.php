<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;

class ShippingPreviewException extends \RuntimeException implements ShouldntReport
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }
}
