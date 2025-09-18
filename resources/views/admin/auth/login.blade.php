<!doctype html>

<html lang="{{ app()->getLocale() == 'en' ?: 'ar' }}"
  class="layout-wide customizer-hide"
  dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}"
  data-skin="default"
  data-bs-theme="light"
  data-assets-path="{{ asset('admin/assets/') }}"
  data-template="vertical-menu-template-no-customizer">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>تسجيل دخول الإدارة</title>

    <meta name="description" content="تسجيل دخول لوحة الإدارة" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('admin/assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/fonts/iconify-icons.css') }}" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/node-waves/node-waves.css') }}" />

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/css/demo.css') }}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    <!-- endbuild -->

    <!-- Vendor -->
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/@form-validation/form-validation.css') }}" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/pages/page-auth.css') }}" />

    <!-- Helpers -->
    <script src="{{ asset('admin/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('admin/assets/js/config.js') }}"></script>
  </head>

  <body>
    <!-- Content -->

    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-6">
          <!-- Login -->
          <div class="card">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center mb-6">
                <a href="{{ route('admin.login') }}" class="app-brand-link">
                  <span class="app-brand-logo demo">
                    <span class="text-primary">
                      <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z"
                          fill="currentColor" />
                        <path
                          opacity="0.06"
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M7.69824 16.4364L12.5199 3.23696L16.5541 7.25596L7.69824 16.4364Z"
                          fill="#161616" />
                        <path
                          opacity="0.06"
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M8.07751 15.9175L13.9419 4.63989L16.5849 7.28475L8.07751 15.9175Z"
                          fill="#161616" />
                        <path
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M7.77295 16.3566L23.6563 0H32V6.88383C32 6.88383 31.8262 9.17836 30.6591 10.4057L19.7824 22H13.6938L7.77295 16.3566Z"
                          fill="currentColor" />
                      </svg>
                    </span>
                  </span>
                  <span class="app-brand-text demo text-heading fw-bold">Admin</span>
                </a>
              </div>
              <!-- /Logo -->
              <h4 class="mb-1">مرحباً بك! 👋</h4>
              <p class="mb-6">الرجاء تسجيل الدخول لإدارة اللوحة</p>

              @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                  <ul class="mb-0">
                      @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                      @endforeach
                  </ul>
                </div>
              @endif

              <form id="formAuthentication" class="mb-4" action="{{ route('admin.login.post') }}" method="POST">
                @csrf
                <div class="mb-6 form-control-validation">
                  <label for="login" class="form-label">البريد الإلكتروني أو اسم المستخدم</label>
                  <input
                    type="text"
                    class="form-control"
                    id="login"
                    name="login"
                    value="{{ old('login') }}"
                    placeholder="أدخل البريد الإلكتروني أو اسم المستخدم"
                    autofocus />
                </div>
                <div class="mb-6 form-password-toggle form-control-validation">
                  <label class="form-label" for="password">كلمة المرور</label>
                  <div class="input-group input-group-merge">
                    <input
                      type="password"
                      id="password"
                      class="form-control"
                      name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password" />
                    <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
                  </div>
                </div>
                <div class="my-8">
                  <div class="d-flex justify-content-between">
                    <div class="form-check mb-0 ms-2">
                      <input class="form-check-input" type="checkbox" id="remember-me" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} />
                      <label class="form-check-label" for="remember-me"> تذكرني </label>
                    </div>
                  </div>
                </div>
                <div class="mb-6">
                  <button class="btn btn-primary d-grid w-100" type="submit">تسجيل الدخول</button>
                </div>
              </form>

            </div>
          </div>
          <!-- /Login -->
        </div>
      </div>
    </div>

    <!-- / Content -->

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
    <script src="{{ asset('admin/assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>

    <!-- Main JS -->

    <script src="{{ asset('admin/assets/js/main.js') }}"></script>

    <!-- Page JS -->
    <script src="{{ asset('admin/assets/js/pages-auth.js') }}"></script>
  </body>
</html>
