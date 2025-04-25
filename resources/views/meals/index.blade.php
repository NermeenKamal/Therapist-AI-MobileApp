@extends('layouts.app')
@section('title','DisplayMeals')
@section('mealactive')
    </li> <li class="nav-item active">
@endsection
@section('disactive')
</li><li class="nav-item">
@endsection
@section('content')
    <section class="food_section layout_padding-bottom">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>
                    Our Menu
                </h2>
            </div>
            <ul class="filters_menu">
                <li class="active" data-filter="*">All</li>
                <li data-filter=".burger">Burger</li>
                <li data-filter=".pizza">Pizza</li>
                <li data-filter=".pasta">Pasta</li>
                <li data-filter=".fries">Fries</li>
            </ul>
            <div class="filters-content">
                <div class="row grid">
            @foreach($meals as $item)
                    <div class="col-sm-6 col-lg-4 all burger">
                        <div class="box">
                            <div>
                                <div class="img-box">
                                    <img src="{{$item['photo']}}">
                                </div>
                                <div class="detail-box">
                                    <h5>
                                        {{$item['name']}}
                                    </h5>
                                    <p>
                                        {{$item['description']}}
                                    </p>
                                    <div class="options">
                                        <h6>
                                            {{$item['price']}}
                                        </h6>
                                        <div class="crud">
                                            <a href="/meals/{{$item['id']}}">
                                                <i class="fa  fa-eye" aria-hidden="false"></i>
                                                <h3>View</h3>
                                            </a>
                                            <a href="{{route('meals.edit',$item['id'])}}">
                                                <i class="fa  fa-pencil-square-o" aria-hidden="false"></i>
                                                <h3>Update</h3>
                                            </a>
                                            <a href="{{route('meals.index')}}">
                                                <i class="fa fa-minus-circle" aria-hidden="false"></i>
                                                <h3>Delete</h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            @endforeach
                </div>
            </div>
            <div class="CRUD">
                <a href="{{url('meals/create')}}">
                    <i class="fa fa-plus-circle" aria-hidden="false"></i>
                    <h3>Add</h3>
                </a>
            </div>
            <div class="btn-box">
                <a href="">
                    View More
                </a>
            </div>
        </div>
    </section>
@endsection






{{--    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">--}}
{{--    <html lang="en">--}}
{{--    <head>--}}
{{--        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">--}}
{{--        <title>Document</title>--}}
{{--    </head>--}}
{{--    <body>--}}
{{--    @dd($meals);--}}
{{--    </body>--}}
{{--    </html>--}}
