<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MakeMeActivityLogger
{
    public static function record(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = []
    ): void {
        $admin = Auth::guard('web')->user();

        $logger = activity('makeme-' . $module)
            ->event($action)
            ->withProperties(array_merge($properties, [
                'module' => $module,
                'action' => $action,
                'ip_address' => request()->ip(),
            ]));

        if ($admin) {
            $logger->causedBy($admin);
        } else {
            $logger->causedByAnonymous();
        }

        if ($subject) {
            $logger->performedOn($subject);
        }

        $logger->log($description);
    }
}
