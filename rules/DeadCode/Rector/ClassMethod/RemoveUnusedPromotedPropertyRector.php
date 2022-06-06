<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DeadCode\Rector\ClassMethod;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\PhpParser\Node\Stmt\Class_;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\Rector\Core\NodeManipulator\PropertyManipulator;
use RectorPrefix20220606\Rector\Core\PhpParser\NodeFinder\PropertyFetchFinder;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\MethodName;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\Core\ValueObject\Visibility;
use RectorPrefix20220606\Rector\Privatization\NodeManipulator\VisibilityManipulator;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector\RemoveUnusedPromotedPropertyRectorTest
 */
final class RemoveUnusedPromotedPropertyRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\NodeFinder\PropertyFetchFinder
     */
    private $propertyFetchFinder;
    /**
     * @readonly
     * @var \Rector\Core\NodeManipulator\PropertyManipulator
     */
    private $propertyManipulator;
    /**
     * @readonly
     * @var \Rector\Privatization\NodeManipulator\VisibilityManipulator
     */
    private $visibilityManipulator;
    public function __construct(PropertyFetchFinder $propertyFetchFinder, PropertyManipulator $propertyManipulator, VisibilityManipulator $visibilityManipulator)
    {
        $this->propertyFetchFinder = $propertyFetchFinder;
        $this->propertyManipulator = $propertyManipulator;
        $this->visibilityManipulator = $visibilityManipulator;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Remove unused promoted property', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        private $someUnusedDependency,
        private $usedDependency
    ) {
    }

    public function getUsedDependency()
    {
        return $this->usedDependency;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        private $usedDependency
    ) {
    }

    public function getUsedDependency()
    {
        return $this->usedDependency;
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
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node) : ?Node
    {
        $hasRemovedProperty = \false;
        if (!$this->isName($node, MethodName::CONSTRUCT)) {
            return null;
        }
        $class = $this->betterNodeFinder->findParentType($node, Class_::class);
        if (!$class instanceof Class_) {
            return null;
        }
        foreach ($node->getParams() as $param) {
            // only private local scope; removing public property might be dangerous
            if (!$this->visibilityManipulator->hasVisibility($param, Visibility::PRIVATE)) {
                continue;
            }
            if ($this->propertyManipulator->isPropertyUsedInReadContext($class, $param)) {
                continue;
            }
            $paramName = $this->getName($param);
            $propertyFetches = $this->propertyFetchFinder->findLocalPropertyFetchesByName($class, $paramName);
            if ($propertyFetches !== []) {
                continue;
            }
            // is variable used? only remove property, keep param
            $variable = $this->betterNodeFinder->findVariableOfName((array) $node->stmts, $paramName);
            if ($variable instanceof Variable) {
                $param->flags = 0;
                continue;
            }
            // remove param
            $this->removeNode($param);
            $hasRemovedProperty = \true;
        }
        if ($hasRemovedProperty) {
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::PROPERTY_PROMOTION;
    }
}
