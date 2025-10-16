@php($isEdit = isset($country) && $country)
@php($action = $isEdit ? route('admin.geo.countries.update', $country->id) : route('admin.geo.countries.store'))

<form method="post" action="{{ $action }}">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="row g-6">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">تفاصيل الدولة</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">رمز ISO2</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-flag"></i></span>
                <input type="text" name="iso2" class="form-control" maxlength="2" placeholder="SA" value="{{ old('iso2', $isEdit ? $country->iso2 : '') }}" required>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">العملة</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-currency"></i></span>
                <input type="text" name="currency" class="form-control" maxlength="3" placeholder="SAR" value="{{ old('currency', $isEdit ? $country->currency : '') }}" required>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">الاسم</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-edit"></i></span>
                <input type="text" name="name" class="form-control" placeholder="السعودية" value="{{ old('name', $isEdit ? $country->name : '') }}" required>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">مدن الدولة</h5>
          <button type="button" class="btn btn-sm btn-outline-primary js-add-city"><i class="ti tabler-plus"></i> إضافة مدينة</button>
        </div>
        <div class="card-body">
          <div id="cities-list" class="d-flex flex-column gap-2 mb-4">
            @php($oldNew = old('new_cities', []))
            @if(is_array($oldNew) && count($oldNew))
              @foreach($oldNew as $val)
                <div class="row g-2 align-items-center city-row">
                  <div class="col">
                    <div class="input-group input-group-merge">
                      <span class="input-group-text"><i class="ti tabler-map-pin"></i></span>
                      <input type="text" name="new_cities[]" class="form-control" placeholder="اسم مدينة جديدة" value="{{ $val }}">
                    </div>
                  </div>
                  <div class="col-auto">
                    <button type="button" class="btn btn-outline-danger btn-sm js-remove-city"><i class="ti tabler-x"></i></button>
                  </div>
                </div>
              @endforeach
            @else
              <div class="row g-2 align-items-center city-row">
                <div class="col">
                  <div class="input-group input-group-merge">
                    <span class="input-group-text"><i class="ti tabler-map-pin"></i></span>
                    <input type="text" name="new_cities[]" class="form-control" placeholder="اسم مدينة جديدة">
                  </div>
                </div>
                <div class="col-auto">
                  <button type="button" class="btn btn-outline-danger btn-sm js-remove-city"><i class="ti tabler-x"></i></button>
                </div>
              </div>
            @endif
          </div>

          @if($isEdit && isset($cities) && $cities->count())
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>المدينة</th>
                    <th class="text-center">حذف</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($cities as $city)
                    <tr>
                      <td>
                        <input type="text" name="cities[{{ $city->id }}]" value="{{ old('cities.'.$city->id, $city->name) }}" class="form-control form-control-sm">
                      </td>
                      <td class="text-center">
                        <input type="checkbox" name="delete_cities[]" value="{{ $city->id }}">
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div>{{ method_exists($cities, 'links') ? $cities->links() : '' }}</div>
          @endif
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.geo.index') }}" class="btn btn-outline-secondary">عودة</a>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'حفظ الدولة والمدن' : 'حفظ' }}</button>
      </div>
  </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  function addCityRow(val=''){
    const row = $(
      '<div class="row g-2 align-items-center city-row">\
         <div class="col">\
           <div class="input-group input-group-merge">\
             <span class="input-group-text"><i class="ti tabler-map-pin"></i></span>\
             <input type="text" name="new_cities[]" class="form-control" placeholder="اسم مدينة جديدة"/>\
           </div>\
         </div>\
         <div class="col-auto">\
           <button type="button" class="btn btn-outline-danger btn-sm js-remove-city"><i class="ti tabler-x"></i></button>\
         </div>\
       </div>'
    );
    row.find('input').val(val);
    $('#cities-list').append(row);
  }
  $(document).on('click','.js-add-city', function(){ addCityRow(''); });
  $(document).on('click','.js-remove-city', function(){
    const $rows = $('#cities-list .city-row');
    if ($rows.length > 1) $(this).closest('.city-row').remove(); else $(this).closest('.city-row').find('input').val('');
  });
});
</script>
@endpush

