<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Gives every controller $this->authorize('ability', $model), which checks the model's Policy
    // and returns a 403 page if the user is not allowed.
    use AuthorizesRequests;
}
