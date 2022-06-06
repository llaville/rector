<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DeadCode\Comparator\Parameter;

use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Param;
use RectorPrefix20220606\PHPStan\Reflection\ParameterReflection;
use RectorPrefix20220606\Rector\Core\PhpParser\Comparing\NodeComparator;
use RectorPrefix20220606\Rector\DowngradePhp80\Reflection\DefaultParameterValueResolver;
final class ParameterDefaultsComparator
{
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Comparing\NodeComparator
     */
    private $nodeComparator;
    /**
     * @readonly
     * @var \Rector\DowngradePhp80\Reflection\DefaultParameterValueResolver
     */
    private $defaultParameterValueResolver;
    public function __construct(NodeComparator $nodeComparator, DefaultParameterValueResolver $defaultParameterValueResolver)
    {
        $this->nodeComparator = $nodeComparator;
        $this->defaultParameterValueResolver = $defaultParameterValueResolver;
    }
    public function areDefaultValuesDifferent(ParameterReflection $parameterReflection, Param $param) : bool
    {
        if ($parameterReflection->getDefaultValue() === null && $param->default === null) {
            return \false;
        }
        if ($this->isMutuallyExclusiveNull($parameterReflection, $param)) {
            return \true;
        }
        /** @var Expr $paramDefault */
        $paramDefault = $param->default;
        $firstParameterValue = $this->defaultParameterValueResolver->resolveFromParameterReflection($parameterReflection);
        return !$this->nodeComparator->areNodesEqual($paramDefault, $firstParameterValue);
    }
    private function isMutuallyExclusiveNull(ParameterReflection $parameterReflection, Param $param) : bool
    {
        if ($parameterReflection->getDefaultValue() === null && $param->default !== null) {
            return \true;
        }
        if ($parameterReflection->getDefaultValue() === null) {
            return \false;
        }
        return $param->default === null;
    }
}
