<?php

namespace Tests\Unit\Shared;

use App\Shared\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('canonicalProvider')]
    public function test_it_canonicalizes_phone_numbers_to_e164(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::canonicalize($input));
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function canonicalProvider(): array
    {
        return [
            'blank' => ['', null],
            'null-like spaces' => ['   ', null],
            'already e164' => ['+15551234567', '+15551234567'],
            'formatted us' => ['+1 (555) 123-4567', '+15551234567'],
            '00 prefix' => ['0015551234567', '+15551234567'],
            'leading zero after plus' => ['+01 555 123 4567', '+15551234567'],
            'missing plus kept' => ['15551234567', '15551234567'],
            'letters kept for rejection' => ['+1-555-CALL', '+1-555-CALL'],
        ];
    }

    public function test_it_accepts_valid_e164_numbers(): void
    {
        $this->assertTrue(PhoneNumber::isE164('+15551234567'));
        $this->assertTrue(PhoneNumber::isE164('+8801712345678'));
        $this->assertFalse(PhoneNumber::isE164('15551234567'));
        $this->assertFalse(PhoneNumber::isE164('+0123'));
        $this->assertFalse(PhoneNumber::isE164('+1abc'));
        $this->assertFalse(PhoneNumber::isE164(null));
    }

    public function test_store_returns_null_for_blank_values(): void
    {
        $this->assertNull(PhoneNumber::store(''));
        $this->assertNull(PhoneNumber::store('   '));
        $this->assertNull(PhoneNumber::store(null));
        $this->assertSame('+15551234567', PhoneNumber::store('+1 (555) 123-4567'));
    }
}
