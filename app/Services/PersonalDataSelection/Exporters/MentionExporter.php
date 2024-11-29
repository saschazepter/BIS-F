<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters;

use App\Models\Mention;
use App\Services\PersonalDataSelection\Exporters\Base\AbstractExporter;
use App\Services\PersonalDataSelection\Exporters\Base\ModelExportable;

class MentionExporter extends AbstractExporter
{
    use ModelExportable;

    protected string $fileName    = 'mentions.json';
    protected string $model       = Mention::class;
    protected string $whereColumn = 'mentioned_id';
}
