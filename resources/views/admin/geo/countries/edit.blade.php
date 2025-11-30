@extends('admin.layouts.app')
@section('title','تعديل دولة')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.geo.index') }}">الدول</a></li>
    <li class="breadcrumb-item active" aria-current="page">تعديل دولة</li>
  </ol>
</nav>

  @include('admin.geo.countries.form', compact('country','cities'))
@endsection
