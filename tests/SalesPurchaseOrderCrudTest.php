<?php

namespace Tests;

use App\Http\Resources\SalesPurchaseOrderResource;
use App\Models\SalesPurchaseOrder;
use App\Models\SalesPurchaseOrderItem;
use App\Services\SalesPurchaseOrderService;

class SalesPurchaseOrderCrudTest extends TestCase
{
    /** @var array<int, int> */
    protected array $orderIds = [];

    protected function tearDown(): void
    {
        if (!empty($this->orderIds)) {
            SalesPurchaseOrderItem::withTrashed()->whereIn('sales_purchase_order_id', $this->orderIds)->forceDelete();
            SalesPurchaseOrder::withTrashed()->whereIn('id', $this->orderIds)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_sales_purchase_order_full_crud_workflow(): void
    {
        /** @var SalesPurchaseOrderService $service */
        $service = app(SalesPurchaseOrderService::class);

        // 1. Create
        $order = $service->create([
            'client_name' => 'Apex Enterprises',
            'company_name' => 'Apex Media Group',
            'city' => 'Delhi',
            'sgst_rate' => 9,
            'cgst_rate' => 9,
            'orders' => [
                [
                    'order_name' => 'Hoarding Connaught Place',
                    'description' => 'Unipole display',
                    'hsn_sac' => '998314',
                    'city' => 'Delhi',
                    'slot' => 2,
                    'rate' => 50000,
                ],
                [
                    'order_name' => 'Airport Pillar',
                    'description' => 'Terminal 3 departure',
                    'hsn_sac' => '998314',
                    'city' => 'Delhi',
                    'slot' => 1,
                    'rate' => 40000,
                ],
            ],
        ]);

        $this->orderIds[] = $order->id;

        $this->assertNotNull($order->id);
        $this->assertStringStartsWith('PO/Client/', $order->po_number);
        $this->assertEquals(140000.0, (float) $order->subtotal);
        $this->assertEquals(12600.0, (float) $order->sgst_amount);
        $this->assertEquals(12600.0, (float) $order->cgst_amount);
        $this->assertEquals(25200.0, (float) $order->tax_amount);
        $this->assertEquals(165200.0, (float) $order->total_amount);
        $this->assertCount(2, $order->items);

        // 2. Read / List
        $list = $service->list(15, ['search' => 'Apex Enterprises']);
        $this->assertGreaterThanOrEqual(1, $list->total());

        // 3. Find
        $found = $service->find($order->id);
        $this->assertNotNull($found);
        $this->assertEquals('Apex Enterprises', $found->client_name);

        // 4. Update
        $updated = $service->update($order->id, [
            'client_name' => 'Apex Global Enterprises',
            'orders' => [
                [
                    'order_name' => 'Hoarding Connaught Place Updated',
                    'slot' => 1,
                    'rate' => 60000,
                ],
            ],
        ]);

        $this->assertNotNull($updated);
        $this->assertEquals('Apex Global Enterprises', $updated->client_name);
        $this->assertEquals(60000.0, (float) $updated->subtotal);
        $this->assertCount(1, $updated->items);

        // 5. Resource transformation
        $resource = new SalesPurchaseOrderResource($updated);
        $data = $resource->toArray(request());
        $this->assertEquals($updated->id, $data['id']);
        $this->assertEquals($updated->po_number, $data['po_number']);
        $this->assertEquals('Apex Global Enterprises', $data['client_name']);
        $this->assertArrayHasKey('po_file_url', $data);
        $this->assertArrayNotHasKey('pdf_path', $data);

        // 6. Delete
        $deleted = $service->delete($order->id);
        $this->assertTrue($deleted);
        $this->assertNull($service->find($order->id));
    }
}
