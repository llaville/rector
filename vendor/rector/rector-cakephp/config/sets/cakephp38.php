<?php

declare (strict_types=1);
namespace RectorPrefix20220419;

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;
# source: https://book.cakephp.org/3.0/en/appendices/3-8-migration-guide.html
return static function (\Rector\Config\RectorConfig $rectorConfig) : void {
    $services = $rectorConfig->services();
    $services->set(\Rector\Renaming\Rector\MethodCall\RenameMethodRector::class)->configure([new \Rector\Renaming\ValueObject\MethodCallRename('Cake\\ORM\\Entity', 'visibleProperties', 'getVisible')]);
};
