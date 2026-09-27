<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AdminNotificationSetting extends Model
{
    protected $table = 'admin_notification_settings';

    protected $fillable = [
        'event_key',
        'category',
        'name_ar',
        'name_en',
        'description_ar',
        'in_app_enabled',
        'email_enabled',
        'webpush_enabled',
        'target_roles',
        'target_user_ids',
        'custom_emails',
        'priority',
    ];

    protected $casts = [
        'in_app_enabled'  => 'boolean',
        'email_enabled'   => 'boolean',
        'webpush_enabled' => 'boolean',
        'target_roles'    => 'array',
        'target_user_ids' => 'array',
    ];

    /**
     * Get active recipient users for this notification event.
     *
     * @return Collection
     */
    public function getRecipientUsers(): Collection
    {
        $userIds = collect($this->target_user_ids ?? [])->filter();
        $roles = collect($this->target_roles ?? [])->filter();

        $query = User::where('status', 'active');

        if ($userIds->isNotEmpty() || $roles->isNotEmpty()) {
            $query->where(function ($q) use ($userIds, $roles) {
                if ($userIds->isNotEmpty()) {
                    $q->whereIn('id', $userIds);
                }
                if ($roles->isNotEmpty()) {
                    $q->orWhereHas('roles', function ($rq) use ($roles) {
                        $rq->whereIn('name', $roles);
                    });
                }
            });
        } else {
            // Default fallback: users with role 'Owner' or 'Admin' or who have 'view_notifications'
            $query->where(function ($q) {
                $q->whereHas('roles', function ($rq) {
                    $rq->whereIn('name', ['Owner', 'Admin']);
                })->orWhereHas('permissions', function ($pq) {
                    $pq->where('name', 'view_notifications');
                });
            });
        }

        return $query->get();
    }

    /**
     * Get all target email addresses for this notification event.
     *
     * @return array
     */
    public function getRecipientEmails(): array
    {
        $emails = [];

        // 1. From recipient users
        $users = $this->getRecipientUsers();
        foreach ($users as $u) {
            if (!empty($u->email) && filter_var($u->email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower(trim($u->email));
            }
        }

        // 2. From custom emails field
        if (!empty($this->custom_emails)) {
            $parsed = preg_split('/[\s,;]+/', $this->custom_emails);
            foreach ($parsed as $email) {
                $email = strtolower(trim($email));
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $email;
                }
            }
        }

        return array_unique(array_filter($emails));
    }
}
