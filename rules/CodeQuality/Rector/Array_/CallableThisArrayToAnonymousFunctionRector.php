<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\CodeQuality\Rector\Array_;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Array_;
use RectorPrefix20220606\PHPStan\Analyser\Scope;
use RectorPrefix20220606\PHPStan\Reflection\Php\PhpMethodReflection;
use RectorPrefix20220606\Rector\Core\Rector\AbstractScopeAwareRector;
use RectorPrefix20220606\Rector\Core\Reflection\ReflectionResolver;
use RectorPrefix20220606\Rector\NodeCollector\NodeAnalyzer\ArrayCallableMethodMatcher;
use RectorPrefix20220606\Rector\NodeCollector\ValueObject\ArrayCallable;
use RectorPrefix20220606\Rector\Php72\NodeFactory\AnonymousFunctionFactory;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://www.php.net/manual/en/language.types.callable.php#117260
 * @changelog https://3v4l.org/MsMbQ
 * @changelog https://3v4l.org/KM1Ji
 *
 * @see \Rector\Tests\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector\CallableThisArrayToAnonymousFunctionRectorTest
 */
final class CallableThisArrayToAnonymousFunctionRector extends AbstractScopeAwareRector
{
    /**
     * @readonly
     * @var \Rector\Php72\NodeFactory\AnonymousFunctionFactory
     */
    private $anonymousFunctionFactory;
    /**
     * @readonly
     * @var \Rector\Core\Reflection\ReflectionResolver
     */
    private $reflectionResolver;
    /**
     * @readonly
     * @var \Rector\NodeCollector\NodeAnalyzer\ArrayCallableMethodMatcher
     */
    private $arrayCallableMethodMatcher;
    public function __construct(AnonymousFunctionFactory $anonymousFunctionFactory, ReflectionResolver $reflectionResolver, ArrayCallableMethodMatcher $arrayCallableMethodMatcher)
    {
        $this->anonymousFunctionFactory = $anonymousFunctionFactory;
        $this->reflectionResolver = $reflectionResolver;
        $this->arrayCallableMethodMatcher = $arrayCallableMethodMatcher;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Convert [$this, "method"] to proper anonymous function', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $values = [1, 5, 3];
        usort($values, [$this, 'compareSize']);

        return $values;
    }

    private function compareSize($first, $second)
    {
        return $first <=> $second;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $values = [1, 5, 3];
        usort($values, function ($first, $second) {
            return $this->compareSize($first, $second);
        });

        return $values;
    }

    private function compareSize($first, $second)
    {
        return $first <=> $second;
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
    public function refactorWithScope(Node $node, Scope $scope) : ?Node
    {
        $arrayCallable = $this->arrayCallableMethodMatcher->match($node);
        if (!$arrayCallable instanceof ArrayCallable) {
            return null;
        }
        $phpMethodReflection = $this->reflectionResolver->resolveMethodReflection($arrayCallable->getClass(), $arrayCallable->getMethod(), $scope);
        if (!$phpMethodReflection instanceof PhpMethodReflection) {
            return null;
        }
        return $this->anonymousFunctionFactory->createFromPhpMethodReflection($phpMethodReflection, $arrayCallable->getCallerExpr());
    }
}
