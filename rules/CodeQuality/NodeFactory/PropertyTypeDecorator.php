<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\CodeQuality\NodeFactory;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\ComplexType;
use RectorPrefix20220606\PhpParser\Node\Identifier;
use RectorPrefix20220606\PhpParser\Node\Name;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PHPStan\Type\ArrayType;
use RectorPrefix20220606\PHPStan\Type\MixedType;
use RectorPrefix20220606\PHPStan\Type\Type;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use RectorPrefix20220606\Rector\Core\Php\PhpVersionProvider;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use RectorPrefix20220606\Rector\Privatization\TypeManipulator\TypeNormalizer;
use RectorPrefix20220606\Rector\StaticTypeMapper\StaticTypeMapper;
final class PropertyTypeDecorator
{
    /**
     * @readonly
     * @var \Rector\Core\Php\PhpVersionProvider
     */
    private $phpVersionProvider;
    /**
     * @readonly
     * @var \Rector\StaticTypeMapper\StaticTypeMapper
     */
    private $staticTypeMapper;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger
     */
    private $phpDocTypeChanger;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory
     */
    private $phpDocInfoFactory;
    /**
     * @readonly
     * @var \Rector\Privatization\TypeManipulator\TypeNormalizer
     */
    private $typeNormalizer;
    public function __construct(PhpVersionProvider $phpVersionProvider, StaticTypeMapper $staticTypeMapper, PhpDocTypeChanger $phpDocTypeChanger, PhpDocInfoFactory $phpDocInfoFactory, TypeNormalizer $typeNormalizer)
    {
        $this->phpVersionProvider = $phpVersionProvider;
        $this->staticTypeMapper = $staticTypeMapper;
        $this->phpDocTypeChanger = $phpDocTypeChanger;
        $this->phpDocInfoFactory = $phpDocInfoFactory;
        $this->typeNormalizer = $typeNormalizer;
    }
    public function decorateProperty(Property $property, Type $propertyType) : void
    {
        // generalize false/true type to bool, as mostly default value but accepts both
        $propertyType = $this->typeNormalizer->generalizeConstantBoolTypes($propertyType);
        $this->decoratePropertyWithVarDoc($property, $propertyType);
        $this->decoratePropertyWithType($property, $propertyType);
    }
    /**
     * @param \PhpParser\Node\ComplexType|\PhpParser\Node\Identifier|\PhpParser\Node\Name $typeNode
     */
    public function decoratePropertyWithDocBlock(Property $property, $typeNode) : void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        if ($phpDocInfo->getVarTagValueNode() !== null) {
            return;
        }
        $newType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($typeNode);
        $this->phpDocTypeChanger->changeVarType($phpDocInfo, $newType);
    }
    private function decoratePropertyWithVarDoc(Property $property, Type $propertyType) : void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        $phpDocInfo->makeMultiLined();
        if ($this->isNonMixedArrayType($propertyType)) {
            $this->phpDocTypeChanger->changeVarType($phpDocInfo, $propertyType);
            $property->type = new Identifier('array');
            return;
        }
        if ($this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::TYPED_PROPERTIES)) {
            $phpParserNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($propertyType, TypeKind::PROPERTY);
            if (!$phpParserNode instanceof Node) {
                // fallback to doc type in PHP 7.4
                $this->phpDocTypeChanger->changeVarType($phpDocInfo, $propertyType);
            }
        } else {
            $this->phpDocTypeChanger->changeVarType($phpDocInfo, $propertyType);
        }
    }
    private function decoratePropertyWithType(Property $property, Type $propertyType) : void
    {
        if (!$this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::TYPED_PROPERTIES)) {
            return;
        }
        $phpParserNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($propertyType, TypeKind::PROPERTY);
        if (!$phpParserNode instanceof Node) {
            return;
        }
        $property->type = $phpParserNode;
    }
    private function isNonMixedArrayType(Type $type) : bool
    {
        if (!$type instanceof ArrayType) {
            return \false;
        }
        if ($type->getKeyType() instanceof MixedType) {
            return \false;
        }
        return !$type->getItemType() instanceof MixedType;
    }
}
