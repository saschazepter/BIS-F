<?php
declare(strict_types=1);

namespace App\Enum;

enum ReiseloesungCategory: string
{
    case ICE = 'ICE';
    case EC_IC = 'EC_IC';
    case IR = 'IR';
    case REGIONAL = 'REGIONAL';
    case SBAHN = 'SBAHN';
    case BUS = 'BUS';
    case SCHIFF = 'SCHIFF';
    case UBAHN = 'UBAHN';
    case TRAM = 'TRAM';
    case ANRUFPFLICHTIG = 'ANRUFPFLICHTIG';
    case UNKNOWN = 'UNKNOWN';


    public function getHTT(): HafasTravelType
    {
        return match ($this->name) {
            'ICE' => HafasTravelType::NATIONAL_EXPRESS,
            'EC_IC' => HafasTravelType::NATIONAL,
            'IR' => HafasTravelType::REGIONAL_EXP,
            'UNKNOWN', 'REGIONAL' => HafasTravelType::REGIONAL,
            'SBAHN' => HafasTravelType::SUBURBAN,
            'BUS' => HafasTravelType::BUS,
            'SCHIFF' => HafasTravelType::FERRY,
            'UBAHN' => HafasTravelType::SUBWAY,
            'TRAM' => HafasTravelType::TRAM,
            'ANRUFPFLICHTIG' => HafasTravelType::TAXI,
            default => HafasTravelType::REGIONAL,
        };
    }
}
