<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CompanyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth'];
    }

    // Mostrar todas las empresas
    public function index()
    {
        $companies = Company::all(); // O agregar paginación: Company::paginate(10);
        return view('system.companies', compact('companies'));
    }
}
