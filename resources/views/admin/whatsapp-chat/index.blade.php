@extends('layouts/layoutMaster')

@section('title', 'محادثات الواتساب')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
<style>
    .app-chat-sidebar {
        border-right: 1px solid #e0e0e0;
        background: #fff;
    }
    .chat-list-item {
        transition: all 0.2s ease-in-out;
        border-bottom: 1px solid #f0f0f0;
    }
    .chat-list-item:hover {
        background-color: #f8f9fa;
    }
    .chat-list-item.active {
        background-color: #e8f5e9;
        border-right: 4px solid #28c76f;
    }
    .chat-avatar {
        width: 45px;
        height: 45px;
        background-color: #e0e0e0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #fff;
    }
    .chat-avatar.bg-success-light {
        background-color: #28c76f;
    }
    .chat-history-wrapper {
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        background-size: contain;
        background-color: #e5ddd5;
        height: 600px;
        overflow-y: auto;
        padding: 2rem;
    }
    .chat-message {
        display: flex;
        margin-bottom: 1.5rem;
    }
    .chat-message.outbound {
        justify-content: flex-start; /* rtl direction so flex-start means right side */
        flex-direction: row-reverse;
    }
    .chat-message.inbound {
        justify-content: flex-end; /* rtl direction so flex-end means left side */
    }
    .chat-message-text {
        max-width: 65%;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        position: relative;
        font-size: 0.95rem;
    }
    html[dir="rtl"] .chat-message.outbound .chat-message-text {
        background-color: #dcf8c6;
        border-top-right-radius: 0;
        margin-left: auto;
    }
    html[dir="rtl"] .chat-message.inbound .chat-message-text {
        background-color: #fff;
        border-top-left-radius: 0;
        margin-right: auto;
    }
    
    html[dir="ltr"] .chat-message.outbound { justify-content: flex-end; flex-direction: row; }
    html[dir="ltr"] .chat-message.inbound { justify-content: flex-start; }
    html[dir="ltr"] .chat-message.outbound .chat-message-text {
        background-color: #dcf8c6;
        border-top-right-radius: 0;
    }
    html[dir="ltr"] .chat-message.inbound .chat-message-text {
        background-color: #fff;
        border-top-left-radius: 0;
    }

    .chat-meta {
        font-size: 0.75rem;
        color: #999;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 5px;
    }
</style>
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">الواتساب /</span> محادثات العملاء
</h4>

