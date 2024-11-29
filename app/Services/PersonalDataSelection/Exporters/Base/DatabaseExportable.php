<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters\Base;

use Illuminate\Support\Facades\DB;

trait DatabaseExportable
{
    protected function exportData(): array {
        return DB::table($this->tableName)
                 ->select($this->columns)
                 ->where($this->whereColumn, $this->user->id)
                 ->get()->toArray();
    }

    protected function onExportValidation(): bool {
        return !empty($this->columns) && !empty($this->tableName) && !empty($this->whereColumn);
    }
}
