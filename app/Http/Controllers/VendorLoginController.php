<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;

class VendorLoginController
{
    public function loginAs(Vendor $vendor)
    {
        $user = $vendor->owner; // or vendor user

        Auth::login($user);

        return redirect('/'); // vendor dashboard
    }
}
