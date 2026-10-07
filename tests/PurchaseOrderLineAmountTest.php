<?php

namespace Tests;

use App\Contracts\Repositories\FinanceRecordRepositoryInterface;
use App\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Services\PurchaseOrderService;
use Illuminate\Validation\ValidationException;

class PurchaseOrderLineAmountTest extends TestCase
{
    public function test_direct_amount_is_used_without_quantity_or_rate(): void
    {
        $calculated = $this->service()->calculateLines([
            [
                'description' => 'Flat fee',
                'amount' => 1250.75,
            ],
        ]);

        $this->assertSame(1250.75, $calculated['items'][0]['amount']);
        $this->assertSame(0.0, $calculated['items'][0]['qty']);
        $this->assertSame(0.0, $calculated['items'][0]['rate']);
        $this->assertTrue($calculated['items'][0]['direct_amount']);
        $this->assertSame(1250.75, $calculated['subtotal']);
    }

    public function test_quantity_and_rate_are_still_used_when_no_direct_amount_is_sent(): void
    {
        $calculated = $this->service()->calculateLines([
            [
                'qty' => 2,
                'rate' => 150.25,
            ],
        ]);

        $this->assertSame(300.5, $calculated['items'][0]['amount']);
        $this->assertFalse($calculated['items'][0]['direct_amount']);
        $this->assertSame(300.5, $calculated['subtotal']);
    }

    public function test_zero_quantity_is_allowed_when_direct_amount_is_provided(): void
    {
        $this->assertNull($this->service()->validatePayload([
            'publisher_id' => 1,
            'finance_record_id' => 1,
            'orders' => [
                ['qty' => 0, 'rate' => 0, 'amount' => 1250],
            ],
        ]));
    }

    public function test_zero_quantity_is_rejected_when_direct_amount_is_missing(): void
    {
        try {
            $this->service()->validatePayload([
                'publisher_id' => 1,
                'finance_record_id' => 1,
                'orders' => [
                    ['qty' => 0, 'rate' => 1250],
                ],
            ]);
            $this->fail('Expected the order line to fail validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('orders.0.qty', $exception->errors());
        }
    }

    private function service(): PurchaseOrderService
    {
        return new class(
            \Mockery::mock(PurchaseOrderRepositoryInterface::class),
            \Mockery::mock(FinanceRecordRepositoryInterface::class)
        ) extends PurchaseOrderService {
            public function calculateLines(array $orders): array
            {
                return $this->calculate($orders, '06ABCDE1234F1Z5', [
                    'sgst' => 0,
                    'cgst' => 0,
                    'igst' => 0,
                ]);
            }

            public function validatePayload(array $payload): void
            {
                $this->validateOrders($payload);
            }
        };
    }
}
