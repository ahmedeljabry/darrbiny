@php
  $editing = isset($user);
@endphp
@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row g-4">
  <div class="col-md-6">
    <label class="form-label">الاسم</label>
    <input type="text" class="form-control" name="name" value="{{ old('name', $editing ? $user->name : '') }}" />
  </div>
  <div class="col-md-6">
    <label class="form-label">البريد الإلكتروني</label>
    <input type="email" class="form-control" name="email" value="{{ old('email', $editing ? $user->email : '') }}" />
  </div>
  <div class="col-md-6">
    <label class="form-label">الهاتف</label>
    <input type="text" class="form-control" name="phone_with_cc" value="{{ old('phone_with_cc', $editing ? $user->phone_with_cc : '') }}" required />
  </div>
  <div class="col-md-6">
    <label class="form-label">كلمة المرور</label>
    <input type="password" class="form-control" name="password" placeholder="{{ $editing ? 'اتركه فارغاً للإبقاء عليه' : '' }}" />
  </div>
  <div class="col-md-6">
    <label class="form-label">الدولة</label>
    <select name="country_id" class="form-select">
      <option value="">—</option>
      @foreach($countries as $c)
        <option value="{{ $c->id }}" @selected(old('country_id', $editing ? $user->country_id : null) == $c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">المدينة</label>
    <select name="city_id" class="form-select">
      <option value="">—</option>
      @foreach($cities as $ct)
        <option value="{{ $ct->id }}" @selected(old('city_id', $editing ? $user->city_id : null) == $ct->id)>{{ $ct->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-6">
    <div class="form-check form-switch mt-4">
      <input class="form-check-input" type="checkbox" id="whatsapp_enabled" name="whatsapp_enabled" value="1" {{ old('whatsapp_enabled', $editing ? $user->whatsapp_enabled : false) ? 'checked' : '' }}>
      <label class="form-check-label" for="whatsapp_enabled">تفعيل واتساب</label>
    </div>
  </div>
  <div class="col-md-6">
    <label class="form-label">الأدوار</label>
    <div class="d-flex gap-3 flex-wrap">
      @foreach($roles as $roleName)
        @php $checked = in_array($roleName, old('roles', $editing ? $user->getRoleNames()->toArray() : [])); @endphp
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $roleName }}" id="role_{{ $roleName }}" {{ $checked ? 'checked' : '' }}>
          <label class="form-check-label" for="role_{{ $roleName }}">{{ $roleName }}</label>
        </div>
      @endforeach
    </div>
  </div>
  <div class="col-md-6">
    <label class="form-label">حظر حتى</label>
    <input type="datetime-local" class="form-control" name="banned_until" value="{{ old('banned_until', $editing && $user->banned_until ? $user->banned_until->format('Y-m-d\TH:i') : '') }}">
  </div>
  <div class="col-md-6">
    <label class="form-label">سبب الحظر</label>
    <input type="text" class="form-control" name="banned_reason" value="{{ old('banned_reason', $editing ? $user->banned_reason : '') }}" />
  </div>
</div>

<div class="d-flex gap-2 justify-content-end mt-4">
  <button type="submit" class="btn btn-primary">حفظ</button>
  <a href="{{ route('admin.users.index') }}" class="btn btn-label-secondary">إلغاء</a>
</div>

