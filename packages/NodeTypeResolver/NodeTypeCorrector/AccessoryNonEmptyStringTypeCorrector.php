<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\NodeTypeResolver\NodeTypeCorrector;

use RectorPrefix20220606\PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use RectorPrefix20220606\PHPStan\Type\IntersectionType;
use RectorPrefix20220606\PHPStan\Type\Type;
final class AccessoryNonEmptyStringTypeCorrector
{
    /**
     * @return \PHPStan\Type\Type|\PHPStan\Type\IntersectionType
     */
    public function correct(Type $mainType)
    {
        if (!$mainType instanceof IntersectionType) {
            return $mainType;
        }
        if (!$mainType->isSubTypeOf(new AccessoryNonEmptyStringType())->yes()) {
            return $mainType;
        }
        $clearIntersectionedTypes = [];
        foreach ($mainType->getTypes() as $intersectionedType) {
            if ($intersectionedType instanceof AccessoryNonEmptyStringType) {
                continue;
            }
            $clearIntersectionedTypes[] = $intersectionedType;
        }
        if (\count($clearIntersectionedTypes) === 1) {
            return $clearIntersectionedTypes[0];
        }
        return new IntersectionType($clearIntersectionedTypes);
    }
}
