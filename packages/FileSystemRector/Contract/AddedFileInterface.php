<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\FileSystemRector\Contract;

interface AddedFileInterface
{
    public function getFilePath() : string;
}
