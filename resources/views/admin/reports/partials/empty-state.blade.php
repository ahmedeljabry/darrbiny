@php
  $icon = $icon ?? 'database-off';
  $message = $message ?? 'لا توجد بيانات';
  $colspan = $colspan ?? 1;
@endphp

<tr>
  <td colspan="{{ $colspan }}" class="text-center">
    <div class="report-empty">
      <span class="report-empty__icon">
        <i class="icon-base ti tabler-{{ $icon }}"></i>
      </span>
      <div>{{ $message }}</div>
    </div>
  </td>
</tr>
