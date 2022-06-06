<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DeadCode;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\Array_;
use RectorPrefix20220606\PhpParser\Node\Expr\BinaryOp\NotEqual;
use RectorPrefix20220606\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use RectorPrefix20220606\PhpParser\Node\Expr\BooleanNot;
use RectorPrefix20220606\PhpParser\Node\Expr\Empty_;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\PhpParser\Node\Param;
use RectorPrefix20220606\PhpParser\Node\Stmt\If_;
use RectorPrefix20220606\PHPStan\Type\ArrayType;
use RectorPrefix20220606\Rector\Core\NodeAnalyzer\ParamAnalyzer;
use RectorPrefix20220606\Rector\Core\PhpParser\Comparing\NodeComparator;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\BetterNodeFinder;
use RectorPrefix20220606\Rector\NodeTypeResolver\NodeTypeResolver;
final class UselessIfCondBeforeForeachDetector
{
    /**
     * @readonly
     * @var \Rector\NodeTypeResolver\NodeTypeResolver
     */
    private $nodeTypeResolver;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Comparing\NodeComparator
     */
    private $nodeComparator;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\BetterNodeFinder
     */
    private $betterNodeFinder;
    /**
     * @readonly
     * @var \Rector\Core\NodeAnalyzer\ParamAnalyzer
     */
    private $paramAnalyzer;
    public function __construct(NodeTypeResolver $nodeTypeResolver, NodeComparator $nodeComparator, BetterNodeFinder $betterNodeFinder, ParamAnalyzer $paramAnalyzer)
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->nodeComparator = $nodeComparator;
        $this->betterNodeFinder = $betterNodeFinder;
        $this->paramAnalyzer = $paramAnalyzer;
    }
    /**
     * Matches:
     * !empty($values)
     */
    public function isMatchingNotEmpty(If_ $if, Expr $foreachExpr) : bool
    {
        $cond = $if->cond;
        if (!$cond instanceof BooleanNot) {
            return \false;
        }
        if (!$cond->expr instanceof Empty_) {
            return \false;
        }
        /** @var Empty_ $empty */
        $empty = $cond->expr;
        if (!$this->nodeComparator->areNodesEqual($empty->expr, $foreachExpr)) {
            return \false;
        }
        // is array though?
        $arrayType = $this->nodeTypeResolver->getType($empty->expr);
        if (!$arrayType instanceof ArrayType) {
            return \false;
        }
        $previousParam = $this->fromPreviousParam($foreachExpr);
        if (!$previousParam instanceof Param) {
            return \true;
        }
        if ($this->paramAnalyzer->isNullable($previousParam)) {
            return \false;
        }
        return !$this->paramAnalyzer->hasDefaultNull($previousParam);
    }
    /**
     * Matches:
     * $values !== []
     * $values != []
     * [] !== $values
     * [] != $values
     */
    public function isMatchingNotIdenticalEmptyArray(If_ $if, Expr $foreachExpr) : bool
    {
        if (!$if->cond instanceof NotIdentical && !$if->cond instanceof NotEqual) {
            return \false;
        }
        /** @var NotIdentical|NotEqual $notIdentical */
        $notIdentical = $if->cond;
        return $this->isMatchingNotBinaryOp($notIdentical, $foreachExpr);
    }
    private function fromPreviousParam(Expr $expr) : ?Node
    {
        return $this->betterNodeFinder->findFirstPrevious($expr, function (Node $node) use($expr) : bool {
            if (!$node instanceof Param) {
                return \false;
            }
            if (!$node->var instanceof Variable) {
                return \false;
            }
            return $this->nodeComparator->areNodesEqual($node->var, $expr);
        });
    }
    /**
     * @param \PhpParser\Node\Expr\BinaryOp\NotIdentical|\PhpParser\Node\Expr\BinaryOp\NotEqual $binaryOp
     */
    private function isMatchingNotBinaryOp($binaryOp, Expr $foreachExpr) : bool
    {
        if ($this->isEmptyArrayAndForeachedVariable($binaryOp->left, $binaryOp->right, $foreachExpr)) {
            return \true;
        }
        return $this->isEmptyArrayAndForeachedVariable($binaryOp->right, $binaryOp->left, $foreachExpr);
    }
    private function isEmptyArrayAndForeachedVariable(Expr $leftExpr, Expr $rightExpr, Expr $foreachExpr) : bool
    {
        if (!$this->isEmptyArray($leftExpr)) {
            return \false;
        }
        return $this->nodeComparator->areNodesEqual($foreachExpr, $rightExpr);
    }
    private function isEmptyArray(Expr $expr) : bool
    {
        if (!$expr instanceof Array_) {
            return \false;
        }
        return $expr->items === [];
    }
}
