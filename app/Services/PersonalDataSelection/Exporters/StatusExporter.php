<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters;

use App\Services\PersonalDataSelection\Exporters\Base\AbstractExporter;

class StatusExporter extends AbstractExporter
{
    protected string $fileName = 'statuses.json';

    public function exportData(): array|string {
        return $this->user->statuses()->with('tags')->get()->toArray(); // todo: columns definieren
    }
}
