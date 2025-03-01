<?php

namespace App\Dto\GeoJson;

use App\Dto\Coordinate;
use stdClass;

class Feature implements \JsonSerializable
{
    /**
     * @var Coordinate[] $coordinates
     */
    private array  $coordinates;
    private string $type;
    private ?int   $statusId;

    public function __construct(array $coordinates, string $type = 'LineString', ?int $statusId = null) {
        $this->coordinates = $coordinates;
        $this->type        = $type;
        $this->statusId    = $statusId;
    }

    public static function fromCoordinate(Coordinate $coordinate): self {
        return new self([$coordinate->longitude, $coordinate->latitude], 'Point');
    }

    public function getCoordinates(bool $invert = false): array {
        if (!$invert) {
            return $this->coordinates;
        }
        return array_map(static function($coordinate) {
            return [$coordinate->latitude, $coordinate->longitude];
        }, $this->coordinates);
    }

    public function toArray(): array {
        $response = [
            'type'       => 'Feature',
            'geometry'   => [
                'type'        => $this->type,
                'coordinates' => $this->coordinates
            ],
            'properties' => new stdClass(),
        ];
        if ($this->statusId) {
            $response['properties'] = ['statusId' => $this->statusId];
        }
        return $response;
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}
