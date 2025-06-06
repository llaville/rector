<?php

declare (strict_types=1);
namespace Rector\TypeDeclaration\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Rector\Enum\ClassName;
use Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Rector\TypeDeclaration\AlreadyAssignDetector\ConstructorAssignDetector;
use Rector\TypeDeclaration\TypeInferer\AssignToPropertyTypeInferer;
use Rector\ValueObject\PhpVersionFeature;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector\TypedPropertyFromCreateMockAssignRectorTest
 */
final class TypedPropertyFromCreateMockAssignRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @readonly
     */
    private AssignToPropertyTypeInferer $assignToPropertyTypeInferer;
    /**
     * @readonly
     */
    private StaticTypeMapper $staticTypeMapper;
    /**
     * @readonly
     */
    private ConstructorAssignDetector $constructorAssignDetector;
    public function __construct(AssignToPropertyTypeInferer $assignToPropertyTypeInferer, StaticTypeMapper $staticTypeMapper, ConstructorAssignDetector $constructorAssignDetector)
    {
        $this->assignToPropertyTypeInferer = $assignToPropertyTypeInferer;
        $this->staticTypeMapper = $staticTypeMapper;
        $this->constructorAssignDetector = $constructorAssignDetector;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Add typed property from assigned mock', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    private $someProperty;

    protected function setUp(): void
    {
        $this->someProperty = $this->createMock(SomeMockedClass::class);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $someProperty;

    protected function setUp(): void
    {
        $this->someProperty = $this->createMock(SomeMockedClass::class);
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes() : array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (!$this->isObjectType($node, new ObjectType(ClassName::TEST_CASE_CLASS))) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            // already use PHPUnit\Framework\MockObject\MockObject type
            if ($property->type instanceof Node && $this->isObjectType($property->type, new ObjectType(ClassName::MOCK_OBJECT))) {
                continue;
            }
            if (\count($property->props) !== 1) {
                continue;
            }
            $propertyName = (string) $this->getName($property);
            $type = $this->assignToPropertyTypeInferer->inferPropertyInClassLike($property, $propertyName, $node);
            if (!$type instanceof Type) {
                continue;
            }
            $propertyType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($type, TypeKind::PROPERTY);
            if (!$propertyType instanceof Node) {
                continue;
            }
            if (!$this->isObjectType($propertyType, new ObjectType(ClassName::MOCK_OBJECT))) {
                continue;
            }
            if (!$this->constructorAssignDetector->isPropertyAssigned($node, $propertyName)) {
                if (!$propertyType instanceof NullableType) {
                    continue;
                }
                $property->props[0]->default = $this->nodeFactory->createNull();
            }
            $property->type = $propertyType;
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::TYPED_PROPERTIES;
    }
}
