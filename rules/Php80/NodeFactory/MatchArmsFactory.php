<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php80\NodeFactory;

use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\MatchArm;
use RectorPrefix20220606\Rector\Php80\ValueObject\CondAndExpr;
final class MatchArmsFactory
{
    /**
     * @param CondAndExpr[] $condAndExprs
     * @return MatchArm[]
     */
    public function createFromCondAndExprs(array $condAndExprs) : array
    {
        $matchArms = [];
        foreach ($condAndExprs as $condAndExpr) {
            $expr = $condAndExpr->getExpr();
            if ($expr instanceof Assign) {
                // $this->assignExpr = $expr->var;
                $expr = $expr->expr;
            }
            $condExprs = $condAndExpr->getCondExprs();
            $matchArms[] = new MatchArm($condExprs, $expr);
        }
        return $matchArms;
    }
}
