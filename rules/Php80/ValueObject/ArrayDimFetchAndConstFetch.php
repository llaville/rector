<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php80\ValueObject;

use RectorPrefix20220606\PhpParser\Node\Expr\ArrayDimFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\ConstFetch;
final class ArrayDimFetchAndConstFetch
{
    /**
     * @readonly
     * @var \PhpParser\Node\Expr\ArrayDimFetch
     */
    private $arrayDimFetch;
    /**
     * @readonly
     * @var \PhpParser\Node\Expr\ConstFetch
     */
    private $constFetch;
    public function __construct(ArrayDimFetch $arrayDimFetch, ConstFetch $constFetch)
    {
        $this->arrayDimFetch = $arrayDimFetch;
        $this->constFetch = $constFetch;
    }
    public function getArrayDimFetch() : ArrayDimFetch
    {
        return $this->arrayDimFetch;
    }
    public function getConstFetch() : ConstFetch
    {
        return $this->constFetch;
    }
}
