<?php

namespace Elnadrion\Tools\Twig\Extensions;

use Twig\Extension\AbstractExtension as TwigAbstractExtension;
use Twig\Extension\GlobalsInterface as TwigGlobalsInterface;
use Twig\TwigFunction;

class PluralFormExtension extends TwigAbstractExtension implements TwigGlobalsInterface
{
    public function getName(): string
    {
        return 'elnadrion_functions_extension';
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('russianPluralForm', $this->russianPluralForm(...)),
        ];
    }

    /**
     * Выводит правильный вариант множественной формы числа
     *
     * @param int|float $count Число, для которого нужно сформировать множественную форму (число будет приведено к целому)
     * @param string[] $input Массив, содержащий 3 слова ['билетов', 'билет', 'билета'] (Ноль билетов, Один билет, Два билета)
     */
    public static function russianPluralForm(int|float $count, array $input): string
    {
        $count = (int)$count;
        $l2 = substr($count, -2);
        $l1 = substr($count, -1);
        if ($l2 > 10 && $l2 < 20) {
            return $input[0];
        } else {
            return match ($l1) {
                1 => $input[1],
                2, 3, 4 => $input[2],
                default => $input[0],
            };
        }
    }
}
