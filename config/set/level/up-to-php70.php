<?php

declare (strict_types=1);
namespace RectorPrefix20220606;

use RectorPrefix20220606\Rector\Config\RectorConfig;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersion;
use RectorPrefix20220606\Rector\Set\ValueObject\LevelSetList;
use RectorPrefix20220606\Rector\Set\ValueObject\SetList;
return static function (RectorConfig $rectorConfig) : void {
    $rectorConfig->sets([SetList::PHP_70, LevelSetList::UP_TO_PHP_56]);
    // parameter must be defined after import, to override imported param version
    $rectorConfig->phpVersion(PhpVersion::PHP_70);
};
