@once
  @push('styles')
    <style>
      .report-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(106, 125, 156, 0.12);
        border-radius: 26px;
        padding: 1.5rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 48%, #eef4ff 100%);
        box-shadow: 0 18px 45px rgba(47, 43, 61, 0.08);
      }

      .report-hero::before,
      .report-hero::after {
        content: "";
        position: absolute;
        border-radius: 999px;
        opacity: 0.18;
        pointer-events: none;
      }

      .report-hero::before {
        width: 220px;
        height: 220px;
        top: -90px;
        inset-inline-end: -70px;
        background: currentColor;
        filter: blur(18px);
      }

      .report-hero::after {
        width: 140px;
        height: 140px;
        bottom: -60px;
        inset-inline-start: -30px;
        background: rgba(255, 255, 255, 0.8);
      }

      .report-hero--success { color: #1b7f5f; }
      .report-hero--primary { color: #4566d8; }
      .report-hero--info { color: #0e7490; }
      .report-hero--warning { color: #b7791f; }
      .report-hero--danger { color: #b42318; }
      .report-hero--secondary { color: #5b667a; }

      .report-hero__body,
      .report-stats {
        position: relative;
        z-index: 1;
      }

      .report-hero__body {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.25rem;
        flex-wrap: wrap;
      }

      .report-hero__lead {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        min-width: 0;
      }

      .report-hero__icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.45);
      }

      .report-hero__text h2 {
        font-size: 1.4rem;
        margin-bottom: 0.35rem;
        color: #1f2937;
      }

      .report-hero__text p {
        margin-bottom: 0;
        color: #5f6b7a;
        max-width: 760px;
      }

      .report-hero__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.85rem;
      }

      .report-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.42rem 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(106, 125, 156, 0.14);
        color: #334155;
        font-size: 0.85rem;
      }

      .report-hero__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: flex-end;
      }

      .report-panel {
        border: 0;
        border-radius: 24px;
        box-shadow: 0 14px 40px rgba(47, 43, 61, 0.08);
        overflow: hidden;
      }

      .report-filter-card {
        border: 1px solid #edf2f7;
        border-radius: 20px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
        padding: 1rem;
        margin-bottom: 1.25rem;
      }

      .report-filter-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
      }

      .report-filter-card__title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .report-filter-card__title h6 {
        margin: 0;
        color: #1f2937;
      }

      .report-filter-card__title p {
        margin: 0.2rem 0 0;
        color: #64748b;
        font-size: 0.88rem;
      }

      .report-filter-card__icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef4ff;
        color: #4566d8;
      }

      .report-form-label {
        font-size: 0.82rem;
        font-weight: 700;
        color: #445166;
        margin-bottom: 0.45rem;
      }

      .report-input,
      .report-select {
        min-height: 46px;
        border-radius: 14px;
        border-color: #dbe3ef;
        box-shadow: none;
      }

      .report-input:focus,
      .report-select:focus {
        border-color: rgba(69, 102, 216, 0.5);
        box-shadow: 0 0 0 0.2rem rgba(69, 102, 216, 0.12);
      }

      .report-stats .report-stat {
        border-radius: 18px;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid rgba(106, 125, 156, 0.12);
        backdrop-filter: blur(8px);
        min-height: 100%;
      }

      .report-stat__label {
        color: #64748b;
        font-size: 0.84rem;
        margin-bottom: 0.45rem;
      }

      .report-stat__value {
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
      }

      .report-table {
        margin-bottom: 0;
      }

      .report-table thead th {
        padding-top: 0.95rem;
        padding-bottom: 0.95rem;
        border-bottom: 0;
        background: #f7f9fc;
        color: #475569;
        font-size: 0.82rem;
        letter-spacing: 0.01em;
      }

      .report-table tbody td {
        vertical-align: middle;
        padding-top: 0.95rem;
        padding-bottom: 0.95rem;
        border-color: #eef2f7;
      }

      .report-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 2rem 1rem;
        color: #64748b;
      }

      .report-empty__icon {
        width: 68px;
        height: 68px;
        border-radius: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: #64748b;
        font-size: 1.75rem;
      }

      .report-toolbar-note {
        color: #64748b;
        font-size: 0.88rem;
      }

      .report-reset {
        white-space: nowrap;
      }

      .report-directory-card {
        position: relative;
        display: block;
        height: 100%;
        border: 1px solid rgba(106, 125, 156, 0.12);
        border-radius: 22px;
        padding: 1.1rem;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
        box-shadow: 0 12px 30px rgba(47, 43, 61, 0.06);
        text-decoration: none;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
      }

      .report-directory-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 38px rgba(47, 43, 61, 0.1);
        border-color: rgba(69, 102, 216, 0.2);
      }

      .report-directory-card__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.9rem;
      }

      .report-directory-card__icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
      }

      .report-directory-card__title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
      }

      .report-directory-card__desc {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 0;
        min-height: 2.7rem;
      }

      .report-directory-card__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1rem;
        color: #4566d8;
        font-size: 0.88rem;
        font-weight: 600;
      }

      .report-note-box {
        border: 1px dashed rgba(69, 102, 216, 0.24);
        border-radius: 18px;
        padding: 0.95rem 1rem;
        background: linear-gradient(180deg, rgba(238, 244, 255, 0.7) 0%, rgba(255, 255, 255, 0.9) 100%);
      }

      .report-note-box p {
        margin: 0;
        color: #475569;
      }

      .report-status {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        padding: 0.34rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 700;
      }

      .report-status--success {
        color: #0f766e;
        background: #ecfdf5;
      }

      .report-status--warning {
        color: #b45309;
        background: #fffbeb;
      }

      .report-status--danger {
        color: #b42318;
        background: #fef2f2;
      }

      .report-status--primary {
        color: #1d4ed8;
        background: #eff6ff;
      }

      .report-status--secondary {
        color: #475569;
        background: #f1f5f9;
      }

      @media (max-width: 767.98px) {
        .report-hero {
          padding: 1.15rem;
          border-radius: 22px;
        }

        .report-panel {
          border-radius: 20px;
        }

        .report-filter-card {
          padding: 0.9rem;
          border-radius: 18px;
        }

        .report-hero__actions {
          width: 100%;
          justify-content: stretch;
        }

        .report-hero__actions .btn {
          flex: 1 1 calc(50% - 0.5rem);
        }
      }
    </style>
  @endpush
@endonce
