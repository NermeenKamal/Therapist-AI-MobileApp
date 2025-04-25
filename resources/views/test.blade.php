<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <h3>nrmn</h3>
     {{--      {{ }} echo statement      --}}
    {{$name}}
<br>
@foreach($books as $item)
    {{$item}}
    <br>
@endforeach

@for($i=0;$i<10;$i++)
    {{$i}} <br>
@endfor

@php
$arr = array("car",0,2.3,true);
foreach ($arr as $item){
    echo $item." ";
}
@endphp

</body>
</html>
