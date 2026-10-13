<?php
/**
 * GoogleCalender
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalender extends Model
{
    protected $fillable = [
        'user_id',
        'token',
    ];
}