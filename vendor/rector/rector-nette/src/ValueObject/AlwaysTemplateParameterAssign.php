<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Nette\ValueObject;

use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
final class AlwaysTemplateParameterAssign
{
    /**
     * @readonly
     * @var \PhpParser\Node\Expr\Assign
     */
    private $assign;
    /**
     * @readonly
     * @var string
     */
    private $parameterName;
    /**
     * @readonly
     * @var \PhpParser\Node\Expr
     */
    private $assignedExpr;
    public function __construct(Assign $assign, string $parameterName, Expr $assignedExpr)
    {
        $this->assign = $assign;
        $this->parameterName = $parameterName;
        $this->assignedExpr = $assignedExpr;
    }
    public function getAssign() : Assign
    {
        return $this->assign;
    }
    public function getAssignVar() : Expr
    {
        return $this->assign->var;
    }
    public function getParameterName() : string
    {
        return $this->parameterName;
    }
    public function getAssignedExpr() : Expr
    {
        return $this->assignedExpr;
    }
}
