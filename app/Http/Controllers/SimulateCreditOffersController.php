<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SimulateCreditOffersController extends Controller
{
    //
    public function simulateCreditOfferPage()
    {
        return view('simulate-credit-offer');
    }
}
