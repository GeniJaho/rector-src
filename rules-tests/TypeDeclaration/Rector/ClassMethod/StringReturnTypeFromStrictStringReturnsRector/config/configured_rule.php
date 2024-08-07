<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictStringReturnsRector;

return RectorConfig::configure()
    ->withRules([StringReturnTypeFromStrictStringReturnsRector::class]);
