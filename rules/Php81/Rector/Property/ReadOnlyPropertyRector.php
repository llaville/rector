<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php81\Rector\Property;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Param;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\Rector\Core\NodeManipulator\PropertyFetchAssignManipulator;
use RectorPrefix20220606\Rector\Core\NodeManipulator\PropertyManipulator;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\Core\ValueObject\Visibility;
use RectorPrefix20220606\Rector\NodeTypeResolver\Node\AttributeKey;
use RectorPrefix20220606\Rector\Privatization\NodeManipulator\VisibilityManipulator;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/readonly_properties_v2
 *
 * @see \Rector\Tests\Php81\Rector\Property\ReadOnlyPropertyRector\ReadOnlyPropertyRectorTest
 */
final class ReadOnlyPropertyRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @readonly
     * @var \Rector\Core\NodeManipulator\PropertyManipulator
     */
    private $propertyManipulator;
    /**
     * @readonly
     * @var \Rector\Core\NodeManipulator\PropertyFetchAssignManipulator
     */
    private $propertyFetchAssignManipulator;
    /**
     * @readonly
     * @var \Rector\Privatization\NodeManipulator\VisibilityManipulator
     */
    private $visibilityManipulator;
    public function __construct(PropertyManipulator $propertyManipulator, PropertyFetchAssignManipulator $propertyFetchAssignManipulator, VisibilityManipulator $visibilityManipulator)
    {
        $this->propertyManipulator = $propertyManipulator;
        $this->propertyFetchAssignManipulator = $propertyFetchAssignManipulator;
        $this->visibilityManipulator = $visibilityManipulator;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Decorate read-only property with `readonly` attribute', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        private string $name
    ) {
    }

    public function getName()
    {
        return $this->name;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        private readonly string $name
    ) {
    }

    public function getName()
    {
        return $this->name;
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
        return [Property::class, Param::class];
    }
    /**
     * @param Property|Param $node
     */
    public function refactor(Node $node) : ?Node
    {
        if ($node instanceof Param) {
            return $this->refactorParam($node);
        }
        return $this->refactorProperty($node);
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::READONLY_PROPERTY;
    }
    private function refactorProperty(Property $property) : ?Property
    {
        // 1. is property read-only?
        if ($this->propertyManipulator->isPropertyChangeableExceptConstructor($property)) {
            return null;
        }
        if ($property->isReadonly()) {
            return null;
        }
        if ($property->props[0]->default instanceof Expr) {
            return null;
        }
        if ($property->type === null) {
            return null;
        }
        if (!$this->visibilityManipulator->hasVisibility($property, Visibility::PRIVATE)) {
            return null;
        }
        if ($property->isStatic()) {
            return null;
        }
        if ($this->propertyFetchAssignManipulator->isAssignedMultipleTimesInConstructor($property)) {
            return null;
        }
        $this->visibilityManipulator->makeReadonly($property);
        $attributeGroups = $property->attrGroups;
        if ($attributeGroups !== []) {
            $property->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        }
        return $property;
    }
    /**
     * @return \PhpParser\Node\Param|null
     */
    private function refactorParam(Param $param)
    {
        if (!$this->visibilityManipulator->hasVisibility($param, Visibility::PRIVATE)) {
            return null;
        }
        if ($param->type === null) {
            return null;
        }
        // promoted property?
        if ($this->propertyManipulator->isPropertyChangeableExceptConstructor($param)) {
            return null;
        }
        if ($this->visibilityManipulator->isReadonly($param)) {
            return null;
        }
        $this->visibilityManipulator->makeReadonly($param);
        return $param;
    }
}
