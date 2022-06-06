<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php71;

use RectorPrefix20220606\PhpParser\Node\Arg;
use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use RectorPrefix20220606\PhpParser\Node\Expr\FuncCall;
use RectorPrefix20220606\PhpParser\Node\Expr\Instanceof_;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\PhpParser\Node\Name;
use RectorPrefix20220606\Rector\Core\NodeManipulator\BinaryOpManipulator;
use RectorPrefix20220606\Rector\NodeNameResolver\NodeNameResolver;
use RectorPrefix20220606\Rector\Php71\ValueObject\TwoNodeMatch;
final class IsArrayAndDualCheckToAble
{
    /**
     * @readonly
     * @var \Rector\Core\NodeManipulator\BinaryOpManipulator
     */
    private $binaryOpManipulator;
    /**
     * @readonly
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    public function __construct(BinaryOpManipulator $binaryOpManipulator, NodeNameResolver $nodeNameResolver)
    {
        $this->binaryOpManipulator = $binaryOpManipulator;
        $this->nodeNameResolver = $nodeNameResolver;
    }
    public function processBooleanOr(BooleanOr $booleanOr, string $type, string $newMethodName) : ?FuncCall
    {
        $twoNodeMatch = $this->binaryOpManipulator->matchFirstAndSecondConditionNode($booleanOr, Instanceof_::class, FuncCall::class);
        if (!$twoNodeMatch instanceof TwoNodeMatch) {
            return null;
        }
        /** @var Instanceof_ $instanceOf */
        $instanceOf = $twoNodeMatch->getFirstExpr();
        /** @var FuncCall $funcCall */
        $funcCall = $twoNodeMatch->getSecondExpr();
        $instanceOfClass = $instanceOf->class;
        if ($instanceOfClass instanceof Expr) {
            return null;
        }
        if ((string) $instanceOfClass !== $type) {
            return null;
        }
        if (!$this->nodeNameResolver->isName($funcCall, 'is_array')) {
            return null;
        }
        if (!isset($funcCall->args[0])) {
            return null;
        }
        if (!$funcCall->args[0] instanceof Arg) {
            return null;
        }
        // both use same var
        if (!$funcCall->args[0]->value instanceof Variable) {
            return null;
        }
        /** @var Variable $firstVarNode */
        $firstVarNode = $funcCall->args[0]->value;
        if (!$instanceOf->expr instanceof Variable) {
            return null;
        }
        /** @var Variable $secondVarNode */
        $secondVarNode = $instanceOf->expr;
        // are they same variables
        if ($firstVarNode->name !== $secondVarNode->name) {
            return null;
        }
        return new FuncCall(new Name($newMethodName), [new Arg($firstVarNode)]);
    }
}