<div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">إجمالي المحادثات</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['total_conversations'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-messages ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">رسائل غير مقروءة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['unread_messages'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-bell-ringing ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">رسائل صادرة (اليوم)</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['messages_sent_today'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-send ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">رسائل واردة (اليوم)</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['messages_received_today'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="ti ti-download ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card overflow-hidden">
            <div class="row g-0">
                <!-- Sidebar -->
                <div class="col-md-4 col-lg-3 app-chat-sidebar" style="height: 600px; overflow-y: auto;">
                    <div class="p-3 border-bottom bg-white sticky-top">
                        <div class="d-flex align-items-center">
                            <div class="chat-avatar bg-success-light me-3">
                                <i class="ti ti-brand-whatsapp"></i>
                            </div>
                            <h5 class="mb-0">محادثات الواتساب</h5>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0" id="conversations-list">
                        @forelse($conversations as $conv)
                        <li class="chat-list-item cursor-pointer conversation-item p-3 d-flex" data-id="{{ $conv->id }}">
                            <div class="chat-avatar bg-primary me-3 flex-shrink-0" style="width: 40px; height:40px; font-size: 1rem;">
                                <i class="ti ti-user"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 text-truncate" style="direction: ltr; text-align: right;">{{ $conv->phone_number }}</h6>
                                    <small class="text-muted text-nowrap ms-2" style="font-size: 0.75rem;">
                                        {{ $conv->last_message_time ? $conv->last_message_time->format('H:i') : '' }}
                                    </small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="mb-0 text-truncate text-muted" style="font-size: 0.85rem;">{{ $conv->last_message_preview }}</p>
                                    @if($conv->unread_count > 0)
                                        <span class="badge bg-danger rounded-pill badge-notifications">{{ $conv->unread_count }}</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="p-4 text-center text-muted">
                            <i class="ti ti-messages mb-2" style="font-size: 2rem;"></i>
                            <p>لا توجد محادثات</p>
                        </li>
                        @endforelse
                    </ul>
                </div>
                <!-- Chat History -->
                <div class="col-md-8 col-lg-9 position-relative">
                    <div class="chat-history-header p-3 border-bottom bg-white sticky-top d-none" id="chat-header">
                        <div class="d-flex align-items-center">
                            <div class="chat-avatar bg-primary me-3">
                                <i class="ti ti-user"></i>
                            </div>
                            <div>
                                <h6 class="mb-0" id="chat-header-phone" style="direction: ltr;">-</h6>
                                <small class="text-muted">محادثة نشطة</small>
                            </div>
                        </div>
                    </div>
                    <div id="chat-messages" class="chat-history-wrapper d-flex flex-column">
                        <div class="m-auto text-center text-muted" id="chat-placeholder">
                            <i class="ti ti-brand-whatsapp mb-3" style="font-size: 4rem; opacity: 0.5;"></i>
                            <h5>اختر محادثة للبدء</h5>
                            <p>يتم عرض الرسائل هنا فور اختيارك لإحدى المحادثات من القائمة</p>
                        </div>
                    </div>
                    <!-- Chat Input -->
                    <div class="chat-history-footer p-3 bg-white border-top d-none" id="chat-footer">
                        <form id="chat-form" class="d-flex align-items-center">
                            @csrf
                            <input type="text" class="form-control me-2" id="chat-input" placeholder="اكتب رسالتك هنا..." required autocomplete="off">
                            <button type="submit" class="btn btn-success d-flex align-items-center" id="send-btn">
                                <i class="ti ti-send me-1"></i> إرسال
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
<script type="module">
$(document).ready(function() {
    $('.conversation-item').on('click', function() {
        // UI Updates
        $('.conversation-item').removeClass('active');
        $(this).addClass('active');
        $(this).find('.badge').remove(); 
        
        let id = $(this).data('id');
        let phone = $(this).find('h6').text();
        let chatArea = $('#chat-messages');
        
        // Show Header
        $('#chat-header').removeClass('d-none');
        $('#chat-header-phone').text(phone);
        
        // Loader
        chatArea.html('<div class="m-auto text-center"><div class="spinner-border text-success" role="status"></div></div>');
        
        $.ajax({
            url: "{{ url('admin/whatsapp-chat') }}/" + id + "/messages",
            type: "GET",
            success: function(response) {
                let html = '';
                if(response.messages.length === 0) {
                    html = '<div class="m-auto text-center text-muted"><p class="bg-white p-2 rounded shadow-sm">لا توجد رسائل سابقة</p></div>';
                } else {
                    response.messages.forEach(function(msg) {
                        let isOut = msg.direction === 'outbound';
                        let alignmentClass = isOut ? 'outbound' : 'inbound';
                        
                        let statusIcon = '';
                        if(isOut) {
                            if(msg.status === 'pending') statusIcon = '<i class="ti ti-clock"></i>';
                            else if(msg.status === 'sent') statusIcon = '<i class="ti ti-check"></i>';
                            else if(msg.status === 'delivered') statusIcon = '<i class="ti ti-checks"></i>';
                            else if(msg.status === 'read') statusIcon = '<i class="ti ti-checks text-info"></i>';
                            else if(msg.status === 'failed') statusIcon = '<i class="ti ti-alert-circle text-danger"></i>';
                        }
                        
                        html += `
                            <div class="chat-message ${alignmentClass}">
                                <div class="chat-message-text">
                                    <div class="text-break">${msg.content.replace(/\n/g, '<br>')}</div>
                                    <div class="chat-meta">
                                        <span class="time">${msg.time}</span>
                                        ${statusIcon}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                chatArea.html(html);
                chatArea.scrollTop(chatArea[0].scrollHeight);
            },
            error: function() {
                chatArea.html('<div class="m-auto text-center text-danger"><p class="bg-white p-2 rounded shadow-sm">حدث خطأ أثناء جلب الرسائل</p></div>');
            }
        });
    });

    let currentConversationId = null;

    $('.conversation-item').on('click', function() {
        currentConversationId = $(this).data('id');
        $('#chat-footer').removeClass('d-none');
    });

    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        let input = $('#chat-input');
        let message = input.val().trim();
        if (!message || !currentConversationId) return;

        let btn = $(this).find('button');
        btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> إرسال');

        $.ajax({
            url: "{{ url('admin/whatsapp-chat') }}/" + currentConversationId + "/send",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                message: message
            },
            success: function(response) {
                if (response.status === 'error' && response.code === 'window_closed') {
                    btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> إرسال');
                    
                    Swal.fire({
                        title: 'عذراً',
                        text: response.message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'إرسال قالب open_chat',
                        cancelButtonText: 'إلغاء',
                        customClass: {
                            confirmButton: 'btn btn-primary me-3',
                            cancelButton: 'btn btn-label-secondary'
                        },
                        buttonsStyling: false
                    }).then(function (result) {
                        if (result.value) {
                            sendOpenChatTemplate(currentConversationId);
                        }
                    });
                    return;
                }

                input.val('');
                btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> إرسال');

                let html = `
                    <div class="chat-message outbound">
                        <div class="chat-message-text">
                            <div class="text-break">${message.replace(/\n/g, '<br>')}</div>
                            <div class="chat-meta">
                                <span class="time">الآن</span>
                                <i class="ti ti-clock"></i>
                            </div>
                        </div>
                    </div>
                `;
                $('#chat-messages').append(html);
                let chatArea = $('#chat-messages');
                chatArea.scrollTop(chatArea[0].scrollHeight);
            },
            error: function() {
                toastr.error('فشل إرسال الرسالة');
                btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> إرسال');
            }
        });
    });

    function sendOpenChatTemplate(conversationId) {
        $.ajax({
            url: "{{ url('admin/whatsapp-chat') }}/" + conversationId + "/send-open-chat",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.status === 'error') {
                    toastr.error(response.message);
                } else {
                    toastr.success(response.message);
                    let html = `
                        <div class="chat-message outbound">
                            <div class="chat-message-text">
                                <div class="text-break text-muted"><em>Template: open_chat</em></div>
                                <div class="chat-meta">
                                    <span class="time">الآن</span>
                                    <i class="ti ti-clock"></i>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#chat-messages').append(html);
                    let chatArea = $('#chat-messages');
                    chatArea.scrollTop(chatArea[0].scrollHeight);
                }
            },
            error: function() {
                toastr.error('حدث خطأ أثناء إرسال قالب المحادثة.');
            }
        });
    }
});
</script>
@endsection
