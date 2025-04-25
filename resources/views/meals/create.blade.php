@extends('layouts.app')
<!--  inheritance from another file -->

@section('title','AddMeal')


@section('content')
    <section class="book_section layout_padding">
        <div class="container">
            <div class="heading_container">
                <h2>
                    Add A Meal
                </h2>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form_container">
                        <form action="{{route('meals.store')}}" method="post">
                            @csrf {{--  protection for security when calling data (to avoide page expired) --}}
                                      {{-- when hackers submit codes in a text box then it should avoide return anything --}}
                            <div>
                                <input type="text" name="name" class="form-control" placeholder="Meal Name" />
                            </div>
                            <div>
                                <input type="text" name="description" class="form-control" placeholder="Description" />
                            </div>
                            <div>
                                <input type="number" name="price" min="1" class="form-control" placeholder="Price" />
                            </div>
                            <div>
                                <input type="file" name="photo" class="form-control" placeholder="photo" />
                            </div>
                            <div class="btn_box">
                                <button type="submit" href="{{ route('meals.index') }}">
                                    Add Meal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="map_container ">
                        <div id="googleMap"> <img src="{{asset('assets/images/f4.png')}}"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

