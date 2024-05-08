<?php

namespace Maximaster\Tools\Twig\Extensions;

use Twig\Extension\AbstractExtension as TwigAbstractExtension;
use Twig\Extension\GlobalsInterface as TwigGlobalsInterface;

/**
 * Class BitrixExtension. Расширение, которое добавляет глобалки php в шаблоны
 *
 * @package Maximaster\Twig
 */
class PhpGlobalsExtension extends TwigAbstractExtension implements TwigGlobalsInterface
{
    public function getName(): string
    {
        return 'php_globals_extension';
    }

    public function getGlobals(): array
    {
        return [
            '_COOKIE' => $_COOKIE,
            '_ENV' => $_ENV,
            '_FILES' => $_FILES,
            '_GET' => $_GET,
            '_GLOBALS' => $GLOBALS,
            '_POST' => $_POST,
            '_REQUEST' => $_REQUEST,
            '_SERVER' => $_SERVER,
            '_SESSION' => $_SESSION,
        ];
    }
}
