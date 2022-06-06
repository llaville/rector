<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Transform\NodeAnalyzer;

use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\Expr\BinaryOp\Identical;
use RectorPrefix20220606\PhpParser\Node\Expr\BooleanNot;
use RectorPrefix20220606\PhpParser\Node\Expr\New_;
use RectorPrefix20220606\PhpParser\Node\Expr\StaticPropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Stmt\Class_;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\PhpParser\Node\Stmt\Expression;
use RectorPrefix20220606\PhpParser\Node\Stmt\If_;
use RectorPrefix20220606\PHPStan\Type\ObjectType;
use RectorPrefix20220606\Rector\Core\PhpParser\Comparing\NodeComparator;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\BetterNodeFinder;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\Value\ValueResolver;
use RectorPrefix20220606\Rector\NodeNameResolver\NodeNameResolver;
use RectorPrefix20220606\Rector\NodeTypeResolver\NodeTypeResolver;
final class SingletonClassMethodAnalyzer
{
    /**
     * @readonly
     * @var \Rector\NodeTypeResolver\NodeTypeResolver
     */
    private $nodeTypeResolver;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\Value\ValueResolver
     */
    private $valueResolver;
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
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    public function __construct(NodeTypeResolver $nodeTypeResolver, ValueResolver $valueResolver, NodeComparator $nodeComparator, BetterNodeFinder $betterNodeFinder, NodeNameResolver $nodeNameResolver)
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->valueResolver = $valueResolver;
        $this->nodeComparator = $nodeComparator;
        $this->betterNodeFinder = $betterNodeFinder;
        $this->nodeNameResolver = $nodeNameResolver;
    }
    /**
     * Match this code:
     * if (null === static::$instance) {
     *     static::$instance = new static();
     * }
     * return static::$instance;
     *
     * Matches "static::$instance" on success
     */
    public function matchStaticPropertyFetch(ClassMethod $classMethod) : ?StaticPropertyFetch
    {
        $stmts = (array) $classMethod->stmts;
        if (\count($stmts) !== 2) {
            return null;
        }
        $firstStmt = $stmts[0] ?? null;
        if (!$firstStmt instanceof If_) {
            return null;
        }
        $staticPropertyFetch = $this->matchStaticPropertyFetchInIfCond($firstStmt->cond);
        if (\count($firstStmt->stmts) !== 1) {
            return null;
        }
        if (!$firstStmt->stmts[0] instanceof Expression) {
            return null;
        }
        $stmt = $firstStmt->stmts[0]->expr;
        // create self and assign to static property
        if (!$stmt instanceof Assign) {
            return null;
        }
        if (!$this->nodeComparator->areNodesEqual($staticPropertyFetch, $stmt->var)) {
            return null;
        }
        if (!$stmt->expr instanceof New_) {
            return null;
        }
        $class = $this->betterNodeFinder->findParentType($classMethod, Class_::class);
        if (!$class instanceof Class_) {
            return null;
        }
        $className = $this->nodeNameResolver->getName($class);
        if (!\is_string($className)) {
            return null;
        }
        // the "self" class is created
        if (!$this->nodeTypeResolver->isObjectType($stmt->expr->class, new ObjectType($className))) {
            return null;
        }
        return $staticPropertyFetch;
    }
    private function matchStaticPropertyFetchInIfCond(Expr $expr) : ?StaticPropertyFetch
    {
        // matching: "self::$static === null"
        if ($expr instanceof Identical) {
            if ($this->valueResolver->isNull($expr->left) && $expr->right instanceof StaticPropertyFetch) {
                return $expr->right;
            }
            if ($this->valueResolver->isNull($expr->right) && $expr->left instanceof StaticPropertyFetch) {
                return $expr->left;
            }
        }
        // matching: "! self::$static"
        if (!$expr instanceof BooleanNot) {
            return null;
        }
        $negatedExpr = $expr->expr;
        if (!$negatedExpr instanceof StaticPropertyFetch) {
            return null;
        }
        return $negatedExpr;
    }
}
