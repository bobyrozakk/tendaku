<?php

namespace Tests\Unit;

use App\Services\PhoneNumberNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNumberNormalizerTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function phoneProvider(): array
    {
        return [
            'format lokal 08' => ['081234567890', '6281234567890'],
            'format internasional +62' => ['+6281234567890', '6281234567890'],
            'format internasional 62' => ['6281234567890', '6281234567890'],
            'format tanpa nol 8' => ['81234567890', '6281234567890'],
            'dengan spasi dan tanda hub' => ['+62 812-3456-7890', '6281234567890'],
            'nomor tidak valid' => ['12345', null],
            'nomor kosong' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('phoneProvider')]
    public function test_it_normalizes_indonesian_phone_numbers(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumberNormalizer::normalize($input));
    }
}
