<?php

namespace App\Exceptions;

use App\Models\MasterItem;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public ?MasterItem $masterItem = null,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if ($message === '' && $masterItem !== null) {
            $message = "Stok alat camping '{$masterItem->name}' tidak mencukupi.";
        }

        parent::__construct($message, $code, $previous);
    }
}
