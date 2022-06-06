<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Symfony\NodeAnalyzer\InvokableAnalyzer;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\ClassConstFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Expr\PropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\Rector\NodeNameResolver\NodeNameResolver;
use RectorPrefix20220606\Rector\Symfony\ValueObject\InvokableController\ActiveClassElements;
use RectorPrefix20220606\Symplify\Astral\NodeTraverser\SimpleCallableNodeTraverser;
final class ActiveClassElementsClassMethodResolver
{
    /**
     * @readonly
     * @var \Symplify\Astral\NodeTraverser\SimpleCallableNodeTraverser
     */
    private $simpleCallableNodeTraverser;
    /**
     * @readonly
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    public function __construct(SimpleCallableNodeTraverser $simpleCallableNodeTraverser, NodeNameResolver $nodeNameResolver)
    {
        $this->simpleCallableNodeTraverser = $simpleCallableNodeTraverser;
        $this->nodeNameResolver = $nodeNameResolver;
    }
    public function resolve(ClassMethod $actionClassMethod) : ActiveClassElements
    {
        $usedLocalPropertyNames = $this->resolveLocalUsedPropertyNames($actionClassMethod);
        $usedLocalConstantNames = $this->resolveLocalUsedConstantNames($actionClassMethod);
        $usedLocalMethodNames = $this->resolveLocalUsedMethodNames($actionClassMethod);
        return new ActiveClassElements($usedLocalPropertyNames, $usedLocalConstantNames, $usedLocalMethodNames);
    }
    /**
     * @return string[]
     */
    private function resolveLocalUsedPropertyNames(ClassMethod $actionClassMethod) : array
    {
        $usedLocalPropertyNames = [];
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($actionClassMethod, function (Node $node) use(&$usedLocalPropertyNames) {
            if (!$node instanceof PropertyFetch) {
                return null;
            }
            if (!$this->nodeNameResolver->isName($node->var, 'this')) {
                return null;
            }
            $propertyName = $this->nodeNameResolver->getName($node->name);
            if (!\is_string($propertyName)) {
                return null;
            }
            $usedLocalPropertyNames[] = $propertyName;
        });
        return $usedLocalPropertyNames;
    }
    /**
     * @return string[]
     */
    private function resolveLocalUsedConstantNames(ClassMethod $actionClassMethod) : array
    {
        $usedLocalConstantNames = [];
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($actionClassMethod, function (Node $node) use(&$usedLocalConstantNames) {
            if (!$node instanceof ClassConstFetch) {
                return null;
            }
            if (!$this->nodeNameResolver->isName($node->class, 'self')) {
                return null;
            }
            $constantName = $this->nodeNameResolver->getName($node->name);
            if (!\is_string($constantName)) {
                return null;
            }
            $usedLocalConstantNames[] = $constantName;
        });
        return $usedLocalConstantNames;
    }
    /**
     * @return string[]
     */
    private function resolveLocalUsedMethodNames(ClassMethod $actionClassMethod) : array
    {
        $usedLocalMethodNames = [];
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($actionClassMethod, function (Node $node) use(&$usedLocalMethodNames) {
            if (!$node instanceof MethodCall) {
                return null;
            }
            if (!$this->nodeNameResolver->isName($node->var, 'this')) {
                return null;
            }
            $methodName = $this->nodeNameResolver->getName($node->name);
            if (!\is_string($methodName)) {
                return null;
            }
            $usedLocalMethodNames[] = $methodName;
        });
        return $usedLocalMethodNames;
    }
}
