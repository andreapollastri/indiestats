<?php

namespace App\Policies;

use App\Models\SiteExport;
use App\Models\User;

class SiteExportPolicy
{
    public function view(User $user, SiteExport $export): bool
    {
        return $user->id === $export->user_id;
    }
}
