<?php

namespace Vendor\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserActivated
{
    use Dispatchable;

    public function __construct(public User $user)
    {
    }
}

