<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;  // 1. Bỏ comment dòng này
use App\Models\Category; // 2. Bỏ comment dòng này

class HomeController extends Controller
{
    public function index()
    {
        return view('home');
    }
}