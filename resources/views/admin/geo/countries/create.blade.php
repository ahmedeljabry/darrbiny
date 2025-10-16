@extends('admin.layouts.app')
@section('title','إضافة دولة')
@section('content')
  @include('admin.geo.countries.form', ['country' => null, 'cities' => collect()])
@endsection
