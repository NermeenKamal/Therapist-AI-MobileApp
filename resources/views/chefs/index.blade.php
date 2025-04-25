@extends('layouts.app')
@section('title','DisplayChefs')
@section('chefactive')
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
                        Our Chefs
                    </h2>
                </div>
                <div class="filters-content">
                    <div class="row grid">
                        @foreach($chefs as $chef)
                            <div class="col-sm-6 col-lg-4 all">
                                <div class="box">
                                    <div>
                                        <div class="img-box">
                                            <img class="team" src="{{ $chef['photo'] }}" alt="{{ $chef['name'] }}">
                                        </div>
                                        <div class="detail-box">
                                            <h5>
                                                {{ $chef['name'] }}
                                            </h5>
                                            <p>
                                                {{ $chef['description'] }}
                                            </p>
                                            <div class="options">
                                                <h6>
                                                    {{ $chef['experience'] }} Years Experience
                                                </h6>
                                                <div class="crud">
                                                    <a href="/chefs/{{ $chef['id'] }}">
                                                        <i class="fa fa-eye" aria-hidden="false"></i>
                                                        <h3>View</h3>
                                                    </a>
                                                    <a href="{{ route('chefs.edit', $chef['id']) }}">
                                                        <i class="fa fa-pencil-square-o" aria-hidden="false"></i>
                                                        <h3>Update</h3>
                                                    </a>
                                                    <a href="{{ route('chefs.index') }}">
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
                    <a href="{{ route('chefs.create') }}">
                        <i class="fa fa-plus-circle" aria-hidden="false"></i>
                        <h3>Add Chef</h3>
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
