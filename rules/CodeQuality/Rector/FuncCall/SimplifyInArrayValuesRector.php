<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\CodeQuality\Rector\FuncCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Arg;
use RectorPrefix20220606\PhpParser\Node\Expr\FuncCall;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\CodeQuality\Rector\FuncCall\SimplifyInArrayValuesRector\SimplifyInArrayValuesRectorTest
 */
final class SimplifyInArrayValuesRector extends AbstractRector
{
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Removes unneeded array_values() in in_array() call', [new CodeSample('in_array("key", array_values($array), true);', 'in_array("key", $array, true);')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (!$this->isName($node, 'in_array')) {
            return null;
        }
        if (!isset($node->args[1])) {
            return null;
        }
        if (!$node->args[1] instanceof Arg) {
            return null;
        }
        if (!$node->args[1]->value instanceof FuncCall) {
            return null;
        }
        /** @var FuncCall $innerFunCall */
        $innerFunCall = $node->args[1]->value;
        if (!$this->isName($innerFunCall, 'array_values')) {
            return null;
        }
        if (!isset($node->args[0])) {
            return null;
        }
        if (!$node->args[0] instanceof Arg) {
            return null;
        }
        $node->args[1] = $innerFunCall->args[0];
        return $node;
    }
}
