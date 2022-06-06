<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\ReadWrite\NodeAnalyzer;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Arg;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayDimFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\AssignOp;
use RectorPrefix20220606\PhpParser\Node\Expr\FuncCall;
use RectorPrefix20220606\PhpParser\Node\Expr\Isset_;
use RectorPrefix20220606\PhpParser\Node\Expr\PropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\StaticPropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Stmt\Unset_;
use RectorPrefix20220606\Rector\Core\Exception\ShouldNotHappenException;
use RectorPrefix20220606\Rector\Core\NodeManipulator\AssignManipulator;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\BetterNodeFinder;
use RectorPrefix20220606\Rector\DeadCode\SideEffect\PureFunctionDetector;
use RectorPrefix20220606\Rector\NodeTypeResolver\Node\AttributeKey;
use RectorPrefix20220606\Rector\ReadWrite\Contract\ParentNodeReadAnalyzerInterface;
/**
 * Possibly re-use the same logic from PHPStan rule:
 * https://github.com/phpstan/phpstan-src/blob/8f16632f6ccb312159250bc06df5531fa4a1ff91/src/Rules/DeadCode/UnusedPrivatePropertyRule.php#L64-L116
 */
final class ReadWritePropertyAnalyzer
{
    /**
     * @readonly
     * @var \Rector\Core\NodeManipulator\AssignManipulator
     */
    private $assignManipulator;
    /**
     * @readonly
     * @var \Rector\ReadWrite\NodeAnalyzer\ReadExprAnalyzer
     */
    private $readExprAnalyzer;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\BetterNodeFinder
     */
    private $betterNodeFinder;
    /**
     * @readonly
     * @var \Rector\DeadCode\SideEffect\PureFunctionDetector
     */
    private $pureFunctionDetector;
    /**
     * @var ParentNodeReadAnalyzerInterface[]
     * @readonly
     */
    private $parentNodeReadAnalyzers;
    /**
     * @param ParentNodeReadAnalyzerInterface[] $parentNodeReadAnalyzers
     */
    public function __construct(AssignManipulator $assignManipulator, ReadExprAnalyzer $readExprAnalyzer, BetterNodeFinder $betterNodeFinder, PureFunctionDetector $pureFunctionDetector, array $parentNodeReadAnalyzers)
    {
        $this->assignManipulator = $assignManipulator;
        $this->readExprAnalyzer = $readExprAnalyzer;
        $this->betterNodeFinder = $betterNodeFinder;
        $this->pureFunctionDetector = $pureFunctionDetector;
        $this->parentNodeReadAnalyzers = $parentNodeReadAnalyzers;
    }
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $node
     */
    public function isRead($node) : bool
    {
        $parent = $node->getAttribute(AttributeKey::PARENT_NODE);
        if (!$parent instanceof Node) {
            throw new ShouldNotHappenException();
        }
        foreach ($this->parentNodeReadAnalyzers as $parentNodeReadAnalyzer) {
            if ($parentNodeReadAnalyzer->isRead($node, $parent)) {
                return \true;
            }
        }
        if ($parent instanceof AssignOp) {
            return \true;
        }
        if ($parent instanceof ArrayDimFetch && $parent->dim === $node && $this->isNotInsideIssetUnset($parent)) {
            return $this->isArrayDimFetchRead($parent);
        }
        if (!$parent instanceof ArrayDimFetch) {
            return !$this->assignManipulator->isLeftPartOfAssign($node);
        }
        if ($this->assignManipulator->isLeftPartOfAssign($parent)) {
            return \false;
        }
        return !$this->isArrayDimFetchInImpureFunction($parent, $node);
    }
    private function isArrayDimFetchInImpureFunction(ArrayDimFetch $arrayDimFetch, Node $node) : bool
    {
        if ($arrayDimFetch->var === $node) {
            $arg = $this->betterNodeFinder->findParentType($arrayDimFetch, Arg::class);
            if ($arg instanceof Arg) {
                $parentArg = $arg->getAttribute(AttributeKey::PARENT_NODE);
                if (!$parentArg instanceof FuncCall) {
                    return \false;
                }
                return !$this->pureFunctionDetector->detect($parentArg);
            }
        }
        return \false;
    }
    private function isNotInsideIssetUnset(ArrayDimFetch $arrayDimFetch) : bool
    {
        return !(bool) $this->betterNodeFinder->findParentByTypes($arrayDimFetch, [Isset_::class, Unset_::class]);
    }
    private function isArrayDimFetchRead(ArrayDimFetch $arrayDimFetch) : bool
    {
        $parentParent = $arrayDimFetch->getAttribute(AttributeKey::PARENT_NODE);
        if (!$parentParent instanceof Node) {
            throw new ShouldNotHappenException();
        }
        if (!$this->assignManipulator->isLeftPartOfAssign($arrayDimFetch)) {
            return \false;
        }
        if ($arrayDimFetch->var instanceof ArrayDimFetch) {
            return \true;
        }
        // the array dim fetch is assing here only; but the variable might be used later
        return $this->readExprAnalyzer->isExprRead($arrayDimFetch->var);
    }
}
