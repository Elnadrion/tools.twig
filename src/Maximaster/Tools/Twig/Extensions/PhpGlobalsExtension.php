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
            '_SERVER' => $_SERVER,
            '_REQUEST' => $_REQUEST,
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_FILES' => $_FILES,
            '_SESSION' => $_SESSION,
            '_COOKIE' => $_COOKIE,
            '_GLOBALS' => $GLOBALS,
        ];
    }
}
