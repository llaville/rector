<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\TypeDeclaration\NodeAnalyzer;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Param;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\PHPStan\Type\CallableType;
use RectorPrefix20220606\PHPStan\Type\MixedType;
use RectorPrefix20220606\PHPStan\Type\ObjectType;
use RectorPrefix20220606\PHPStan\Type\Type;
use RectorPrefix20220606\PHPStan\Type\UnionType;
use RectorPrefix20220606\Rector\Core\Php\PhpVersionProvider;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use RectorPrefix20220606\Rector\PHPStanStaticTypeMapper\TypeAnalyzer\UnionTypeCommonTypeNarrower;
use RectorPrefix20220606\Rector\StaticTypeMapper\StaticTypeMapper;
use RectorPrefix20220606\Rector\VendorLocker\NodeVendorLocker\ClassMethodParamVendorLockResolver;
final class ClassMethodParamTypeCompleter
{
    /**
     * @readonly
     * @var \Rector\StaticTypeMapper\StaticTypeMapper
     */
    private $staticTypeMapper;
    /**
     * @readonly
     * @var \Rector\VendorLocker\NodeVendorLocker\ClassMethodParamVendorLockResolver
     */
    private $classMethodParamVendorLockResolver;
    /**
     * @readonly
     * @var \Rector\PHPStanStaticTypeMapper\TypeAnalyzer\UnionTypeCommonTypeNarrower
     */
    private $unionTypeCommonTypeNarrower;
    /**
     * @readonly
     * @var \Rector\Core\Php\PhpVersionProvider
     */
    private $phpVersionProvider;
    public function __construct(StaticTypeMapper $staticTypeMapper, ClassMethodParamVendorLockResolver $classMethodParamVendorLockResolver, UnionTypeCommonTypeNarrower $unionTypeCommonTypeNarrower, PhpVersionProvider $phpVersionProvider)
    {
        $this->staticTypeMapper = $staticTypeMapper;
        $this->classMethodParamVendorLockResolver = $classMethodParamVendorLockResolver;
        $this->unionTypeCommonTypeNarrower = $unionTypeCommonTypeNarrower;
        $this->phpVersionProvider = $phpVersionProvider;
    }
    /**
     * @param array<int, Type> $classParameterTypes
     */
    public function complete(ClassMethod $classMethod, array $classParameterTypes, int $maxUnionTypes) : ?ClassMethod
    {
        $hasChanged = \false;
        foreach ($classParameterTypes as $position => $argumentStaticType) {
            /** @var Type $argumentStaticType */
            if ($this->shouldSkipArgumentStaticType($classMethod, $argumentStaticType, $position, $maxUnionTypes)) {
                continue;
            }
            $phpParserTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($argumentStaticType, TypeKind::PARAM);
            if (!$phpParserTypeNode instanceof Node) {
                continue;
            }
            // check default override
            $param = $classMethod->params[$position];
            if (!$this->isAcceptedByDefault($param, $argumentStaticType)) {
                continue;
            }
            // update parameter
            $param->type = $phpParserTypeNode;
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $classMethod;
        }
        return null;
    }
    private function shouldSkipArgumentStaticType(ClassMethod $classMethod, Type $argumentStaticType, int $position, int $maxUnionTypes) : bool
    {
        if ($argumentStaticType instanceof MixedType) {
            return \true;
        }
        // skip mixed in union type
        if ($argumentStaticType instanceof UnionType && $argumentStaticType->isSuperTypeOf(new MixedType())->yes()) {
            return \true;
        }
        if (!isset($classMethod->params[$position])) {
            return \true;
        }
        if ($this->classMethodParamVendorLockResolver->isVendorLocked($classMethod)) {
            return \true;
        }
        $parameter = $classMethod->params[$position];
        if ($parameter->type === null) {
            return \false;
        }
        $currentParameterStaticType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($parameter->type);
        if ($this->isClosureAndCallableType($currentParameterStaticType, $argumentStaticType)) {
            return \true;
        }
        // narrow union type in case its not supported yet
        $argumentStaticType = $this->narrowUnionTypeIfNotSupported($argumentStaticType);
        // too many union types
        if ($this->isTooDetailedUnionType($currentParameterStaticType, $argumentStaticType, $maxUnionTypes)) {
            return \true;
        }
        // current type already accepts the one added
        if ($currentParameterStaticType->accepts($argumentStaticType, \true)->yes()) {
            return \true;
        }
        // avoid overriding more precise type
        if ($argumentStaticType->isSuperTypeOf($currentParameterStaticType)->yes()) {
            return \true;
        }
        // already completed → skip
        return $currentParameterStaticType->equals($argumentStaticType);
    }
    private function isClosureAndCallableType(Type $parameterStaticType, Type $argumentStaticType) : bool
    {
        if ($parameterStaticType instanceof CallableType && $this->isClosureObjectType($argumentStaticType)) {
            return \true;
        }
        return $argumentStaticType instanceof CallableType && $this->isClosureObjectType($parameterStaticType);
    }
    private function isClosureObjectType(Type $type) : bool
    {
        if (!$type instanceof ObjectType) {
            return \false;
        }
        return $type->getClassName() === 'Closure';
    }
    private function isTooDetailedUnionType(Type $currentType, Type $newType, int $maxUnionTypes) : bool
    {
        if ($currentType instanceof MixedType) {
            return \false;
        }
        if (!$newType instanceof UnionType) {
            return \false;
        }
        return \count($newType->getTypes()) > $maxUnionTypes;
    }
    private function narrowUnionTypeIfNotSupported(Type $type) : Type
    {
        if (!$type instanceof UnionType) {
            return $type;
        }
        // union is supported, so it's ok
        if ($this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::UNION_TYPES)) {
            return $type;
        }
        $narrowedObjectType = $this->unionTypeCommonTypeNarrower->narrowToSharedObjectType($type);
        if ($narrowedObjectType instanceof ObjectType) {
            return $narrowedObjectType;
        }
        return $type;
    }
    private function isAcceptedByDefault(Param $param, Type $argumentStaticType) : bool
    {
        if (!$param->default instanceof Expr) {
            return \true;
        }
        $defaultExpr = $param->default;
        $defaultStaticType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($defaultExpr);
        return $argumentStaticType->accepts($defaultStaticType, \false)->yes();
    }
}
