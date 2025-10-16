<div class="card mt-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div>
      <h5 class="mb-0">مميزات الباقة</h5>
      <small class="text-body-secondary">أضف نقاط القوة التي تميّز هذه الخطة</small>
    </div>
    <button type="button" class="btn btn-sm btn-primary js-add-feature">
      <i class="ti tabler-plus"></i>
      إضافة ميزة
    </button>
  </div>
  <div class="card-body">
    @php
      $featureValues = old('features', isset($plan) && $plan ? $plan->features->pluck('label')->toArray() : []);
      if (empty($featureValues)) { $featureValues = ['']; }
    @endphp
    <div id="features-list" class="d-flex flex-column gap-2">
      @foreach($featureValues as $feature)
        <div class="row g-2 align-items-center feature-row">
          <div class="col">
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="icon-base ti tabler-check"></i></span>
              <input type="text" name="features[]" class="form-control" value="{{ $feature }}" placeholder="اكتب ميزة للخطة">
            </div>
          </div>
          <div class="col-auto">
            <button type="button" class="btn btn-outline-danger btn-sm js-remove-feature" title="حذف"><i class="ti tabler-x"></i></button>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      function addFeatureRow(value = '') {
        const row = $(
          '<div class="row g-2 align-items-center feature-row">\
             <div class="col">\
               <div class="input-group input-group-merge">\
                 <span class="input-group-text"><i class="icon-base ti tabler-check"></i></span>\
                 <input type="text" name="features[]" class="form-control" placeholder="اكتب ميزة للخطة"/>\
               </div>\
             </div>\
             <div class="col-auto">\
               <button type="button" class="btn btn-outline-danger btn-sm js-remove-feature" title="حذف"><i class="ti tabler-x"></i></button>\
             </div>\
           </div>'
        );
        row.find('input').val(value || '');
        $('#features-list').append(row);
      }

      $(document).on('click', '.js-add-feature', function(){
        addFeatureRow('');
      });

      $(document).on('click', '.js-remove-feature', function(){
        const $rows = $('#features-list .feature-row');
        if ($rows.length > 1) {
          $(this).closest('.feature-row').remove();
        } else {
          $(this).closest('.feature-row').find('input').val('');
        }
      });
    });
  </script>
@endpush

