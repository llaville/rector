<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\CodeQuality\Rector\Array_;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Arg;
use RectorPrefix20220606\PhpParser\Node\Expr\Array_;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayDimFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Expr\PropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PHPStan\Reflection\ReflectionProvider;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\NodeCollector\NodeAnalyzer\ArrayCallableMethodMatcher;
use RectorPrefix20220606\Rector\NodeCollector\ValueObject\ArrayCallable;
use RectorPrefix20220606\Rector\NodeTypeResolver\Node\AttributeKey;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\CodeQuality\Rector\Array_\ArrayThisCallToThisMethodCallRector\ArrayThisCallToThisMethodCallRectorTest
 */
final class ArrayThisCallToThisMethodCallRector extends AbstractRector
{
    /**
     * @readonly
     * @var \Rector\NodeCollector\NodeAnalyzer\ArrayCallableMethodMatcher
     */
    private $arrayCallableMethodMatcher;
    /**
     * @readonly
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    public function __construct(ArrayCallableMethodMatcher $arrayCallableMethodMatcher, ReflectionProvider $reflectionProvider)
    {
        $this->arrayCallableMethodMatcher = $arrayCallableMethodMatcher;
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Change `[$this, someMethod]` without any args to $this->someMethod()', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $values = [$this, 'giveMeMore'];
    }

    public function giveMeMore()
    {
        return 'more';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $values = $this->giveMeMore();
    }

    public function giveMeMore()
    {
        return 'more';
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Array_::class];
    }
    /**
     * @param Array_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        $arrayCallable = $this->arrayCallableMethodMatcher->match($node);
        if (!$arrayCallable instanceof ArrayCallable) {
            return null;
        }
        if ($this->isAssignedToNetteMagicOnProperty($node)) {
            return null;
        }
        if ($this->isInsideProperty($node)) {
            return null;
        }
        $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
        // skip if part of method
        if ($parentNode instanceof Arg) {
            return null;
        }
        if (!$this->reflectionProvider->hasClass($arrayCallable->getClass())) {
            return null;
        }
        $classReflection = $this->reflectionProvider->getClass($arrayCallable->getClass());
        if (!$classReflection->hasMethod($arrayCallable->getMethod())) {
            return null;
        }
        $nativeReflectionClass = $classReflection->getNativeReflection();
        $nativeReflectionMethod = $nativeReflectionClass->getMethod($arrayCallable->getMethod());
        if ($nativeReflectionMethod->getNumberOfParameters() === 0) {
            return new MethodCall(new Variable('this'), $arrayCallable->getMethod());
        }
        $methodReflection = $classReflection->getNativeMethod($arrayCallable->getMethod());
        return $this->nodeFactory->createClosureFromMethodReflection($methodReflection);
    }
    private function isAssignedToNetteMagicOnProperty(Array_ $array) : bool
    {
        $parent = $array->getAttribute(AttributeKey::PARENT_NODE);
        if (!$parent instanceof Assign) {
            return \false;
        }
        if (!$parent->var instanceof ArrayDimFetch) {
            return \false;
        }
        if (!$parent->var->var instanceof PropertyFetch) {
            return \false;
        }
        /** @var PropertyFetch $propertyFetch */
        $propertyFetch = $parent->var->var;
        return $this->isName($propertyFetch->name, 'on*');
    }
    private function isInsideProperty(Array_ $array) : bool
    {
        $parentProperty = $this->betterNodeFinder->findParentType($array, Property::class);
        return $parentProperty !== null;
    }
}
