<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\TypeDeclaration\TypeAnalyzer;

use RectorPrefix20220606\PHPStan\Type\CallableType;
use RectorPrefix20220606\PHPStan\Type\ObjectType;
use RectorPrefix20220606\PHPStan\Type\ObjectWithoutClassType;
use RectorPrefix20220606\PHPStan\Type\Type;
final class ObjectTypeComparator
{
    /**
     * E.g. current E, new type A, E extends A → true
     * Also for closure/callable, iterable/Traversable/Iterator/Generator
     */
    public function isCurrentObjectTypeSubType(Type $currentType, Type $newType) : bool
    {
        if ($newType instanceof ObjectWithoutClassType && $currentType instanceof ObjectType) {
            return \true;
        }
        if ($this->isBothCallable($currentType, $newType)) {
            return \true;
        }
        if (!$currentType instanceof ObjectType) {
            return \false;
        }
        if (!$newType instanceof ObjectType) {
            return \false;
        }
        return $newType->isSuperTypeOf($currentType)->yes();
    }
    private function isBothCallable(Type $currentType, Type $newType) : bool
    {
        if ($currentType instanceof CallableType && $this->isClosure($newType)) {
            return \true;
        }
        return $newType instanceof CallableType && $this->isClosure($currentType);
    }
    private function isClosure(Type $type) : bool
    {
        $closureObjectType = new ObjectType('Closure');
        return $closureObjectType->equals($type);
    }
}
