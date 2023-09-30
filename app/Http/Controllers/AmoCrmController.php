<?php

namespace App\Http\Controllers;

use App\AmoCrmService;

class AmoCrmController extends Controller
{
    public function webhooks(AmoCrmService $service)
    {
        $service->addNote($_POST);
    }

    public function test(AmoCrmService $service)
    {

    }
}
