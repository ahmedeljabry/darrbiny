<!-- Core JS -->
<!-- build:js assets/vendor/js/theme.js  -->

<script src="{{ asset('admin/assets/vendor/libs/jquery/jquery.js') }}"></script>

<script src="{{ asset('admin/assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/node-waves/node-waves.js') }}"></script>

<script src="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

<script src="{{ asset('admin/assets/vendor/libs/hammer/hammer.js') }}"></script>

<script src="{{ asset('admin/assets/vendor/libs/i18n/i18n.js') }}"></script>

<script src="{{ asset('admin/assets/vendor/js/menu.js') }}"></script>

<!-- endbuild -->

<!-- Vendors JS -->
<script src="{{ asset('admin/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>

<script src="{{ asset('admin/assets/vendor/libs/cleave-zen/cleave-zen.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/select2/select2.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Main JS -->

<script src="{{ asset('admin/assets/js/main.js') }}"></script>
<script src="{{ asset('admin/assets/js/table-enhancements.js') }}"></script>

<!-- Page JS -->
<script src="{{ asset('admin/assets/js/app-logistics-dashboard.js') }}"></script>
@stack('scripts')

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

<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof Swal === 'undefined') return;

    function needsConfirm(form) {
      const hasDataAttr = form.matches('[data-confirm="delete"]') || form.classList.contains('js-confirm-delete');
      const methodInput = form.querySelector('input[name="_method"]');
      const methodVal = methodInput ? String(methodInput.value).toUpperCase() : '';
      const isDeleteMethod = methodVal === 'DELETE';
      return hasDataAttr || isDeleteMethod;
    }

    document.addEventListener('submit', function (e) {
      const form = e.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (!needsConfirm(form)) return;
      if (form.dataset.confirmed === 'true') return;

      e.preventDefault();
      Swal.fire({
        title: 'تأكيد الحذف',
        text: 'هل تريد حذف هذا العنصر؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، حذف',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#d33',
      }).then((result) => {
        if (result.isConfirmed) {
          form.dataset.confirmed = 'true';
          form.submit();
        }
      });
    }, true);
  });
</script>
