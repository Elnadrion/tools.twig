<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\OrderedTraitsFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleTraitInsertPerStatementFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $services = $ecsConfig->services();

    $services->set(OrderedTraitsFixer::class);
    $services->set(SingleQuoteFixer::class);
    $services->set(NoExtraBlankLinesFixer::class);
    $services->set(SingleTraitInsertPerStatementFixer::class);

    $services->set(YodaStyleFixer::class)
        ->call('configure', [
            [
                'equal' => false,
                'identical' => false,
                'less_and_greater' => false,
            ],
        ]);
    $services->set(TrailingCommaInMultilineFixer::class)
        ->call('configure', [
            [
                'elements' => ['arrays'],
            ],
        ]);

    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $ecsConfig->import(SetList::CLEAN_CODE);
    $ecsConfig->import(SetList::PSR_12);

    $ecsConfig->parallel();
    $ecsConfig->cacheDirectory('.ecs_cache');
};
