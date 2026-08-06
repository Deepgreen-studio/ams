<?php

namespace App\Shared\Helpers;

use App\Models\User;
use Spatie\Activitylog\Facades\CauserResolver;

class ActivityHelper
{
    public static function causedBy(?User $user): void
    {
        if ($user) {
            CauserResolver::setCauser($user);
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(string $description, ?User $causer = null, array $properties = []): void
    {
        $logger = activity();

        if ($causer) {
            $logger->causedBy($causer);
        }

        $logger->withProperties($properties)->log($description);
    }
}
