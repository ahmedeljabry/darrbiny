@include('admin.reports.partials.theme')

@php
  $title = $title ?? '';
  $subtitle = $subtitle ?? '';
  $icon = $icon ?? 'chart-donut-3';
  $tone = $tone ?? 'primary';
  $actions = $actions ?? [];
  $stats = $stats ?? [];
  $tags = $tags ?? [];
@endphp

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
  </ol>
</nav>

<div class="report-hero report-hero--{{ $tone }} mb-4">
  <div class="report-hero__body">
    <div class="report-hero__lead">
      <span class="report-hero__icon bg-label-{{ $tone }}">
        <i class="icon-base ti tabler-{{ $icon }}"></i>
      </span>
      <div class="report-hero__text">
        <h2>{{ $title }}</h2>
        <p>{{ $subtitle }}</p>
        @if(!empty($tags))
          <div class="report-hero__tags">
            @foreach($tags as $tag)
              <span class="report-tag">
                @if(!empty($tag['icon']))
                  <i class="icon-base ti tabler-{{ $tag['icon'] }}"></i>
                @endif
                {{ $tag['label'] }}
              </span>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    @if(!empty($actions))
      <div class="report-hero__actions">
        @foreach($actions as $action)
          <a
            href="{{ $action['url'] }}"
            class="{{ $action['class'] ?? 'btn btn-light' }}"
          >
            @if(!empty($action['icon']))
              <i class="icon-base ti tabler-{{ $action['icon'] }} me-1"></i>
            @endif
            {{ $action['label'] }}
          </a>
        @endforeach
      </div>
    @endif
  </div>

  @if(!empty($stats))
    <div class="row g-3 report-stats mt-2">
      @foreach($stats as $stat)
        <div class="col-xl-3 col-md-6">
          <div class="report-stat">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <div>
                <div class="report-stat__label">{{ $stat['label'] }}</div>
                <p class="report-stat__value">{{ $stat['value'] }}</p>
              </div>
              @if(!empty($stat['icon']))
                <span class="avatar-initial rounded bg-label-{{ $stat['tone'] ?? $tone }}">
                  <i class="icon-base ti tabler-{{ $stat['icon'] }}"></i>
                </span>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
