<?php

use Illuminate\Support\Facades\Route;

// Ruta web inicial de Laravel: muestra la vista de bienvenida del proyecto.
Route::get('/', function () {
    return view('welcome');
});
