@extends('layouts/layoutMaster')

@section('title', 'الملف الشخصي')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة المبسط --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-label-primary border-0 shadow-none">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <span class="avatar-initial rounded-circle bg-primary"><i class="ti ti-user-circle ti-md"></i></span>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold text-primary">{{ $investor->name }}</h4>
                            <p class="mb-0 small text-muted">إدارة الملف الشخصي والإعدادات الأمنية</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3 mt-sm-0">
                        <span class="badge bg-label-success px-3 py-2"><i class="ti ti-shield-check me-1"></i>مستثمر معتمد</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach(['success','error'] as $msg)
        @if(session($msg))
            <div class="alert alert-{{ $msg === 'error' ? 'danger' : $msg }} alert-dismissible mb-4" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                {{ session($msg) }}
            </div>
        @endif
    @endforeach

    <div class="row g-4">
        {{-- القائمة الجانبية للملف --}}
        <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
            {{-- بطاقة العقد --}}
            @if($investor->activeInvestmentContract)
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <small class="card-text text-uppercase text-muted small">تفاصيل العقد الحالي</small>
                    @php $c = $investor->activeInvestmentContract; @endphp
                    <div class="mt-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="badge bg-label-primary rounded p-2 me-2"><i class="ti ti-certificate ti-sm"></i></div>
                            <div>
                                <small class="text-muted d-block small">نوع العقد</small>
                                <span class="fw-medium text-dark">{{ $c->contract_type === 'task_investment' ? 'استثمار بالمهام' : 'استثمار عام' }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="badge bg-label-success rounded p-2 me-2"><i class="ti ti-currency-dollar ti-sm"></i></div>
                            <div>
                                <small class="text-muted d-block small">العمولة المتفق عليها</small>
                                <span class="fw-medium text-dark">{{ $c->commission_value }}{{ $c->commission_type === 'percentage' ? '%' : ' ر.س' }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="badge bg-label-info rounded p-2 me-2"><i class="ti ti-calendar ti-sm"></i></div>
                            <div>
                                <small class="text-muted d-block small">تاريخ البدء</small>
                                <span class="fw-medium text-dark">{{ $c->start_date->format('Y-m-d') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- بطاقة تواصل --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3">هل تحتاج للمساعدة؟</h6>
                    <p class="text-muted small">إذا كان لديك أي استفسار حول حسابك أو استثماراتك، يمكنك التواصل مع الدعم الفني.</p>
                    <button class="btn btn-label-secondary w-100"><i class="ti ti-headset me-1"></i>تواصل معنا</button>
                </div>
            </div>
        </div>

        {{-- نماذج التعديل --}}
        <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
            {{-- تبويبات --}}
            <div class="nav-align-top mb-4">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-profile" aria-controls="navs-pills-top-profile" aria-selected="true">
                            <i class="ti ti-user-edit me-1"></i> المعلومات الشخصية
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-security" aria-controls="navs-pills-top-security" aria-selected="false">
                            <i class="ti ti-lock me-1"></i> الأمان وكلمة المرور
                        </button>
                    </li>
                </ul>
                <div class="tab-content border-0 p-0 bg-transparent shadow-none">
                    {{-- البيانات الشخصية --}}
                    <div class="tab-pane fade show active" id="navs-pills-top-profile" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="{{ route('investor.profile.update') }}">
                                    @csrf @method('PUT')
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label">الاسم الكامل</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="ti ti-user"></i></span>
                                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $investor->name) }}" required>
                                            </div>
                                            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label">البريد الإلكتروني</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="ti ti-mail"></i></span>
                                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $investor->email) }}" required>
                                            </div>
                                            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3 col-md-3">
                                            <label class="form-label">رمز الدولة</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="ti ti-world"></i></span>
                                                <select name="phone_code" class="form-select @error('phone_code') is-invalid @enderror">
                                                    <option value="+966" {{ old('phone_code', $investor->phone_code) === '+966' ? 'selected' : '' }}>السعودية (+966)</option>
                                                    <option value="+971" {{ old('phone_code', $investor->phone_code) === '+971' ? 'selected' : '' }}>الإمارات (+971)</option>
                                                    <option value="+965" {{ old('phone_code', $investor->phone_code) === '+965' ? 'selected' : '' }}>الكويت (+965)</option>
                                                    <option value="+974" {{ old('phone_code', $investor->phone_code) === '+974' ? 'selected' : '' }}>قطر (+974)</option>
                                                    <option value="+973" {{ old('phone_code', $investor->phone_code) === '+973' ? 'selected' : '' }}>البحرين (+973)</option>
                                                    <option value="+968" {{ old('phone_code', $investor->phone_code) === '+968' ? 'selected' : '' }}>عمان (+968)</option>
                                                    <option value="+962" {{ old('phone_code', $investor->phone_code) === '+962' ? 'selected' : '' }}>الأردن (+962)</option>
                                                    <option value="+20"  {{ old('phone_code', $investor->phone_code) === '+20' ? 'selected' : '' }}>مصر (+20)</option>
                                                </select>
                                            </div>
                                            @error('phone_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label">رقم الجوال</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="ti ti-phone"></i></span>
                                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $investor->phone) }}">
                                            </div>
                                            @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary me-2">حفظ التغييرات</button>
                                        <button type="reset" class="btn btn-label-secondary">إلغاء</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    {{-- الأمان --}}
                    <div class="tab-pane fade" id="navs-pills-top-security" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="{{ route('investor.password.update') }}">
                                    @csrf @method('PUT')
                                    <div class="row">
                                        <div class="mb-3 col-md-12">
                                            <label class="form-label">كلمة المرور الحالية</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="ti ti-key"></i></span>
                                                <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                                            </div>
                                            @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label">كلمة المرور الجديدة</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                            </div>
                                            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label">تأكيد كلمة المرور</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="ti ti-lock-check"></i></span>
                                                <input type="password" name="password_confirmation" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-warning">تحديث كلمة المرور</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
