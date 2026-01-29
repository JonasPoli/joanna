<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApprovalExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('approval_color_class', [$this, 'getApprovalColorClass']),
        ];
    }

    /**
     * Retorna a classe CSS de cor baseada no número de aprovações
     */
    public function getApprovalColorClass(int $count): string
    {
        return match ($count) {
            0 => '',
            1 => 'bg-green-50',
            2 => 'bg-green-100',
            3 => 'bg-green-200',
            4 => 'bg-green-300',
            5 => 'bg-green-400',
            6 => 'bg-green-500 text-white',
            7 => 'bg-green-600 text-white',
            8 => 'bg-green-700 text-white',
            9 => 'bg-green-800 text-white',
            10 => 'bg-green-900 text-white',
            default => 'bg-green-950 text-white',
        };
    }
}
