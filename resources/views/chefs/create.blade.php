@extends('layouts.app')
<!--  inheritance from another file -->

@section('title','AddChefs')


@section('content')
    <section class="book_section layout_padding">
        <div class="container">
            <div class="heading_container">
                <h2>
                    Add A Chef
                </h2>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form_container">
                        <form action="{{route('chefs.store')}}" method="post">
                            @csrf {{--  protection for security when calling data (to avoide page expired) --}}
                            {{-- when hackers submit codes in a text box then it should avoide return anything --}}
                            <div>
                                <input type="text" name="name" class="form-control" placeholder="Chef Name" />
                            </div>
                            <div>
                                <input type="text" name="description" class="form-control" placeholder="Description" />
                            </div>
                            <div>
                                <input type="number" name="experience" min="1" class="form-control" placeholder="Years Of Experience" />
                            </div>
                            <div>
                                <input type="file" name="photo" class="form-control" placeholder="photo" />
                            </div>
                            <div class="btn_box">
                                <button type="submit" href="{{ route('chefs.index') }}">
                                    Add Chef
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="map_container ">
                        <div id="googleMap"> <img src="{{asset('assets/images/team-3.jpg')}}"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

