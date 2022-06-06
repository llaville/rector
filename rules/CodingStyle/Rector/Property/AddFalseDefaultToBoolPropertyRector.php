<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\CodingStyle\Rector\Property;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Stmt\Class_;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassLike;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PhpParser\Node\Stmt\Trait_;
use RectorPrefix20220606\PHPStan\Type\BooleanType;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\TypeDeclaration\AlreadyAssignDetector\ConstructorAssignDetector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\CodingStyle\Rector\Property\AddFalseDefaultToBoolPropertyRector\AddFalseDefaultToBoolPropertyRectorTest
 */
final class AddFalseDefaultToBoolPropertyRector extends AbstractRector
{
    /**
     * @readonly
     * @var \Rector\TypeDeclaration\AlreadyAssignDetector\ConstructorAssignDetector
     */
    private $constructorAssignDetector;
    public function __construct(ConstructorAssignDetector $constructorAssignDetector)
    {
        $this->constructorAssignDetector = $constructorAssignDetector;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Add false default to bool properties, to prevent null compare errors', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var bool
     */
    private $isDisabled;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var bool
     */
    private $isDisabled = false;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Property::class];
    }
    /**
     * @param Property $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (\count($node->props) !== 1) {
            return null;
        }
        $onlyProperty = $node->props[0];
        if ($onlyProperty->default !== null) {
            return null;
        }
        if (!$this->isBoolDocType($node)) {
            return null;
        }
        $classLike = $this->betterNodeFinder->findParentByTypes($node, [Class_::class, Trait_::class]);
        if (!$classLike instanceof ClassLike) {
            return null;
        }
        $propertyName = $this->nodeNameResolver->getName($onlyProperty);
        if ($this->constructorAssignDetector->isPropertyAssigned($classLike, $propertyName)) {
            return null;
        }
        $onlyProperty->default = $this->nodeFactory->createFalse();
        return $node;
    }
    private function isBoolDocType(Property $property) : bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        return $phpDocInfo->getVarType() instanceof BooleanType;
    }
}
