@extends('admin.layouts.app')
@section('title','المحتوى')
@section('content')

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">إدارة المحتوى</h5>
  </div>
  <div class="card-body">
    <form method="post" action="{{ route('admin.content.update') }}">
      @csrf
      <div id="kvPairs">
        @php $i=0; @endphp
        @foreach($settings as $key => $value)
          <div class="row g-3 mb-3 align-items-center kv-row">
            <div class="col-md-5">
              <label class="form-label">المفتاح</label>
              <input type="text" name="items[{{ $i }}][key]" class="form-control" value="{{ $key }}" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">القيمة</label>
              <input type="text" name="items[{{ $i }}][value]" class="form-control" value="{{ $value }}" />
            </div>
            <div class="col-md-1 d-grid mt-4">
              <button type="button" class="btn btn-label-danger btn-remove">حذف</button>
            </div>
          </div>
          @php $i++; @endphp
        @endforeach
        @if($i===0)
          <div class="row g-3 mb-3 align-items-center kv-row">
            <div class="col-md-5">
              <label class="form-label">المفتاح</label>
              <input type="text" name="items[0][key]" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">القيمة</label>
              <input type="text" name="items[0][value]" class="form-control" />
            </div>
            <div class="col-md-1 d-grid mt-4">
              <button type="button" class="btn btn-label-danger btn-remove">حذف</button>
            </div>
          </div>
        @endif
      </div>
      <div class="d-flex justify-content-between">
        <button type="button" id="btnAddRow" class="btn btn-label-primary">إضافة سطر</button>
        <button type="submit" class="btn btn-primary">حفظ</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('kvPairs');
  const btnAdd = document.getElementById('btnAddRow');
  btnAdd.addEventListener('click', function() {
    const idx = container.querySelectorAll('.kv-row').length;
    const tpl = document.createElement('div');
    tpl.className = 'row g-3 mb-3 align-items-center kv-row';
    tpl.innerHTML = `
      <div class="col-md-5">
        <label class="form-label">المفتاح</label>
        <input type="text" name="items[${idx}][key]" class="form-control" required />
      </div>
      <div class="col-md-6">
        <label class="form-label">القيمة</label>
        <input type="text" name="items[${idx}][value]" class="form-control" />
      </div>
      <div class="col-md-1 d-grid mt-4">
        <button type="button" class="btn btn-label-danger btn-remove">حذف</button>
      </div>
    `;
    container.appendChild(tpl);
  });
  container.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove')) {
      const row = e.target.closest('.kv-row');
      row.parentNode.removeChild(row);
    }
  });
});
</script>
@endsection

