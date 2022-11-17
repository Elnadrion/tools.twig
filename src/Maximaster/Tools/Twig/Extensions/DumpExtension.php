<?php

namespace Maximaster\Tools\Twig\Extensions;

use Twig\Extension\AbstractExtension as TwigAbstractExtension;
use Twig\Extension\GlobalsInterface as TwigGlobalsInterface;
use Twig\TwigFunction;

class DumpExtension extends TwigAbstractExtension implements TwigGlobalsInterface
{
    public function getName(): string
    {
        return 'elnadrion_twig_dump_extension';
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dump', [$this, 'dump']),
        ];
    }

    public static function dump($data): void
    {
        dump($data);
    }
}
