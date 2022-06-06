<?php

declare (strict_types=1);
namespace RectorPrefix20220606;

use RectorPrefix20220606\Rector\Config\RectorConfig;
use RectorPrefix20220606\Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use RectorPrefix20220606\Rector\Renaming\ValueObject\MethodCallRename;
return static function (RectorConfig $rectorConfig) : void {
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        // Rename is now move, specific for files.
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'rename', 'move'),
        // No arbitrary abbreviations
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'createDir', 'createDirectory'),
        // Writes are now deterministic
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'update', 'write'),
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'updateStream', 'writeStream'),
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'put', 'write'),
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'putStream', 'writeStream'),
        // Metadata getters are renamed
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'getTimestamp', 'lastModified'),
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'has', 'fileExists'),
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'getMimetype', 'mimeType'),
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'getSize', 'fileSize'),
        new MethodCallRename('League\\Flysystem\\FilesystemInterface', 'getVisibility', 'visibility'),
    ]);
};
