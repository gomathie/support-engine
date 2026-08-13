<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Every employee-facing controller authorizes before it reads. Pulled in at
    // the base class so a new controller cannot quietly skip it by forgetting
    // the trait.
    use AuthorizesRequests;
}
