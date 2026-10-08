<?php

/**
 * Publisher
 * -----------------------------------------
 * Active publisher from the DGPlay ads_user table, including bank details.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Publisher extends Model
{
    use SoftDeletes;

    protected $connection = 'dgplay';

    protected $table = 'ads_user';

    protected $primaryKey = 'user_id';

    /**
     * Register the DGPlay connection before the first publisher query.
     */
    protected static function booted(): void
    {
        static::ensureConnection();
    }

    /**
     * Load an active publisher and the active bank row.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveWithBank(int $publisherId): ?array
    {
        $publisher = $this->newQuery()
            ->with(['bankDetail', 'addresses'])
            ->where('user_id', $publisherId)
            ->where('admin_flag', '2')
            ->where('status', '1')
            ->first();

        if (!$publisher) {
            return null;
        }

        $bank = $publisher->bankDetail;

        return [
            'id' => (int) $publisher->user_id,
            'name' => trim(trim((string) $publisher->first_name) . ' ' . trim((string) $publisher->last_name)),
            'primary_email' => (string) $publisher->email_id,
            'company_name' => (string) $publisher->company_name,
            'addresses' => $publisher->addresses->map(function ($address) {
                $row = [
                    'id' => (int) $address->id,
                    'address' => trim((string) $address->address),
                    'city' => trim((string) $address->city),
                    'state' => trim((string) $address->state),
                    'country' => trim((string) $address->country),
                    'pincode' => trim((string) $address->pincode),
                ];
                $row['line'] = $this->formatAddressLine($row);

                return $row;
            })->values()->all(),
            'bank_id' => $bank ? (int) $bank->id : null,
            'gst_number' => (string) ($bank->gst_number ?? ''),
            'pan_number' => (string) ($bank->pan_number ?? ''),
            'account_holder_name' => (string) ($bank->account_holder_name ?? ''),
            'account_number' => (string) ($bank->account_number ?? ''),
            'ifsc_code' => (string) ($bank->ifsc_code ?? ''),
            'bank_name' => (string) ($bank->bank_name ?? ''),
        ];
    }

    /**
     * Active addresses for this publisher.
     */
    public function addresses()
    {
        return $this->hasMany(PublisherAddress::class, 'user_id', 'user_id')
            ->where('status', 1)
            ->orderBy('id');
    }

    /**
     * Address, City, State, Country - Pincode.
     *
     * @param array<string, mixed> $address
     */
    public function formatAddressLine(array $address): string
    {
        $parts = array_values(array_filter([
            trim((string) ($address['address'] ?? '')),
            trim((string) ($address['city'] ?? '')),
            trim((string) ($address['state'] ?? '')),
            trim((string) ($address['country'] ?? '')),
        ], static function (string $part): bool {
            return $part !== '';
        }));

        $line = implode(', ', $parts);
        $pincode = trim((string) ($address['pincode'] ?? ''));

        if ($pincode === '') {
            return $line;
        }

        return $line === '' ? $pincode : $line . ' - ' . $pincode;
    }

    /**
     * Active bank details for this publisher.
     */
    public function bankDetail()
    {
        return $this->hasOne(PublisherBankDetail::class, 'user_id', 'user_id')
            ->where('status', 1)
            ->latest('id');
    }

    /**
     * Point Eloquent at the DGPlay database.
     */
    public static function ensureConnection(): void
    {
        if (config('database.connections.dgplay')) {
            return;
        }

        config([
            'database.connections.dgplay' => [
                'driver' => 'mysql',
                'host' => env('DGPLAY_DB_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => env('DGPLAY_DB_PORT', env('DB_PORT', '3306')),
                'database' => env('DGPLAY_DB_DATABASE', 'comp_dgtoohl'),
                'username' => env('DGPLAY_DB_USERNAME', env('DB_USERNAME', 'root')),
                'password' => env('DGPLAY_DB_PASSWORD', env('DB_PASSWORD', '')),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ],
        ]);
    }
}
