@extends('layouts.app')

@section('title','Edit Chef')

@section('content')
    <section class="book_section layout_padding">
        <div class="container">
            <div class="heading_container">
                <h2>
                    Edit Chef Profile
                </h2>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form_container">
                        <form action="{{ route('chefs.update', $chef['id']) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('put')

                            <div>
                                <input type="text" name="name" value="{{ $chef['name'] }}"
                                       class="form-control" placeholder="Chef Name" required>
                            </div>
                            <div>
                                <input type="number" name="experience" value="{{ $chef['experience'] }}"
                                       min="1" class="form-control" placeholder="{{ $chef['experience'] }}" required>
                            </div>
                            <div>
                                <textarea name="description" class="form-control"
                                          placeholder="Chef Description" rows="5" required>{{ $chef['description'] }}</textarea>
                            </div>
                            <div>
                                <input type="file" name="photo" class="form-control-file">
                            </div>
                            <div class="btn_box">
                                <button type="submit" class="btn-primary">
                                    Update Chef Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="map_container">
                        <div id="chef-photo-preview">
                            <img style="width: 80%; height: auto;"
                                 src="{{ asset($chef['photo']) }}"
                                 alt="{{ $chef['name'] }}'s Current Photo">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
