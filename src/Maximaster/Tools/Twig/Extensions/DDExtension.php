<?php

namespace Maximaster\Tools\Twig\Extensions;

use Twig\Extension\AbstractExtension as TwigAbstractExtension;
use Twig\Extension\GlobalsInterface as TwigGlobalsInterface;
use Twig\TwigFunction;

class DDExtension extends TwigAbstractExtension implements TwigGlobalsInterface
{
    public function getName()
    {
        return 'elnadrion_twig_dd_extension';
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dd', [$this, 'dd']),
        ];
    }

    public static function dd($data): string
    {
        return dd($data);
    }
}
