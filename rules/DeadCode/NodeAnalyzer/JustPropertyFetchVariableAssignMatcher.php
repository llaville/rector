<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DeadCode\NodeAnalyzer;

use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\Expr\PropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\PhpParser\Node\Stmt;
use RectorPrefix20220606\PhpParser\Node\Stmt\Expression;
use RectorPrefix20220606\Rector\Core\Contract\PhpParser\Node\StmtsAwareInterface;
use RectorPrefix20220606\Rector\Core\PhpParser\Comparing\NodeComparator;
use RectorPrefix20220606\Rector\DeadCode\ValueObject\VariableAndPropertyFetchAssign;
final class JustPropertyFetchVariableAssignMatcher
{
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Comparing\NodeComparator
     */
    private $nodeComparator;
    public function __construct(NodeComparator $nodeComparator)
    {
        $this->nodeComparator = $nodeComparator;
    }
    public function match(StmtsAwareInterface $stmtsAware) : ?VariableAndPropertyFetchAssign
    {
        $stmts = (array) $stmtsAware->stmts;
        $stmtCount = \count($stmts);
        // must be exactly 3 stmts
        if ($stmtCount !== 3) {
            return null;
        }
        $firstVariableAndPropertyFetchAssign = $this->matchVariableAndPropertyFetchAssign($stmts[0]);
        if (!$firstVariableAndPropertyFetchAssign instanceof VariableAndPropertyFetchAssign) {
            return null;
        }
        $thirdVariableAndPropertyFetchAssign = $this->matchRevertedVariableAndPropertyFetchAssign($stmts[2]);
        if (!$thirdVariableAndPropertyFetchAssign instanceof VariableAndPropertyFetchAssign) {
            return null;
        }
        // property fetch are the same
        if (!$this->nodeComparator->areNodesEqual($firstVariableAndPropertyFetchAssign->getPropertyFetch(), $thirdVariableAndPropertyFetchAssign->getPropertyFetch())) {
            return null;
        }
        // variables are the same
        if (!$this->nodeComparator->areNodesEqual($firstVariableAndPropertyFetchAssign->getVariable(), $thirdVariableAndPropertyFetchAssign->getVariable())) {
            return null;
        }
        return $firstVariableAndPropertyFetchAssign;
    }
    private function matchVariableAndPropertyFetchAssign(Stmt $stmt) : ?VariableAndPropertyFetchAssign
    {
        if (!$stmt instanceof Expression) {
            return null;
        }
        if (!$stmt->expr instanceof Assign) {
            return null;
        }
        $assign = $stmt->expr;
        if (!$assign->expr instanceof PropertyFetch) {
            return null;
        }
        if (!$assign->var instanceof Variable) {
            return null;
        }
        return new VariableAndPropertyFetchAssign($assign->var, $assign->expr);
    }
    private function matchRevertedVariableAndPropertyFetchAssign(Stmt $stmt) : ?VariableAndPropertyFetchAssign
    {
        if (!$stmt instanceof Expression) {
            return null;
        }
        if (!$stmt->expr instanceof Assign) {
            return null;
        }
        $assign = $stmt->expr;
        if (!$assign->var instanceof PropertyFetch) {
            return null;
        }
        if (!$assign->expr instanceof Variable) {
            return null;
        }
        return new VariableAndPropertyFetchAssign($assign->expr, $assign->var);
    }
}
