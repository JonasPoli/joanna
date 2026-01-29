<?php

namespace App\Enum;

enum ReferenceType: string
{
    case DIRETA = 'Direta';
    case INDIRETA = 'Indireta';
    case EPIGRAFE = 'Epígrafe';
    case OUTRO = 'Outro';

    public static function fromString(string $type): self
    {
        $normalized = mb_strtolower(trim($type));

        if (str_contains($normalized, 'indireta') || str_contains($normalized, 'indiireta')) {
            return self::INDIRETA;
        }

        if (str_contains($normalized, 'epígrafe') || str_contains($normalized, 'epigrafe')) {
            return self::EPIGRAFE;
        }

        if (str_contains($normalized, 'direta')) {
            return self::DIRETA;
        }

        return self::OUTRO;
    }
}
