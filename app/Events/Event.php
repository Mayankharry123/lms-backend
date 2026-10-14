<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;

/**
 * Base abstract event class.
 */
abstract class Event
{
    use SerializesModels;
}

