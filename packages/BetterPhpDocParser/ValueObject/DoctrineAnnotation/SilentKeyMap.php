<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\BetterPhpDocParser\ValueObject\DoctrineAnnotation;

final class SilentKeyMap
{
    /**
     * @var array<string, string>
     */
    public const CLASS_NAMES_TO_SILENT_KEYS = ['Symfony\\Component\\Routing\\Annotation\\Route' => 'path'];
}
