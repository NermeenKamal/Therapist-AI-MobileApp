<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MealController extends Controller
{
    /**
     * Display a listing of the resource. read
     */
    public function index()
    {
        // select * from courses
        $allMeals = [
            ['id'=>1,'name'=>'Delicious Pizza','price'=>"\$20",'photo'=>'assets/images/f1.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'],
            ['id'=>2,'name'=>'Delicious Burger','price'=>"\$10",'photo'=>'assets/images/f2.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'],
            ['id'=>3,'name'=>'Delicious Pasta','price'=>"\$30",'photo'=>'assets/images/f4.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'],
            ['id'=>4,'name'=>'French Fries','price'=>"\$50",'photo'=>'assets/images/f5.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'],
            ['id'=>5,'name'=>'Delicious Pizza','price'=>"\$40",'photo'=>'assets/images/f6.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'],
            ['id'=>6,'name'=>'Tasty Burger','price'=>"\$30",'photo'=>'assets/images/f7.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque']
        ];
        return view('meals.index', ['meals'=>$allMeals]);
    }

    /**
     * Show the form for creating a new resource. add
     */
    public function create()
    {
        return view('meals.create');
    }

    /**
     * Store a newly created resource in storage. save
     */
    public function store(Request $request)
    {
        // get meal data from Form
        $data = $_POST;
        // save in database
        // redirect to route index
        // should be expired csrf token

//        $data = request()->all();  ==  request();
        $data = request()->name;  // return  nrmn
//        return $data;
        return  to_route('meals.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $meals=[];
        if($id==1){
            $meals =  ['id'=>1,'name'=>'Delicious Pizza','price'=>"\$20",'photo'=>'assets/images/f1.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==2){
           $meals =  ['id'=>2,'name'=>'Delicious Burger','price'=>"\$10",'photo'=>'assets/images/f2.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==3){
            $meals =  ['id'=>3,'name'=>'Delicious Pasta','price'=>"\$30",'photo'=>'assets/images/f4.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==4){
            $meals =   ['id'=>4,'name'=>'French Fries','price'=>"\$50",'photo'=>'assets/images/f5.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==5){
            $meals =   ['id'=>5,'name'=>'Delicious Pizza','price'=>"\$40",'photo'=>'assets/images/f6.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==6){
            $meals =   ['id'=>6,'name'=>'Tasty Burger','price'=>"\$30",'photo'=>'assets/images/f7.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        return view('meals.view', ['meals'=>$meals]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $meals=[];
        if($id==1){
            $meals =  ['id'=>1,'name'=>'Delicious Pizza','price'=>"\$20",'photo'=>'assets/images/f1.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==2){
            $meals =  ['id'=>2,'name'=>'Delicious Burger','price'=>"\$10",'photo'=>'assets/images/f2.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==3){
            $meals =  ['id'=>3,'name'=>'Delicious Pasta','price'=>"\$30",'photo'=>'assets/images/f4.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==4){
            $meals =   ['id'=>4,'name'=>'French Fries','price'=>"\$50",'photo'=>'assets/images/f5.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==5){
            $meals =   ['id'=>5,'name'=>'Delicious Pizza','price'=>"\$40",'photo'=>'assets/images/f6.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==6){
            $meals =   ['id'=>6,'name'=>'Tasty Burger','price'=>"\$30",'photo'=>'assets/images/f7.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        return view('meals.edit', ['meals'=>$meals]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // get updated meal record
        // save changes to database
        // redirect to  show
        $meals=[];
        if($id==1){
            $meals =  ['id'=>1,'name'=>'Delicious Pizza','price'=>"\$20",'photo'=>'assets/images/f1.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==2){
            $meals =  ['id'=>2,'name'=>'Delicious Burger','price'=>"\$10",'photo'=>'assets/images/f2.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==3){
            $meals =  ['id'=>3,'name'=>'Delicious Pasta','price'=>"\$30",'photo'=>'assets/images/f4.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==4){
            $meals =   ['id'=>4,'name'=>'French Fries','price'=>"\$50",'photo'=>'assets/images/f5.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==5){
            $meals =   ['id'=>5,'name'=>'Delicious Pizza','price'=>"\$40",'photo'=>'assets/images/f6.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        elseif($id==6){
            $meals =   ['id'=>6,'name'=>'Tasty Burger','price'=>"\$30",'photo'=>'assets/images/f7.png','description'=>'Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque'];
        }
        return view('meals.view', ['meals'=>$meals]);
    }

    /**
     * Remove the specified resource from storage. delete
     */
    public function destroy(string $id)
    {
        // get course record
        // delete course from database
        // redirect to index
        return to_route('meals.index');
    }
}
