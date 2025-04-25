@extends('layouts.app')
<!--  inheritance from another file -->

@section('title','Edit')


@section('content')
    <section class="book_section layout_padding">
        <div class="container">
            <div class="heading_container">
                <h2>
                    Edit meal
                </h2>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form_container">
                        <form action="{{route('meals.update',$meals['id'])}}" method="post">   {{-- html only knows post and edit  --}}

                            @csrf {{--  protection for security when calling data (to avoide page expired) --}}
                            {{-- when hackers submit codes in a text box then it should avoide return anything --}}
                            @method('put')  {{--  force html post to act as put  --}}

                            <div>
                                <input type="text" name="name" value="{{$meals['name']}}" class="form-control" placeholder="Meal Name" />
                            </div>
                            <div>
                                <input type="text" name="description" value="{{$meals['description']}}"  class="form-control" placeholder="Description" />
                            </div>
                            <div>
                                <input type="number" name="price" value="{{$meals['price']}}"  min="1" class="form-control" placeholder="{{$meals['price']}}" />
                            </div>
                            <div>
                                <input type="file" name="photo" value="{{$meals['photo']}}"  class="form-control" placeholder="{{$meals['photo']}}"  />
                            </div>
                            <div class="btn_box">
                                <button type="submit" href="{{route('meals.index')}}">
                                    Edit meal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="map_container ">
                        <div id="googleMap"> <img style="width: 50%; height: 75%;" src="{{asset($meals['photo'])}}"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

