<?php

declare (strict_types=1);
namespace Rector\Naming\RenameGuard;

use PHPStan\Type\ObjectType;
use Rector\Naming\Guard\DateTimeAtNamingConventionGuard;
use Rector\Naming\Guard\HasMagicGetSetGuard;
use Rector\Naming\ValueObject\PropertyRename;
use Rector\NodeTypeResolver\NodeTypeResolver;
final class PropertyRenameGuard
{
    /**
     * @readonly
     */
    private NodeTypeResolver $nodeTypeResolver;
    /**
     * @readonly
     */
    private DateTimeAtNamingConventionGuard $dateTimeAtNamingConventionGuard;
    /**
     * @readonly
     */
    private HasMagicGetSetGuard $hasMagicGetSetGuard;
    public function __construct(NodeTypeResolver $nodeTypeResolver, DateTimeAtNamingConventionGuard $dateTimeAtNamingConventionGuard, HasMagicGetSetGuard $hasMagicGetSetGuard)
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->dateTimeAtNamingConventionGuard = $dateTimeAtNamingConventionGuard;
        $this->hasMagicGetSetGuard = $hasMagicGetSetGuard;
    }
    public function shouldSkip(PropertyRename $propertyRename) : bool
    {
        if (!$propertyRename->isPrivateProperty()) {
            return \true;
        }
        if ($this->nodeTypeResolver->isObjectType($propertyRename->getProperty(), new ObjectType('Ramsey\\Uuid\\UuidInterface'))) {
            return \true;
        }
        // skip date times, as often custom named and using "dateTime" does not bring any value to code
        if ($propertyRename->getExpectedName() === 'dateTime') {
            return \true;
        }
        if ($this->dateTimeAtNamingConventionGuard->isConflicting($propertyRename)) {
            return \true;
        }
        return $this->hasMagicGetSetGuard->isConflicting($propertyRename);
    }
}
