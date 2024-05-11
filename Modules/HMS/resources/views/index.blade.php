@extends('hms::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('hms.name') !!}</p>
@endsection
