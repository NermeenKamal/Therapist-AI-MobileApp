<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// use namespace that is in UserController
use App\Http\Controllers\ChefController;
use App\Http\Controllers\MealController;

/*
 // helper function pre-defined: return view
// array of variables in second parameter for view
Route::get('user',function(){   // 'name' same as $name in test.blade.php
    // associative array
    $localname='nrmn';
    return view('test', ['name' => $localname,
        'books'=>['php','html','css','js']]);
});

//       {id} parameter required      http://127.0.0.1:8000/user/5
Route::get('user/{id}',function($id){
    echo "id: ". $id;
});
//  call back function for second parameter route
//       {name?} parameter optional     http://127.0.0.1:8000/users/nrmn
Route::get('users/{name?}', function($name=""){
    echo "welcome ".$name;
});
*/

Route::get('/',function(){
    return view('layouts.app');
});

Route::get('/meals',
    [MealController::class, 'index']) ->name('meals.index');
// using route name instead of url controller

Route::get('/meals/create',
[MealController::class, 'create']
);


Route::get('/meals/{meal}/edit',
[MealController::class, 'edit'])->name('meals.edit');
Route::get('/meals/{meal}/delete',
[MealController::class, 'destroy'])->name('meals.delete');


Route::put('/meals/{meal}/update', // edit data in database , put for APIs
[MealController::class, 'update'])->name('meals.update');


Route::get('/meals/{meal}',
[MealController::class, 'show'])->name('meals.view');

// we have to use post method to store
Route::post('/meals',
    [MealController::class, 'store'])->name('meals.store');

// we have to put dynamic /{meal} route after static route /meal

//  UserController::class  ==  App\Http\Controllers\UserController








Route::get('/chefs',
    [ChefController::class, 'index']) ->name('chefs.index');
// using route name instead of url controller

Route::get('/chefs/create',
    [ChefController::class, 'create'])->name('chefs.create');

Route::get('/chefs/{chef}/edit',
    [ChefController::class, 'edit'])->name('chefs.edit');
Route::get('/chefs/{chef}/delete',
    [ChefController::class, 'destroy'])->name('chefs.delete');

Route::put('/chefs/{chef}/update', // edit data in database , put for APIs
    [ChefController::class, 'update'])->name('chefs.update');

Route::get('/chefs/{chef}',
    [ChefController::class, 'show'])->name('chefs.view');

// we have to use post method to store
Route::post('/chefs',
    [ChefController::class, 'store'])->name('chefs.store');

// 1- user: define route for user to can access browser
//  route can call view or controller
// 2- define controller can render view
// 3- define  view content as static data (form)
// 4- remove any static data from view and add dynamic data from database


// structure course database (any change on Database structure(create table, edit column, remove column))
// operations on database(crud on data record)
//  using database not gui, but using migration(version control to allow team add and edit  database)
//

Route::get('/test-db', function() {
    try {
        DB::connection()->getPdo();
        return "Connected!";
    } catch (\Exception $e) {
        return "Failed: " . $e->getMessage();
    }
});
