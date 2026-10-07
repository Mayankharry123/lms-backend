<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Voucher extends BaseModel
{
    protected $table = 'vouchers';

    protected $fillable = [
        'uuid',
        'voucher_number',
        'voucher_type_id',
        'person_name',
        'file_path',
        'subtotal',
        'sgst_rate',
        'sgst_amount',
        'cgst_rate',
        'cgst_amount',
        'igst_rate',
        'igst_amount',
        'total_tax',
        'total_amount',
        'amount_in_words',
        'month',
        'notes',
        'created_by',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'float',
        'sgst_rate' => 'float',
        'sgst_amount' => 'float',
        'cgst_rate' => 'float',
        'cgst_amount' => 'float',
        'igst_rate' => 'float',
        'igst_amount' => 'float',
        'total_tax' => 'float',
        'total_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Voucher belongs to a voucher type.
     */
    public function voucherType(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class, 'voucher_type_id');
    }

    /**
     * Relationship: Voucher is created by a user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Voucher has many line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(VoucherItem::class, 'voucher_id');
    }

    /**
     * Query paginated vouchers for list view with filters.
     */
    public function paginateForList(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->with(['voucherType', 'creator', 'items'])
            ->whereNull('vouchers.deleted_at')
            ->where('vouchers.status', '!=', '15');

        if (!empty($filters['voucher_type_id'])) {
            $query->where('vouchers.voucher_type_id', (int) $filters['voucher_type_id']);
        }

        if (!empty($filters['person_name'])) {
            $query->where('vouchers.person_name', 'LIKE', '%' . trim((string) $filters['person_name']) . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('vouchers.status', (string) $filters['status']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('vouchers.created_by', (int) $filters['created_by']);
        }

        if (!empty($filters['month'])) {
            $query->where('vouchers.month', (string) $filters['month']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('vouchers.created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('vouchers.created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($sub) use ($term) {
                $sub->where('vouchers.voucher_number', 'LIKE', "%{$term}%")
                    ->orWhere('vouchers.person_name', 'LIKE', "%{$term}%")
                    ->orWhereHas('voucherType', function ($typeQ) use ($term) {
                        $typeQ->where('name', 'LIKE', "%{$term}%");
                    })
                    ->orWhereHas('items', function ($itemQ) use ($term) {
                        $itemQ->where('particular', 'LIKE', "%{$term}%")
                            ->orWhere('purpose', 'LIKE', "%{$term}%")
                            ->orWhere('mode', 'LIKE', "%{$term}%");
                    });
            });
        }

        return $query->latest('vouchers.id')->paginate($perPage);
    }

    /**
     * Find a single voucher by ID with relationships.
     */
    public function findForDetail(int $id): ?self
    {
        return $this->newQuery()
            ->with(['voucherType', 'creator', 'items'])
            ->whereNull('deleted_at')
            ->find($id);
    }

    /**
     * Get the latest voucher matching a voucher number prefix (including trashed).
     */
    public function latestByNumberPrefix(string $prefix): ?self
    {
        return $this->newQuery()
            ->withTrashed()
            ->where('voucher_number', 'LIKE', $prefix . '%')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Create a new voucher record.
     */
    public function storeVoucher(array $data): self
    {
        return $this->newQuery()->create($data);
    }

    /**
     * Update an existing voucher record.
     */
    public function updateVoucher(int $id, array $data): ?self
    {
        $voucher = $this->findForDetail($id);
        if (!$voucher) {
            return null;
        }

        $voucher->update($data);
        return $voucher->fresh(['voucherType', 'creator', 'items']);
    }

    /**
     * Soft delete a voucher record and cascade status to 15.
     */
    public function deleteVoucher(int $id): bool
    {
        $voucher = $this->findForDetail($id);
        if (!$voucher) {
            return false;
        }

        $voucher->status = '15';
        $voucher->save();

        $voucher->items()->update(['status' => '15']);
        $voucher->items()->delete();

        return (bool) $voucher->delete();
    }

    /**
     * Store line items for this voucher.
     */
    public function storeItems(array $items): void
    {
        foreach ($items as $item) {
            $this->items()->create([
                'date' => !empty($item['date']) ? Carbon::parse($item['date'])->format('Y-m-d') : null,
                'particular' => (string) ($item['particular'] ?? ''),
                'purpose' => isset($item['purpose']) ? (string) $item['purpose'] : null,
                'mode' => isset($item['mode']) ? (string) $item['mode'] : null,
                'amount' => (float) ($item['amount'] ?? 0),
                'status' => '1',
            ]);
        }
    }

    /**
     * Sync line items for this voucher.
     */
    public function syncItems(array $items): void
    {
        $this->items()->delete();
        $this->storeItems($items);
    }

    /**
     * Accessor: Resolve public file URL.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $this->file_path), '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        $baseUrl = rtrim((string) config('app.url', env('APP_URL', '')), '/');
        return $baseUrl . '/storage/' . $path;
    }

    /**
     * Accessor: Get clean file name.
     */
    public function getFileNameAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }
        return basename($this->file_path);
    }
}
