<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters;

use App\Models\Event;
use App\Services\PersonalDataSelection\Exporters\Base\AbstractExporter;
use App\Services\PersonalDataSelection\Exporters\Base\ModelExportable;

class EventExporter extends AbstractExporter
{
    use ModelExportable;

    protected string $fileName    = 'events.json';
    protected string $model       = Event::class;
    protected string $whereColumn = 'approved_by';
}
