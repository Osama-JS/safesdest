<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Accounting Invoice') }} - {{ $invoice->invoice_number }}</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 30px 15px;
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .invoice-card {
            background: #fff;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .invoice-header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .invoice-title {
            color: #1e3a8a;
            font-weight: 800;
            font-size: 26px;
            margin-bottom: 5px;
        }
        .invoice-badge {
            display: inline-block;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 20px;
        }
        .badge-unpaid { background-color: #fee2e2; color: #dc2626; }
        .badge-paid { background-color: #dcfce7; color: #16a34a; }
        .badge-approved { background-color: #e0e7ff; color: #4338ca; }
        .badge-cancelled { background-color: #f1f5f9; color: #64748b; }

        .meta-box {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .meta-title {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .meta-val {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table.items-table th {
            background-color: #1e40af;
            color: #fff;
            padding: 12px;
            font-size: 14px;
            text-align: right;
            border: none;
        }
        table.items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13.5px;
            text-align: right;
        }
        table.items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .summary-box {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            float: left;
            width: 320px;
            margin-bottom: 30px;
            border: 1px solid #cbd5e1;
        }
        .summary-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .summary-total {
            font-size: 18px;
            font-weight: 800;
            color: #1e3a8a;
            border-top: 2px dashed #94a3b8;
            padding-top: 10px;
            margin-top: 10px;
        }

        .signatures {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .sig-block {
            text-align: center;
        }
        .sig-line {
            height: 60px;
            border-bottom: 1px dashed #94a3b8;
            margin-bottom: 8px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .invoice-card {
                box-shadow: none;
                border: none;
                padding: 15px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print text-center mb-4">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-bold">
            <i class="ti ti-printer me-1"></i> طباعة الفاتورة المحاسبية
        </button>
        <button onclick="window.close()" class="btn btn-secondary px-4 py-2 ms-2">
            إغلاق
        </button>
    </div>

    <div class="invoice-card">
        <!-- Header -->
        <div class="invoice-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="invoice-title">{{ __('Accounting Invoice') }}</h1>
                <div class="text-muted fs-6">فاتورة ديون مستحقة للمحفظة</div>
            </div>
            <div class="text-start">
                <h3 class="fw-bold mb-1 text-primary">{{ $invoice->invoice_number }}</h3>
                @if($invoice->status === 'paid')
                    <span class="invoice-badge badge-paid">مدفوعة بالكامل</span>
                @elseif($invoice->status === 'approved')
                    <span class="invoice-badge badge-approved">معتمدة رسمياً</span>
                @elseif($invoice->status === 'cancelled')
                    <span class="invoice-badge badge-cancelled">ملغاة</span>
                @else
                    <span class="invoice-badge badge-unpaid">مستحقة للدفع</span>
                @endif
            </div>
        </div>

        <!-- Meta Details -->
        <div class="meta-box">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="meta-title">رقم الفاتورة النظامي:</div>
                    <div class="meta-val">{{ $invoice->invoice_number }}</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="meta-title">الرقم المرجعي المحاسبي:</div>
                    <div class="meta-val">{{ $invoice->accounting_reference_no ?: '-' }}</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="meta-title">تاريخ الإصدار:</div>
                    <div class="meta-val">{{ $invoice->issue_date ? $invoice->issue_date->format('Y-m-d') : '-' }}</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="meta-title">تاريخ الاستحقاق (Maturity):</div>
                    <div class="meta-val {{ $invoice->is_overdue ? 'text-danger' : 'text-primary' }}">
                        {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-' }}
                        @if($invoice->is_overdue)
                            <span class="badge bg-danger ms-1">متأخرة</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer & Wallet Info -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="p-3 bg-light rounded border">
                    <div class="fw-bold text-secondary mb-2">بيانات العميل:</div>
                    <div><strong>الاسم:</strong> {{ $invoice->customer->name ?? '-' }}</div>
                    <div><strong>رقم الجوال:</strong> <span dir="ltr">{{ $invoice->customer->phone ?? '-' }}</span></div>
                    <div><strong>البريد الإلكتروني:</strong> {{ $invoice->customer->email ?? '-' }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-light rounded border">
                    <div class="fw-bold text-secondary mb-2">بيانات المحفظة:</div>
                    <div><strong>رقم المحفظة:</strong> #{{ $invoice->wallet_id }}</div>
                    <div><strong>الرصيد الإجمالي للمحفظة:</strong> {{ number_format($invoice->wallet->balance ?? 0, 2) }} ر.س</div>
                    <div><strong>سقف الدين:</strong> {{ number_format($invoice->wallet->debt_ceiling ?? 0, 2) }} ر.س</div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <h5 class="fw-bold text-dark mb-2">بنود الفاتورة والحركات المالية المربوطة:</h5>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 18%;">رقم الحركة / التسلسل</th>
                    <th style="width: 15%;">رقم المهمة</th>
                    <th style="width: 42%;">الوصف والبيان</th>
                    <th style="width: 20%; text-align: left;">المبلغ (ر.س)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->walletTransaction->sequence ?? $item->wallet_transaction_id }}</td>
                        <td>
                            @if($item->task)
                                {{ $item->task->custom_task_number ?? ('#' . $item->task_id) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $item->walletTransaction->description ?? 'قيمة مهمة / دين محفظة' }}</td>
                        <td style="text-align: left; font-weight: bold;">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">لا توجد بنود مرتبطة بهذه الفاتورة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary & Notes -->
        <div class="row">
            <div class="col-md-7">
                @if($invoice->notes)
                    <div class="p-3 bg-light rounded border mb-3">
                        <strong class="d-block mb-1 text-secondary">ملاحظات الفاتورة:</strong>
                        <p class="mb-0 text-muted" style="white-space: pre-line;">{{ $invoice->notes }}</p>
                    </div>
                @endif

                @if($invoice->attachment_file)
                    <div class="p-3 bg-white rounded border no-print">
                        <strong class="d-block mb-1 text-primary">
                            <i class="ti ti-paperclip me-1"></i> الملف المرفق بالفاتورة:
                        </strong>
                        <a href="{{ asset('storage/' . $invoice->attachment_file) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                            عرض / تحميل المرفق
                        </a>
                    </div>
                @endif
            </div>
            <div class="col-md-5">
                <div class="summary-box w-100">
                    <div class="summary-line">
                        <span>إجمالي الفاتورة:</span>
                        <span class="fw-bold">{{ number_format($invoice->total_amount, 2) }} ر.س</span>
                    </div>
                    <div class="summary-line">
                        <span>المبلغ المسدد:</span>
                        <span class="fw-bold text-success">{{ number_format($invoice->paid_amount, 2) }} ر.س</span>
                    </div>
                    <div class="summary-line summary-total">
                        <span>المبلغ المتبقي:</span>
                        <span class="text-danger">{{ number_format($invoice->remaining_amount, 2) }} ر.س</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="clear: both;"></div>

        <!-- Signatures & Audit -->
        <div class="signatures">
            <div class="row">
                <div class="col-4 sig-block">
                    <div class="text-muted mb-2">منشئ الفاتورة</div>
                    <div class="sig-line"></div>
                    <div class="fw-bold">{{ $invoice->creator->name ?? 'المحاسب' }}</div>
                    <small class="text-muted">{{ $invoice->created_at->format('Y-m-d H:i') }}</small>
                </div>
                <div class="col-4 sig-block">
                    <div class="text-muted mb-2">الاعتماد المحاسبي</div>
                    <div class="sig-line"></div>
                    <div class="fw-bold">{{ $invoice->approver->name ?? 'بانتظار الاعتماد' }}</div>
                    @if($invoice->approved_at)
                        <small class="text-muted">{{ $invoice->approved_at->format('Y-m-d H:i') }}</small>
                    @endif
                </div>
                <div class="col-4 sig-block">
                    <div class="text-muted mb-2">ختم / استلام العميل</div>
                    <div class="sig-line"></div>
                    <div class="fw-bold">{{ $invoice->customer->name ?? 'العميل' }}</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
