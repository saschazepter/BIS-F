<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters\Base;

use App\Models\User;
use InvalidArgumentException;
use Spatie\PersonalDataExport\PersonalDataSelection;

class Exporter
{
    private PersonalDataSelection $pds;
    private AbstractExporter      $exporter;

    public function __construct(
        PersonalDataSelection $pds,
        string                $exporter,
        User                  $user
    ) {
        $this->checkClass($exporter);
        $this->pds      = $pds;
        $this->exporter = new $exporter($user);
    }

    public function export(): void {
        $this->pds->add($this->exporter->getFileName(), $this->exporter->exportData());
    }

    private function checkClass(string $exporter): void {
        if (!class_exists($exporter) || !is_subclass_of($exporter, AbstractExporter::class)) {
            throw new InvalidArgumentException(sprintf('%s is not of type %s', $exporter, AbstractExporter::class));
        }
    }
}
