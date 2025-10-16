@extends('admin.layouts.app')
@section('title','تعديل دولة')
@section('content')
  @include('admin.geo.countries.form', compact('country','cities'))
@endsection
