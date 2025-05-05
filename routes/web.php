<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


Route::get('/',function(){
    return view('layouts.app');
});


Route::get('/test-db', function() {
    try {
        DB::connection()->getPdo();
        return "Connected!";
    } catch (\Exception $e) {
        return "Failed: " . $e->getMessage();
    }
});
