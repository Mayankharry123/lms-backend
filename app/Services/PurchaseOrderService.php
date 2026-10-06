<?php

/**
 * PurchaseOrder Service
 * -----------------------------------------
 * Creates a purchase order from a publisher id and order lines, then renders the PDF.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Services;

use App\Contracts\Repositories\FinanceRecordRepositoryInterface;
use App\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\AmountInWords;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mpdf\Mpdf;
use Throwable;

class PurchaseOrderService
{
    /**
     * Issuer GSTIN. The first two digits are the supply state used for CGST/SGST versus IGST.
     */
    private const ISSUER_GSTIN = '06AAMCM1308E1ZT';

    /**
     * Company PO series used on the reference document. The sequence and year are generated.
     */
    private const PO_SERIES = 'PO/Mobi';

    protected PurchaseOrderRepositoryInterface $purchaseOrderRepository;
    protected FinanceRecordRepositoryInterface $financeRecordRepository;

    /**
     * Inject the purchase order and finance record repositories.
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        FinanceRecordRepositoryInterface $financeRecordRepository
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->financeRecordRepository = $financeRecordRepository;
    }

    /**
     * Create the purchase order, its items, and the PDF.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     * @throws ValidationException
     * @throws DomainException
     * @throws Throwable
     */
    public function create(array $payload): array
    {
        $purchaseOrder = null;

        try {
            $this->validateOrders($payload);

            $financeRecord = $this->financeRecordRepository->find((int) $payload['finance_record_id']);

            if (!$financeRecord) {
                throw new DomainException('Finance record not found');
            }

            $publisher = $this->purchaseOrderRepository->findPublisherWithBank((int) $payload['publisher_id']);

            if (!$publisher) {
                throw new DomainException('Publisher not found');
            }

            if (empty($publisher['bank_id'])) {
                throw new DomainException('Publisher bank details not found');
            }

            $selectedAddress = $this->resolvePublisherAddress(
                $publisher['addresses'] ?? [],
                isset($payload['publisher_address_id']) ? (int) $payload['publisher_address_id'] : null
            );
            $publisher['address'] = (string) ($selectedAddress['line'] ?? '');

            $calculated = $this->calculate($payload['orders'], (string) $publisher['gst_number']);
            $poNumber = $this->nextPoNumber();
            $amountInWords = AmountInWords::rupees($calculated['total_amount']);

            $purchaseOrder = $this->purchaseOrderRepository->create([
                'po_number' => $poNumber,
                'publisher_id' => $publisher['id'],
                'publisher_address_id' => $selectedAddress['id'] ?? null,
                'finance_record_id' => (int) $financeRecord->id,
                'publisher_name' => $publisher['name'],
                'company_name' => $publisher['company_name'],
                'primary_email' => $publisher['primary_email'],
                'gst_number' => $publisher['gst_number'],
                'pan_number' => $publisher['pan_number'],
                'account_holder_name' => $publisher['account_holder_name'],
                'account_number' => $publisher['account_number'],
                'ifsc_code' => $publisher['ifsc_code'],
                'bank_name' => $publisher['bank_name'],
                'address' => $this->blankToNull($selectedAddress['address'] ?? null),
                'city' => $this->blankToNull($selectedAddress['city'] ?? null),
                'state' => $this->blankToNull($selectedAddress['state'] ?? null),
                'country' => $this->blankToNull($selectedAddress['country'] ?? null),
                'pincode' => $this->blankToNull($selectedAddress['pincode'] ?? null),
                'subtotal' => $calculated['subtotal'],
                'tax_amount' => $calculated['tax_amount'],
                'total_amount' => $calculated['total_amount'],
                'amount_in_words' => $amountInWords,
                'status' => '1',
            ]);

            $this->purchaseOrderRepository->createItems($purchaseOrder, $calculated['items']);

            $pdfPath = $this->writePdf($purchaseOrder, $publisher, $calculated, $amountInWords, [
                'campaign' => trim((string) ($payload['campaign'] ?? '')),
                'period' => trim((string) ($payload['period'] ?? '')),
            ]);

            $purchaseOrder->update(['pdf_path' => $pdfPath]);

            return [
                'id' => (int) $purchaseOrder->id,
                'finance_record_id' => (int) $purchaseOrder->finance_record_id,
                'po_number' => $purchaseOrder->po_number,
                'subtotal' => (float) $purchaseOrder->subtotal,
                'tax_amount' => (float) $purchaseOrder->tax_amount,
                'total_amount' => (float) $purchaseOrder->total_amount,
                'amount_in_words' => $purchaseOrder->amount_in_words,
                'pdf_path' => $pdfPath,
            ];
        } catch (Throwable $e) {
            $this->removeFailedPurchaseOrder($purchaseOrder);

            if (!$e instanceof DomainException && !$e instanceof ValidationException) {
                Log::error('Error creating purchase order', [
                    'publisher_id' => $payload['publisher_id'] ?? null,
                    'publisher_address_id' => $payload['publisher_address_id'] ?? null,
                    'finance_record_id' => $payload['finance_record_id'] ?? null,
                    'exception' => $e,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Remove a purchase order when a later step fails after the header was saved.
     */
    protected function removeFailedPurchaseOrder(?PurchaseOrder $purchaseOrder): void
    {
        if (!$purchaseOrder || !$purchaseOrder->id) {
            return;
        }

        try {
            $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $purchaseOrder->po_number) . '.pdf';
            $fullPath = storage_path('app/public/purchase-orders/' . $filename);

            if (is_file($fullPath)) {
                unlink($fullPath);
            }

            $purchaseOrder->forceDelete();
        } catch (Throwable $e) {
            Log::error('Error removing failed purchase order', [
                'purchase_order_id' => $purchaseOrder->id,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Validate the order lines before any totals are calculated.
     *
     * @param array<string, mixed> $payload
     * @throws ValidationException
     */
    protected function validateOrders(array $payload): void
    {
        try {
            $validator = Validator::make($payload, [
                'publisher_id' => 'required|integer|min:1',
                'publisher_address_id' => 'nullable|integer|min:1',
                'finance_record_id' => 'required|integer|min:1',
                'campaign' => 'nullable|string|max:255',
                'period' => 'nullable|string|max:255',
                'orders' => 'required|array|min:1',
                'orders.*.description' => 'required|string|max:1000',
                'orders.*.hsn_sac' => 'required|string|max:20',
                'orders.*.city' => 'nullable|string|max:255',
                'orders.*.qty' => 'required|numeric|gt:0',
                'orders.*.rate' => 'required|numeric|gte:0',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        } catch (Throwable $e) {
            if (!$e instanceof ValidationException) {
                Log::error('Error validating purchase order', ['exception' => $e]);
            }

            throw $e;
        }
    }

    /**
     * Use the selected publisher address. A single address is used when none is selected.
     *
     * @param array<int, array<string, mixed>> $addresses
     * @return array<string, mixed>
     */
    protected function resolvePublisherAddress(array $addresses, ?int $addressId): array
    {
        try {
            if ($addressId) {
                foreach ($addresses as $address) {
                    if ((int) $address['id'] === $addressId) {
                        return $address;
                    }
                }

                throw new DomainException('Publisher address not found');
            }

            if (count($addresses) > 1) {
                throw new DomainException('Select a publisher address');
            }

            return $addresses[0] ?? [];
        } catch (Throwable $e) {
            if (!$e instanceof DomainException) {
                Log::error('Error resolving publisher address', [
                    'publisher_address_id' => $addressId,
                    'exception' => $e,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Store an empty address part as null.
     */
    protected function blankToNull($value): ?string
    {
        try {
            $value = trim((string) $value);

            return $value === '' ? null : $value;
        } catch (Throwable $e) {
            Log::error('Error normalising purchase order address', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Calculate line amounts, GST, and the grand total.
     *
     * @param array<int, array<string, mixed>> $orders
     * @return array<string, mixed>
     */
    protected function calculate(array $orders, string $gstNumber): array
    {
        try {
            $items = [];
            $subtotal = 0.0;

            foreach ($orders as $order) {
                $qty = round((float) $order['qty'], 2);
                $rate = round((float) $order['rate'], 2);
                $amount = round($qty * $rate, 2);
                $subtotal += $amount;

                $items[] = [
                    'description' => trim((string) $order['description']),
                    'hsn_sac' => trim((string) $order['hsn_sac']),
                    'city' => trim((string) ($order['city'] ?? '')),
                    'qty' => $qty,
                    'rate' => $rate,
                    'amount' => $amount,
                ];
            }

            $subtotal = round($subtotal, 2);
            $publisherState = substr(preg_replace('/\s+/', '', $gstNumber) ?? '', 0, 2);
            $sameState = $publisherState !== '' && $publisherState === substr(self::ISSUER_GSTIN, 0, 2);
            $taxes = [];

            if ($sameState) {
                $half = round($subtotal * 9 / 100, 2);
                $taxAmount = round($half * 2, 2);
                $taxes[] = ['label' => 'CGST @ 9%', 'amount' => $half];
                $taxes[] = ['label' => 'SGST @ 9%', 'amount' => $half];
            } else {
                $taxAmount = round($subtotal * 18 / 100, 2);
                $taxes[] = ['label' => 'IGST @ 18%', 'amount' => $taxAmount];
            }

            return [
                'items' => $items,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => round($subtotal + $taxAmount, 2),
                'taxes' => $taxes,
                'place_of_supply' => $this->stateName($publisherState),
            ];
        } catch (Throwable $e) {
            Log::error('Error calculating purchase order totals', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Next number in the form PO/Mobi/0139/26-27.
     */
    protected function nextPoNumber(): string
    {
        try {
            $suffix = $this->financialYearSuffix();
            $latest = $this->purchaseOrderRepository->latestByNumberPrefix(self::PO_SERIES . '/%/' . $suffix);
            $sequence = 1;

            if ($latest && preg_match('/\/(\d+)\//', (string) $latest->po_number, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return sprintf('%s/%04d/%s', self::PO_SERIES, $sequence, $suffix);
        } catch (Throwable $e) {
            Log::error('Error generating purchase order number', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Indian financial year suffix, for example 26-27.
     */
    protected function financialYearSuffix(): string
    {
        try {
            $today = Carbon::now('Asia/Kolkata');
            $year = (int) $today->format('Y');
            $start = ((int) $today->format('n') < 4) ? $year - 1 : $year;

            return sprintf('%02d-%02d', $start % 100, ($start + 1) % 100);
        } catch (Throwable $e) {
            Log::error('Error resolving financial year', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Write the PDF under the public storage disk and return its storage path.
     *
     * @param array<string, mixed> $publisher
     * @param array<string, mixed> $calculated
     * @param array<string, string> $details
     */
    protected function writePdf(PurchaseOrder $purchaseOrder, array $publisher, array $calculated, string $amountInWords, array $details): string
    {
        try {
            $consigneeName = trim((string) ($publisher['company_name'] ?: $publisher['name']));
        $document = [
            'issuer' => $this->issuer(),
            'po_number' => $purchaseOrder->po_number,
            'po_date' => Carbon::now('Asia/Kolkata')->format('d-m-Y'),
            'campaign' => $details['campaign'],
            'period' => $details['period'],
            'consignee_name' => $consigneeName,
            'consignee_address' => trim((string) ($publisher['address'] ?? '')),
            'consignee_gst' => $publisher['gst_number'],
            'place_of_supply' => $calculated['place_of_supply'],
            'items' => array_map(function (array $item) {
                return [
                    'hsn_sac' => $item['hsn_sac'],
                    'city' => $item['city'],
                    'description' => $item['description'],
                    'qty' => $this->formatQty($item['qty']),
                    'rate' => AmountInWords::format($item['rate']),
                    'amount' => AmountInWords::format($item['amount']),
                ];
            }, $calculated['items']),
            'subtotal' => AmountInWords::format($calculated['subtotal']),
            'taxes' => array_map(function (array $tax) {
                return [
                    'label' => $tax['label'],
                    'amount' => AmountInWords::format($tax['amount']),
                ];
            }, $calculated['taxes']),
            'total' => AmountInWords::format($calculated['total_amount']),
            'amount_in_words' => $amountInWords,
        ];

        $html = $this->renderHtml($document);
        $tempDir = storage_path('framework/mpdf');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => $tempDir,
        ]);
        $mpdf->shrink_tables_to_fit = 1;
        $mpdf->setAutoTopMargin = false;
        $mpdf->SetTitle($purchaseOrder->po_number);
        $mpdf->WriteHTML($html);

        $directory = storage_path('app/public/purchase-orders');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $purchaseOrder->po_number) . '.pdf';
        $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($fullPath, $mpdf->Output('', 'S'));

            return 'purchase-orders/' . $filename;
        } catch (Throwable $e) {
            Log::error('Error writing purchase order PDF', [
                'po_number' => $purchaseOrder->po_number,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Issuing company printed on every purchase order.
     *
     * @return array<string, mixed>
     */
    protected function issuer(): array
    {
        try {
            return [
            'name' => 'MOBIYOUNG DIGITAL AD AGENCY PRIVATE LIMITED',
            'registered_office' => 'Regd. Off: NO 106 1ST FLOOR DVL RESIDENCY JAYARAM REDDY,Bangalore KA 560037 IN',
            'billing_address' => 'GSTN /Billing Address : 2nd FLOOR, PLOT NO.H-4178, ANSAL VERSALIA,NEAR AIP MALL,SECTOR-67, GURUGRAM, HARYANA-121001',
            'email' => 'finance@mobiyoung.com',
            'contact' => '8882055536',
            'cin' => 'U74999KA2018PTC117156',
            'pan' => 'AAMCM1308E',
            'gst' => self::ISSUER_GSTIN,
            'signatory' => 'For MOBIYOUNG DIGITAL AD AGENCY PVT. LTD.',
            'note' => 'PLEASE RETURN THIS COPY OF P.O. DULY SIGNED AND STAMPED',
            'terms' => [
                'If job is not executed as per specification, company will not be liable to pay',
                'The order can be cancelled with a prior notice.',
                'Important terms: Payment shall be released upon receipt of payment from client.',
            ],
            ];
        } catch (Throwable $e) {
            Log::error('Error loading purchase order issuer', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Fill the HTML template.
     *
     * @param array<string, mixed> $document
     */
    protected function renderHtml(array $document): string
    {
        try {
            ob_start();
            include base_path('resources/views/pdf/purchase-order.php');

            return (string) ob_get_clean();
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            Log::error('Error rendering purchase order HTML', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Show a whole quantity without trailing decimals.
     */
    protected function formatQty(float $qty): string
    {
        try {
            if (abs($qty - round($qty)) < 0.001) {
                return AmountInWords::format($qty);
            }

            return rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
        } catch (Throwable $e) {
            Log::error('Error formatting purchase order quantity', ['qty' => $qty, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * State name from the first two digits of a GSTIN.
     */
    protected function stateName(string $code): string
    {
        try {
            $states = [
            '01' => 'Jammu and Kashmir',
            '02' => 'Himachal Pradesh',
            '03' => 'Punjab',
            '04' => 'Chandigarh',
            '05' => 'Uttarakhand',
            '06' => 'Haryana',
            '07' => 'Delhi',
            '08' => 'Rajasthan',
            '09' => 'Uttar Pradesh',
            '10' => 'Bihar',
            '11' => 'Sikkim',
            '12' => 'Arunachal Pradesh',
            '13' => 'Nagaland',
            '14' => 'Manipur',
            '15' => 'Mizoram',
            '16' => 'Tripura',
            '17' => 'Meghalaya',
            '18' => 'Assam',
            '19' => 'West Bengal',
            '20' => 'Jharkhand',
            '21' => 'Odisha',
            '22' => 'Chhattisgarh',
            '23' => 'Madhya Pradesh',
            '24' => 'Gujarat',
            '27' => 'Maharashtra',
            '29' => 'Karnataka',
            '30' => 'Goa',
            '32' => 'Kerala',
            '33' => 'Tamil Nadu',
            '36' => 'Telangana',
            '37' => 'Andhra Pradesh',
            ];

            return $states[$code] ?? '';
        } catch (Throwable $e) {
            Log::error('Error resolving place of supply', ['code' => $code, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * List purchase orders.
     *
     * @throws Throwable
     */
    public function list(int $perPage = 15, array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        try {
            return $this->purchaseOrderRepository->paginate($perPage, $filters, $user);
        } catch (Throwable $e) {
            Log::error('Error fetching purchase orders', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find one purchase order.
     *
     * @throws Throwable
     */
    public function find(int $id, ?User $user = null): ?PurchaseOrder
    {
        try {
            return $this->purchaseOrderRepository->find($id, $user);
        } catch (Throwable $e) {
            Log::error('Error fetching purchase order by ID', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }
}
