<?php

namespace Elnadrion\Tools\Twig;

use Bitrix\Main\Config\Configuration;

/**
 * Класс для более удобного способа доступа к настрокам twig
 * @package Elnadrion\Tools\Twig
 */
class TwigOptionsStorage implements \ArrayAccess
{
    private array $options = [];

    private array $configurableKeys = [
        'debug',
        'charset',
        'cache',
        'use_by_default',
    ];

    public function __construct()
    {
        $this->getOptions();
    }

    public function getDefaultOptions(): array
    {
        return [
            'debug' => false,
            'charset' => 'UTF-8',
            'cache' => $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/elnadrion/tools.twig',
            'auto_reload' => isset($_GET['clear_cache']) && strtoupper((string)$_GET['clear_cache']) === 'Y',
            'autoescape' => false,
            'extract_result' => false,
            'use_by_default' => false,
        ];
    }

    public function getOptions(): array
    {
        $c = Configuration::getInstance();
        $twigConfig = $c->get('tools.twig');

        if (empty($twigConfig) || !is_array($twigConfig)) {
            $twigConfig = [];
        } else {
            $twigConfig = array_intersect_key($twigConfig, array_flip($this->configurableKeys));
        }

        $this->options = array_merge($this->getDefaultOptions(), $twigConfig);
        return $this->options;
    }

    public function asArray(): array
    {
        return $this->options;
    }

    public function getCache(): string
    {
        return (string)$this->options['cache'];
    }

    public function getDebug(): bool
    {
        return (bool)$this->options['debug'];
    }

    public function getCharset(): string
    {
        return (string)$this->options['charset'];
    }

    public function getAutoReload(): bool
    {
        return (bool)$this->options['auto_reload'];
    }

    public function getAutoescape(): bool
    {
        return (bool)$this->options['autoescape'];
    }

    public function getExtractResult(): bool
    {
        return (bool)$this->options['extract_result'];
    }

    public function getUsedByDefault(): bool
    {
        return (bool)$this->options['use_by_default'];
    }

    public function offsetExists($offset): bool
    {
        return isset($this->options[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->options[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->options[ $offset ] = $value;
    }

    public function offsetUnset($offset): void
    {
    }
}
