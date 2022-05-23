<?php

use Maximaster\Tools\Twig\Aop\AspectKernel;
use Maximaster\Tools\Twig\TemplateEngine;
use Maximaster\Tools\Twig\TwigOptionsStorage;
use Twig\Error\LoaderError as TwigLoaderError;

if (!function_exists('maximasterRenderTwigTemplate')) {
    function maximasterRenderTwigTemplate(
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
            $arLangMessages,
            $templateFolder,
            $parentTemplateFolder,
            $template
        );
    }

    function maximasterRegisterTwigTemplateEngine(): void
    {
        if (!class_exists('CMain')) {
            return;
        }

        $options = new TwigOptionsStorage();

        global $arCustomTemplateEngines;
        $arCustomTemplateEngines['twig'] = [
            'templateExt' => ['twig'],
            'function' => 'maximasterRenderTwigTemplate',
            'sort' => $options->getUsedByDefault() ? 1 : 500,
        ];
    }

    maximasterRegisterTwigTemplateEngine();

    if (class_exists('\Go\Core\AspectKernel') && class_exists('CMain')) {
        $aspectKernel = AspectKernel::getInstance();
        $aspectKernel->init([
            'appDir' => $_SERVER['DOCUMENT_ROOT'],
            'cacheDir' => TemplateEngine::getInstance()->getOptions()->getCache(),
        ]);
    }
} else {
    throw new TwigLoaderError('Необходимо, чтобы функция с именем maximasterRenderTwigTemplate не была определена');
}
