<?php

declare (strict_types=1);
namespace RectorPrefix20220606;

use RectorPrefix20220606\Rector\CakePHP\Set\CakePHPLevelSetList;
use RectorPrefix20220606\Rector\CakePHP\Set\CakePHPSetList;
use RectorPrefix20220606\Rector\Config\RectorConfig;
return static function (RectorConfig $rectorConfig) : void {
    $rectorConfig->sets([CakePHPSetList::CAKEPHP_38, CakePHPLevelSetList::UP_TO_CAKEPHP_37]);
};
