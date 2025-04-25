@extends('layouts.app')
@section('title','DisplayChefs')
@section('chefactive')
    </li> <li class="nav-item active">
    @endsection
    @section('disactive')
</li><li class="nav-item">
    @endsection

    @section('content')
        <div style="display: flex; justify-content: center;">
            <div class="card" style="width: 18rem;">
                <img class="card-img-top" src="{{asset($chef['photo'])}}" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title">{{$chef['name']}}</h5>
                    <p class="card-text">{{$chef['description']}}</p>
                    <p class="card-text">{{$chef['experience']}}</p>
                </div>
            </div>
        </div>
@endsection
