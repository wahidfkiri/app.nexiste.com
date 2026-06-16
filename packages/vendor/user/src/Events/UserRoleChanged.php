<?php

namespace Vendor\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserRoleChanged
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public string $oldRole,
        public string $newRole,
    ) {
    }
}

