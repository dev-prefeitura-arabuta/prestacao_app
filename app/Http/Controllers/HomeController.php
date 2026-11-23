<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        if (session('isLoggedIn')) {
            return redirect('/accountability');
        }

        return view('home.index');
    }
}
