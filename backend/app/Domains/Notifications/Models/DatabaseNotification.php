<?php

namespace App\Domains\Notifications\Models;

use Illuminate\Notifications\DatabaseNotification as LaravelDatabaseNotification;

/**
 * Laravel database-channel notifications (in-app driver storage).
 * Separated from the enterprise `notifications` table.
 */
class DatabaseNotification extends LaravelDatabaseNotification
{
    protected $table = 'database_notifications';
}
