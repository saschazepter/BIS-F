<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters;

use App\Services\PersonalDataSelection\Exporters\Base\AbstractExporter;

class UserDataExporter extends AbstractExporter
{
    protected string $fileName = 'user.json';
    protected array  $columns  = [
        'name', 'username', 'home_id', 'private_profile', 'default_status_visibility',
        'default_status_sensitivity', 'prevent_index', 'privacy_hide_days', 'language',
        'timezone', 'friend_checkin', 'likes_enabled', 'points_enabled', 'mapprovider',
        'email', 'email_verified_at', 'privacy_ack_at',
        'last_login', 'created_at', 'updated_at'
    ];

    protected function exportData(): array|string {
        return $this->user->only($this->columns);
    }

    protected function onExportValidation(): bool {
        return true;
    }
}
