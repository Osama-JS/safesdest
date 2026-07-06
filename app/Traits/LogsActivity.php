<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Boot the trait to listen for model events.
     */
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            static::logActivity($model, 'إنشاء', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getDirty());
            $newValues = $model->getDirty();
            
            if (!empty($newValues)) {
                static::logActivity($model, 'تحديث', $oldValues, $newValues);
            }
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'حذف', $model->getOriginal(), null);
        });
    }

    /**
     * Log the activity to the database.
     */
    protected static function logActivity($model, $action, $oldValues, $newValues)
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        
        $sensitiveFields = ['password', 'remember_token'];
        
        if ($oldValues) {
            foreach ($sensitiveFields as $field) {
                if (isset($oldValues[$field])) {
                    $oldValues[$field] = '********';
                }
            }
        }

        if ($newValues) {
            foreach ($sensitiveFields as $field) {
                if (isset($newValues[$field])) {
                    $newValues[$field] = '********';
                }
            }
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'table_name' => $model->getTable(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(), // تسجيل عنوان الـ IP
        ]);
    }
}
