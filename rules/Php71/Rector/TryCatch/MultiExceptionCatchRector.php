<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php71\Rector\TryCatch;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Name;
use RectorPrefix20220606\PhpParser\Node\Stmt\Catch_;
use RectorPrefix20220606\PhpParser\Node\Stmt\TryCatch;
use RectorPrefix20220606\Rector\Core\Contract\PhpParser\NodePrinterInterface;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/multiple-catch
 *
 * @see \Rector\Tests\Php71\Rector\TryCatch\MultiExceptionCatchRector\MultiExceptionCatchRectorTest
 */
final class MultiExceptionCatchRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @readonly
     * @var \Rector\Core\Contract\PhpParser\NodePrinterInterface
     */
    private $nodePrinter;
    public function __construct(NodePrinterInterface $nodePrinter)
    {
        $this->nodePrinter = $nodePrinter;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Changes multi catch of same exception to single one | separated.', [new CodeSample(<<<'CODE_SAMPLE'
try {
    // Some code...
} catch (ExceptionType1 $exception) {
    $sameCode;
} catch (ExceptionType2 $exception) {
    $sameCode;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
try {
    // Some code...
} catch (ExceptionType1 | ExceptionType2 $exception) {
    $sameCode;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [TryCatch::class];
    }
    /**
     * @param TryCatch $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (\count($node->catches) < 2) {
            return null;
        }
        $catchKeysByContent = $this->collectCatchKeysByContent($node);
        $hasRemovedCatch = \false;
        /** @var Catch_[] $catchKeys */
        foreach ($catchKeysByContent as $catchKeys) {
            // no duplicates
            $count = \count($catchKeys);
            if ($count < 2) {
                continue;
            }
            $collectedTypes = $this->collectTypesFromCatchedByIds($catchKeys);
            /** @var Catch_ $firstCatch */
            $firstCatch = \array_shift($catchKeys);
            $firstCatch->types = $collectedTypes;
            foreach ($catchKeys as $catchKey) {
                $this->removeNode($catchKey);
                $hasRemovedCatch = \true;
            }
        }
        if (!$hasRemovedCatch) {
            return null;
        }
        return $node;
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::MULTI_EXCEPTION_CATCH;
    }
    /**
     * @return array<string, Catch_[]>
     */
    private function collectCatchKeysByContent(TryCatch $tryCatch) : array
    {
        $catchKeysByContent = [];
        foreach ($tryCatch->catches as $catch) {
            $catchContent = $this->nodePrinter->print($catch->stmts);
            $catchKeysByContent[$catchContent][] = $catch;
        }
        return $catchKeysByContent;
    }
    /**
     * @param Catch_[] $catches
     * @return Name[]
     */
    private function collectTypesFromCatchedByIds(array $catches) : array
    {
        $collectedTypes = [];
        foreach ($catches as $catch) {
            $collectedTypes = \array_merge($collectedTypes, $catch->types);
        }
        return $collectedTypes;
    }
}
