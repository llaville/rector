<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php72\Rector\While_;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Arg;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayItem;
use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\Expr\FuncCall;
use RectorPrefix20220606\PhpParser\Node\Expr\List_;
use RectorPrefix20220606\PhpParser\Node\Stmt\Foreach_;
use RectorPrefix20220606\PhpParser\Node\Stmt\While_;
use RectorPrefix20220606\Rector\Core\NodeManipulator\AssignManipulator;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/deprecations_php_7_2#each
 *
 * @see \Rector\Tests\Php72\Rector\While_\WhileEachToForeachRector\WhileEachToForeachRectorTest
 */
final class WhileEachToForeachRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @readonly
     * @var \Rector\Core\NodeManipulator\AssignManipulator
     */
    private $assignManipulator;
    public function __construct(AssignManipulator $assignManipulator)
    {
        $this->assignManipulator = $assignManipulator;
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::DEPRECATE_EACH;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('each() function is deprecated, use foreach() instead.', [new CodeSample(<<<'CODE_SAMPLE'
while (list($key, $callback) = each($callbacks)) {
    // ...
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
foreach ($callbacks as $key => $callback) {
    // ...
}
CODE_SAMPLE
), new CodeSample(<<<'CODE_SAMPLE'
while (list($key) = each($callbacks)) {
    // ...
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
foreach (array_keys($callbacks) as $key) {
    // ...
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [While_::class];
    }
    /**
     * @param While_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (!$node->cond instanceof Assign) {
            return null;
        }
        /** @var Assign $assignNode */
        $assignNode = $node->cond;
        if (!$this->assignManipulator->isListToEachAssign($assignNode)) {
            return null;
        }
        /** @var FuncCall $eachFuncCall */
        $eachFuncCall = $assignNode->expr;
        /** @var List_ $listNode */
        $listNode = $assignNode->var;
        if (!isset($eachFuncCall->args[0])) {
            return null;
        }
        if (!$eachFuncCall->args[0] instanceof Arg) {
            return null;
        }
        $foreachedExpr = \count($listNode->items) === 1 ? $this->nodeFactory->createFuncCall('array_keys', [$eachFuncCall->args[0]]) : $eachFuncCall->args[0]->value;
        /** @var ArrayItem $arrayItem */
        $arrayItem = \array_pop($listNode->items);
        $foreach = new Foreach_($foreachedExpr, $arrayItem, ['stmts' => $node->stmts]);
        $this->mirrorComments($foreach, $node);
        // is key included? add it to foreach
        if ($listNode->items !== []) {
            /** @var ArrayItem|null $keyItem */
            $keyItem = \array_pop($listNode->items);
            if ($keyItem !== null) {
                $foreach->keyVar = $keyItem->value;
            }
        }
        return $foreach;
    }
}
