<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\TypeDeclaration\NodeAnalyzer;

use RectorPrefix20220606\PhpParser\Node\Arg;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Expr\StaticCall;
use RectorPrefix20220606\PHPStan\Type\MixedType;
use RectorPrefix20220606\PHPStan\Type\NullType;
use RectorPrefix20220606\PHPStan\Type\ObjectType;
use RectorPrefix20220606\PHPStan\Type\ThisType;
use RectorPrefix20220606\PHPStan\Type\Type;
use RectorPrefix20220606\PHPStan\Type\TypeWithClassName;
use RectorPrefix20220606\PHPStan\Type\UnionType;
use RectorPrefix20220606\Rector\NodeCollector\ValueObject\ArrayCallable;
use RectorPrefix20220606\Rector\NodeTypeResolver\NodeTypeResolver;
use RectorPrefix20220606\Rector\NodeTypeResolver\PHPStan\Type\TypeFactory;
final class CallTypesResolver
{
    /**
     * @readonly
     * @var \Rector\NodeTypeResolver\NodeTypeResolver
     */
    private $nodeTypeResolver;
    /**
     * @readonly
     * @var \Rector\NodeTypeResolver\PHPStan\Type\TypeFactory
     */
    private $typeFactory;
    public function __construct(NodeTypeResolver $nodeTypeResolver, TypeFactory $typeFactory)
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->typeFactory = $typeFactory;
    }
    /**
     * @param MethodCall[]|StaticCall[]|ArrayCallable[] $calls
     * @return array<int, Type>
     */
    public function resolveStrictTypesFromCalls(array $calls) : array
    {
        $staticTypesByArgumentPosition = [];
        foreach ($calls as $call) {
            if (!$call instanceof StaticCall && !$call instanceof MethodCall) {
                continue;
            }
            foreach ($call->args as $position => $arg) {
                if (!$arg instanceof Arg) {
                    continue;
                }
                $argValueType = $this->resolveStrictArgValueType($arg);
                $staticTypesByArgumentPosition[$position][] = $argValueType;
            }
        }
        // unite to single type
        return $this->unionToSingleType($staticTypesByArgumentPosition);
    }
    private function resolveStrictArgValueType(Arg $arg) : Type
    {
        $argValueType = $this->nodeTypeResolver->getNativeType($arg->value);
        // "self" in another object is not correct, this make it independent
        return $this->correctSelfType($argValueType);
    }
    private function correctSelfType(Type $argValueType) : Type
    {
        if ($argValueType instanceof ThisType) {
            return new ObjectType($argValueType->getClassName());
        }
        return $argValueType;
    }
    /**
     * @param array<int, Type[]> $staticTypesByArgumentPosition
     * @return array<int, Type>
     */
    private function unionToSingleType(array $staticTypesByArgumentPosition) : array
    {
        $staticTypeByArgumentPosition = [];
        foreach ($staticTypesByArgumentPosition as $position => $staticTypes) {
            $unionedType = $this->typeFactory->createMixedPassedOrUnionType($staticTypes);
            // narrow parents to most child type
            $unionedType = $this->narrowParentObjectTreeToSingleObjectChildType($unionedType);
            $staticTypeByArgumentPosition[$position] = $unionedType;
        }
        if (\count($staticTypeByArgumentPosition) !== 1) {
            return $staticTypeByArgumentPosition;
        }
        if (!$staticTypeByArgumentPosition[0] instanceof NullType) {
            return $staticTypeByArgumentPosition;
        }
        return [new MixedType()];
    }
    private function narrowParentObjectTreeToSingleObjectChildType(Type $type) : Type
    {
        if (!$type instanceof UnionType) {
            return $type;
        }
        if (!$this->isTypeWithClassNameOnly($type)) {
            return $type;
        }
        /** @var TypeWithClassName $firstUnionedType */
        $firstUnionedType = $type->getTypes()[0];
        foreach ($type->getTypes() as $unionedType) {
            if (!$unionedType instanceof TypeWithClassName) {
                return $type;
            }
            if ($unionedType->isSuperTypeOf($firstUnionedType)->yes()) {
                return $type;
            }
        }
        return $firstUnionedType;
    }
    private function isTypeWithClassNameOnly(UnionType $unionType) : bool
    {
        foreach ($unionType->getTypes() as $unionedType) {
            if (!$unionedType instanceof TypeWithClassName) {
                return \false;
            }
        }
        return \true;
    }
}
