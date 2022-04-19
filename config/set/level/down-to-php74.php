<?php

declare (strict_types=1);
namespace RectorPrefix20220419;

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\DowngradeLevelSetList;
use Rector\Set\ValueObject\DowngradeSetList;
return static function (\Rector\Config\RectorConfig $rectorConfig) : void {
    $rectorConfig->import(\Rector\Set\ValueObject\DowngradeLevelSetList::DOWN_TO_PHP_80);
    $rectorConfig->import(\Rector\Set\ValueObject\DowngradeSetList::PHP_80);
};
