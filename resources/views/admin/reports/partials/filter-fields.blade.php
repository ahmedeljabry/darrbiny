@php
  $fields = $fields ?? [];
  $values = $values ?? [];
  $resetUrl = $resetUrl ?? url()->current();
  $title = $title ?? 'الفلاتر';
  $subtitle = $subtitle ?? 'فلترة النتائج حسب أكثر من معيار';
@endphp

<div class="report-filter-card">
  <div class="report-filter-card__header">
    <div class="report-filter-card__title">
      <span class="report-filter-card__icon">
        <i class="icon-base ti tabler-adjustments-horizontal"></i>
      </span>
      <div>
        <h6>{{ $title }}</h6>
        <p>{{ $subtitle }}</p>
      </div>
    </div>
    <div class="report-toolbar-note">
      {{ collect($fields)->filter(fn ($field) => filled($values[$field['name']] ?? null))->count() }} فلتر نشط
    </div>
  </div>

  <form method="get" class="row g-3 align-items-end">
    @foreach($fields as $field)
      @php
        $name = $field['name'];
        $type = $field['type'] ?? 'text';
        $col = $field['col'] ?? 'col-xl-3 col-md-4';
        $value = $values[$name] ?? null;

        if ($value instanceof \DateTimeInterface) {
          $value = match ($type) {
            'date' => $value->format('Y-m-d'),
            'datetime-local' => $value->format('Y-m-d\TH:i'),
            default => $value->format('Y-m-d H:i:s'),
          };
        }
      @endphp

      <div class="{{ $col }}">
        <label class="report-form-label" for="filter-{{ $name }}">{{ $field['label'] }}</label>

        @if($type === 'select')
          <select
            id="filter-{{ $name }}"
            name="{{ $name }}"
            class="form-select report-select"
          >
            <option value="">{{ $field['placeholder'] ?? 'الكل' }}</option>
            @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
              <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
          </select>
        @else
          <input
            id="filter-{{ $name }}"
            type="{{ $type }}"
            name="{{ $name }}"
            value="{{ $value }}"
            class="form-control report-input"
            placeholder="{{ $field['placeholder'] ?? '' }}"
          >
        @endif
      </div>
    @endforeach

    <div class="col-xl-3 col-md-4">
      <div class="d-flex gap-2">
        <button class="btn btn-primary flex-fill">
          <i class="icon-base ti tabler-filter me-1"></i> تطبيق
        </button>
        <a href="{{ $resetUrl }}" class="btn btn-outline-secondary report-reset">
          <i class="icon-base ti tabler-rotate-2 me-1"></i> إعادة
        </a>
      </div>
    </div>
  </form>
</div>
