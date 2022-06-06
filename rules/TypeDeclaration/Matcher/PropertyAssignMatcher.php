<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\TypeDeclaration\Matcher;

use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayDimFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\Rector\Core\NodeAnalyzer\PropertyFetchAnalyzer;
use RectorPrefix20220606\Rector\NodeNameResolver\NodeNameResolver;
final class PropertyAssignMatcher
{
    /**
     * @readonly
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @readonly
     * @var \Rector\Core\NodeAnalyzer\PropertyFetchAnalyzer
     */
    private $propertyFetchAnalyzer;
    public function __construct(NodeNameResolver $nodeNameResolver, PropertyFetchAnalyzer $propertyFetchAnalyzer)
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->propertyFetchAnalyzer = $propertyFetchAnalyzer;
    }
    /**
     * Covers:
     * - $this->propertyName = $expr;
     * - $this->propertyName[] = $expr;
     */
    public function matchPropertyAssignExpr(Assign $assign, string $propertyName) : ?Expr
    {
        if ($this->propertyFetchAnalyzer->isPropertyFetch($assign->var)) {
            if (!$this->nodeNameResolver->isName($assign->var, $propertyName)) {
                return null;
            }
            return $assign->expr;
        }
        if ($assign->var instanceof ArrayDimFetch && $this->propertyFetchAnalyzer->isPropertyFetch($assign->var->var)) {
            if (!$this->nodeNameResolver->isName($assign->var->var, $propertyName)) {
                return null;
            }
            return $assign->expr;
        }
        return null;
    }
}
