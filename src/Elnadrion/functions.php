<?php

use Elnadrion\Tools\Twig\TemplateEngine;
use Elnadrion\Tools\Twig\TwigOptionsStorage;
use Twig\Error\LoaderError as TwigLoaderError;

if (!function_exists('elnadrionRenderTwigTemplate')) {
    function elnadrionRenderTwigTemplate(
        $templateFile,
        $arResult,
        $arParams,
        $arLangMessages,
        $templateFolder,
        $parentTemplateFolder,
        \CBitrixComponentTemplate $template
    ): void {
        TemplateEngine::render(
            $templateFile,
            $arResult,
            $arParams,
            $arLangMessages ?? [],
            $templateFolder,
            $parentTemplateFolder,
            $template
        );
    }

    function registerTwigTemplateEngine(): void
    {
        if (!class_exists('CMain')) {
            return;
        }

        $options = new TwigOptionsStorage();

        global $arCustomTemplateEngines;
        $arCustomTemplateEngines['twig'] = [
            'templateExt' => ['twig'],
            'function' => 'elnadrionRenderTwigTemplate',
            'sort' => $options->getUsedByDefault() ? 1 : 500,
        ];
    }

    registerTwigTemplateEngine();
} else {
    throw new TwigLoaderError('Необходимо, чтобы функция с именем registerTwigTemplateEngine не была определена');
}
