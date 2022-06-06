<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Doctrine\ValueObject;

use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\Expr\PropertyFetch;
final class AssignToPropertyFetch
{
    /**
     * @readonly
     * @var \PhpParser\Node\Expr\Assign
     */
    private $assign;
    /**
     * @readonly
     * @var \PhpParser\Node\Expr\PropertyFetch
     */
    private $propertyFetch;
    /**
     * @readonly
     * @var string
     */
    private $propertyName;
    public function __construct(Assign $assign, PropertyFetch $propertyFetch, string $propertyName)
    {
        $this->assign = $assign;
        $this->propertyFetch = $propertyFetch;
        $this->propertyName = $propertyName;
    }
    public function getAssign() : Assign
    {
        return $this->assign;
    }
    public function getPropertyFetch() : PropertyFetch
    {
        return $this->propertyFetch;
    }
    public function getPropertyName() : string
    {
        return $this->propertyName;
    }
}
