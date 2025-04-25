<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChefController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allChefs = [
            ['id' => 1, 'name' => 'Ashraf Khaled', 'experience' => "4 Years", 'photo' => 'assets/images/team-1.jpg',
                'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'],
            ['id' => 2, 'name' => 'Osama Mohamed', 'experience' => "5 Years", 'photo' => 'assets/images/team-2.jpg',
                'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'],
            ['id' => 3, 'name' => 'Ahmed Samy', 'experience' => "4 Years", 'photo' => 'assets/images/team-3.jpg',
                'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'],
            ['id' => 4, 'name' => 'Ali Tarek', 'experience' => "6 Years", 'photo' => 'assets/images/team-4.jpg',
                'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque']
        ];
        return view('chefs.index', ['chefs' => $allChefs]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('chefs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Get chef data from Form
        $data = $_POST;
        // Save to database logic would go here
        // Redirect to index route
        return to_route('chefs.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $chef = [];
        switch($id) {
            case 1:
                $chef = ['id' => 1, 'name' => 'Ashraf Khaled', 'experience' => "4 Years",
                    'photo' => 'assets/images/team-1.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
            case 2:
                $chef = ['id' => 2, 'name' => 'Osama Mohamed', 'experience' => "5 Years",
                    'photo' => 'assets/images/team-2.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
            case 3:
                $chef = ['id' => 3, 'name' => 'Ahmed Samy', 'experience' => "4 Years",
                    'photo' => 'assets/images/team-3.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
            case 4:
                $chef = ['id' => 4, 'name' => 'Ali Tarek', 'experience' => "6 Years",
                    'photo' => 'assets/images/team-4.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
        }
        return view('chefs.view', ['chef' => $chef]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $chef = [];
        switch($id) {
            case 1:
                $chef = ['id' => 1, 'name' => 'Ashraf Khaled', 'experience' => "4 Years",
                    'photo' => 'assets/images/team-1.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
            case 2:
                $chef = ['id' => 2, 'name' => 'Osama Mohamed', 'experience' => "5 Years",
                    'photo' => 'assets/images/team-2.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
            case 3:
                $chef = ['id' => 3, 'name' => 'Ahmed Samy', 'experience' => "4 Years",
                    'photo' => 'assets/images/team-3.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
            case 4:
                $chef = ['id' => 4, 'name' => 'Ali Tarek', 'experience' => "6 Years",
                    'photo' => 'assets/images/team-4.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
        }
        return view('chefs.edit', ['chef' => $chef]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Update logic would go here
        // Redirect to show view

        $chef = [];
        switch($id) {
            case 1:
                $chef = ['id' => 1, 'name' => 'Ashraf Khaled', 'experience' => "4 Years",
                    'photo' => 'assets/images/team-1.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
            case 2:
                $chef = ['id' => 2, 'name' => 'Osama Mohamed', 'experience' => "5 Years",
                    'photo' => 'assets/images/team-2.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
            case 3:
                $chef = ['id' => 3, 'name' => 'Ahmed Samy', 'experience' => "4 Years",
                    'photo' => 'assets/images/team-3.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
            case 4:
                $chef = ['id' => 4, 'name' => 'Ali Tarek', 'experience' => "6 Years",
                    'photo' => 'assets/images/team-4.jpg', 'description' => 'Veniam debitis quaerat officiis quasi cupiditate quo...'];
                break;
        }
        return view('chefs.view', ['chef'=>$chef]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Delete logic would go here
        return to_route('chefs.index');
    }
}
