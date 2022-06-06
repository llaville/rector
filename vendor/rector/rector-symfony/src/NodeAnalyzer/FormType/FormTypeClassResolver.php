<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Symfony\NodeAnalyzer\FormType;

use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\New_;
use RectorPrefix20220606\PHPStan\Type\TypeWithClassName;
use RectorPrefix20220606\Rector\NodeNameResolver\NodeNameResolver;
use RectorPrefix20220606\Rector\NodeTypeResolver\NodeTypeResolver;
final class FormTypeClassResolver
{
    /**
     * @readonly
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @readonly
     * @var \Rector\NodeTypeResolver\NodeTypeResolver
     */
    private $nodeTypeResolver;
    public function __construct(NodeNameResolver $nodeNameResolver, NodeTypeResolver $nodeTypeResolver)
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->nodeTypeResolver = $nodeTypeResolver;
    }
    public function resolveFromExpr(Expr $expr) : ?string
    {
        if ($expr instanceof New_) {
            // we can only process direct name
            return $this->nodeNameResolver->getName($expr->class);
        }
        $exprType = $this->nodeTypeResolver->getType($expr);
        if ($exprType instanceof TypeWithClassName) {
            return $exprType->getClassName();
        }
        return null;
    }
}
