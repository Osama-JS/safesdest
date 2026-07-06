<div class="row g-4">
    <!-- معلومات أساسية -->
    <div class="col-12">
        <div class="card bg-label-secondary border-0 shadow-none">
            <div class="card-header border-bottom pb-3">
                <h6 class="card-title mb-0"><i class="ti ti-info-circle me-1"></i> المعلومات الأساسية</h6>
            </div>
            <div class="card-body pt-3">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <span class="text-muted d-block mb-1">المستخدم</span>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2">
                                <span class="avatar-initial rounded-circle bg-primary"><i class="ti ti-user"></i></span>
                            </div>
                            <span class="fw-medium text-heading">{{ $log->user ? $log->user->name : 'غير معروف' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <span class="text-muted d-block mb-1">الجدول المتأثر</span>
                        <span class="badge bg-label-info">{{ $log->table_name }}</span>
                    </div>
                    <div class="col-md-6 mb-3 mb-md-0">
                        <span class="text-muted d-block mb-1">نوع الإجراء</span>
                        @if($log->action == 'إنشاء')
                            <span class="badge bg-label-success"><i class="ti ti-plus me-1"></i> {{ $log->action }}</span>
                        @elseif($log->action == 'تحديث')
                            <span class="badge bg-label-warning"><i class="ti ti-edit me-1"></i> {{ $log->action }}</span>
                        @else
                            <span class="badge bg-label-danger"><i class="ti ti-trash me-1"></i> {{ $log->action }}</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted d-block mb-1">تاريخ العملية ووقت الـ IP</span>
                        <span class="d-block text-heading"><i class="ti ti-calendar me-1"></i> {{ $log->created_at->format('Y-m-d h:i A') }}</span>
                        <span class="d-block text-muted small mt-1"><i class="ti ti-network me-1"></i> {{ $log->ip_address ?? 'غير متوفر' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- التفاصيل والتغييرات -->
    <div class="col-12">
        <div class="card border border-label-primary shadow-none">
            <div class="card-header border-bottom pb-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="ti ti-list-details me-1"></i> تفاصيل البيانات</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-borderless mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>اسم الحقل (Field)</th>
                                @if($log->action == 'تحديث' || $log->action == 'حذف')
                                    <th class="text-danger">القيمة القديمة</th>
                                @endif
                                @if($log->action == 'تحديث' || $log->action == 'إنشاء')
                                    <th class="text-success">القيمة الجديدة</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $keys = [];
                                if (is_array($log->old_values)) {
                                    $keys = array_merge($keys, array_keys($log->old_values));
                                }
                                if (is_array($log->new_values)) {
                                    $keys = array_merge($keys, array_keys($log->new_values));
                                }
                                $keys = array_unique($keys);
                            @endphp

                            @forelse($keys as $key)
                                <tr>
                                    <td class="fw-medium"><code>{{ $key }}</code></td>
                                    
                                    @if($log->action == 'تحديث' || $log->action == 'حذف')
                                        <td>
                                            @if(isset($log->old_values[$key]))
                                                @if(is_array($log->old_values[$key]))
                                                    <pre class="mb-0 text-wrap">{{ json_encode($log->old_values[$key], JSON_UNESCAPED_UNICODE) }}</pre>
                                                @else
                                                    {{ $log->old_values[$key] }}
                                                @endif
                                            @else
                                                <span class="text-muted fst-italic">فارغ</span>
                                            @endif
                                        </td>
                                    @endif

                                    @if($log->action == 'تحديث' || $log->action == 'إنشاء')
                                        <td>
                                            @if(isset($log->new_values[$key]))
                                                @if(is_array($log->new_values[$key]))
                                                    <pre class="mb-0 text-wrap">{{ json_encode($log->new_values[$key], JSON_UNESCAPED_UNICODE) }}</pre>
                                                @else
                                                    {{ $log->new_values[$key] }}
                                                @endif
                                            @else
                                                <span class="text-muted fst-italic">فارغ</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">لا توجد تفاصيل لعرضها</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
