<?php

namespace Elnadrion\Tools\Twig;

use Bitrix\Main\ArgumentException;
use Twig\Environment as TwigEnvironment;

/**
 * Класс, который берет на себя очистку кеша твига
 * @package Elnadrion\Tools\Twig
 */
class TwigCacheCleaner
{
    public function __construct(protected TwigEnvironment $engine)
    {
        $this->checkCacheEngine();
    }

    /**
     * Проверяет, является ли кеш файловым, просто на основании существования директории с кешем
     */
    private function isFileCache(): bool
    {
        return is_dir($this->engine->getCache(true));
    }

    private function checkCacheEngine(): void
    {
        if (!$this->isFileCache()) {
            throw new \LogicException('Невозможно очистить кеш. Он либо хранится не в файлах, либо кеш отсутствует полностью');
        }
    }

    /**
     * Очищает кеш по его строковому имени
     *
     * @param string $name Имя шаблона для удаления
     * @return int Количество удаленных файлов кеша
     * @throws ArgumentException
     */
    public function clearByName(string $name): int
    {
        if (strlen($name) === 0) {
            throw new ArgumentException('Имя шаблона не задано');
        }

        $counter = 0;

        $templateClass = $this->engine->getTemplateClass($name);
        if (strlen($name) === 0) {
            throw new ArgumentException("Шаблон с именем '{$name}' не найден");
        }

        $fileName = $this->engine->getCache(false)->generateKey($name, $templateClass);

        if (is_file($fileName)) {
            @unlink($fileName);

            if (is_file($fileName)) {
                throw new \LogicException("Шаблон '{$name}'.\nПроизошла ошибка в процессе удаления файла:\n$fileName");
            }

            $counter++;
        }

        return $counter;
    }

    /**
     * Удаляет весь кеш твига
     *
     * @return int Количество удаленных файлов кеша
     */
    public function clearAll(): int
    {
        $counter = 0;

        $cachePath = $this->engine->getCache(true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                @unlink($file->getPathname());
                if (!is_file($file->getPathname())) {
                    $counter++;
                }
            }
        }

        return $counter;
    }
}
