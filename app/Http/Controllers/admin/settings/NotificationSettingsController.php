<?php

namespace App\Http\Controllers\admin\settings;

use App\Http\Controllers\Controller;
use App\Models\AdminNotificationSetting;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:general_settings');
    }

    /**
     * Display all admin notification settings.
     */
    public function index()
    {
        $settings = AdminNotificationSetting::orderBy('category')
            ->orderBy('id')
            ->get()
            ->groupBy('category');

        $roles = Role::where('guard_name', 'web')->get();
        $users = User::where('status', 'active')->select('id', 'name', 'email')->get();

        return view('admin.settings.notifications.index', compact('settings', 'roles', 'users'));
    }

    /**
     * Quick toggle for a specific channel (AJAX).
     */
    public function toggleChannel(Request $request, $id)
    {
        $request->validate([
            'channel' => 'required|in:in_app,email,webpush',
            'status'  => 'required|boolean',
        ]);

        $setting = AdminNotificationSetting::findOrFail($id);

        $column = match ($request->channel) {
            'in_app'  => 'in_app_enabled',
            'email'   => 'email_enabled',
            'webpush' => 'webpush_enabled',
        };

        $setting->$column = $request->status;
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => __('تم تحديث القناة بنجاح.'),
        ]);
    }

    /**
     * Update full settings for an event (roles, users, custom emails, priority).
     */
    public function update(Request $request, $id)
    {
        $setting = AdminNotificationSetting::findOrFail($id);

        $request->validate([
            'target_roles'    => 'nullable|array',
            'target_user_ids' => 'nullable|array',
            'custom_emails'   => 'nullable|string',
            'priority'        => 'required|in:high,normal,low',
        ]);

        $setting->update([
            'in_app_enabled'  => $request->boolean('in_app_enabled'),
            'email_enabled'   => $request->boolean('email_enabled'),
            'webpush_enabled' => $request->boolean('webpush_enabled'),
            'target_roles'    => $request->input('target_roles', []),
            'target_user_ids' => $request->input('target_user_ids', []),
            'custom_emails'   => $request->input('custom_emails'),
            'priority'        => $request->input('priority', 'normal'),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('تم حفظ إعدادات الإشعار بنجاح.'),
        ]);
    }
}
