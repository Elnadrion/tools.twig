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
     * @param int $n Число, для которого нужно сформировать множественную форму
     * @param string[] $forms Массив, содержащий 3 слова ['билетов', 'билет', 'билета'] (Ноль билетов, Один билет, Два билета)
     */
    public static function russianPluralForm(int $n, array $forms): string
    {
        return $n % 10 == 1 && $n % 100 != 11 ? $forms[0] : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $forms[1] : $forms[2]);
    }
}
