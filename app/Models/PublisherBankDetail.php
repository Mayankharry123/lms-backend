<?php

/**
 * PublisherBankDetail
 * -----------------------------------------
 * Bank details for a DGPlay publisher.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublisherBankDetail extends Model
{
    use SoftDeletes;

    protected $connection = 'dgplay';

    protected $table = 'ads_user_bank_details';

    /**
     * Register the DGPlay connection before the first bank query.
     */
    protected static function booted(): void
    {
        Publisher::ensureConnection();
    }

    /**
     * Publisher that owns these bank details.
     */
    public function publisher()
    {
        return $this->belongsTo(Publisher::class, 'user_id', 'user_id');
    }
}
