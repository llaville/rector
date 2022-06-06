<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Ssch\TYPO3Rector\FileProcessor\TypoScript\Rector;

use Helmich\TypoScriptParser\Parser\AST\Statement;
use RectorPrefix20220606\Helmich\TypoScriptParser\Parser\Traverser\Visitor;
use RectorPrefix20220606\Ssch\TYPO3Rector\Contract\FileProcessor\TypoScript\TypoScriptRectorInterface;
abstract class AbstractTypoScriptRector implements Visitor, TypoScriptRectorInterface
{
    /**
     * @var bool
     */
    protected $hasChanged = \false;
    public function enterTree(array $statements) : void
    {
    }
    public function enterNode(Statement $statement) : void
    {
    }
    public function exitNode(Statement $statement) : void
    {
    }
    public function exitTree(array $statements) : void
    {
    }
    public function hasChanged() : bool
    {
        return $this->hasChanged;
    }
}
