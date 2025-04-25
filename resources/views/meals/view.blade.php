@extends('layouts.app')
@section('title','DisplayMeals')
@section('mealactive')
    </li> <li class="nav-item active">
    @endsection
    @section('disactive')
</li><li class="nav-item">
@endsection

    @section('content')
        <div style="display: flex; justify-content: center;">
    <div class="card" style="width: 18rem;">
        <img class="card-img-top" src="{{asset($meals['photo'])}}" alt="Card image cap">
        <div class="card-body">
            <h5 class="card-title">{{$meals['name']}}</h5>
            <p class="card-text">{{$meals['description']}}</p>
            <p class="card-text">{{$meals['price']}}</p>
        </div>
    </div>
        </div>
@endsection
