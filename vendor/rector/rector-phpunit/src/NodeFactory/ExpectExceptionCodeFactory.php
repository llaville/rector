<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\PHPUnit\NodeFactory;

use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\Rector\NodeNameResolver\NodeNameResolver;
use RectorPrefix20220606\Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
final class ExpectExceptionCodeFactory
{
    /**
     * @readonly
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @readonly
     * @var \Rector\PHPUnit\NodeFactory\ArgumentShiftingFactory
     */
    private $argumentShiftingFactory;
    /**
     * @readonly
     * @var \Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer
     */
    private $testsNodeAnalyzer;
    public function __construct(NodeNameResolver $nodeNameResolver, ArgumentShiftingFactory $argumentShiftingFactory, TestsNodeAnalyzer $testsNodeAnalyzer)
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->argumentShiftingFactory = $argumentShiftingFactory;
        $this->testsNodeAnalyzer = $testsNodeAnalyzer;
    }
    public function create(MethodCall $methodCall, Variable $exceptionVariable) : ?MethodCall
    {
        if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($methodCall, ['assertSame', 'assertEquals'])) {
            return null;
        }
        $secondArgument = $methodCall->args[1]->value;
        if (!$secondArgument instanceof MethodCall) {
            return null;
        }
        // looking for "$exception->getMessage()"
        if (!$this->nodeNameResolver->areNamesEqual($secondArgument->var, $exceptionVariable)) {
            return null;
        }
        if (!$this->nodeNameResolver->isName($secondArgument->name, 'getCode')) {
            return null;
        }
        $this->argumentShiftingFactory->removeAllButFirstArgMethodCall($methodCall, 'expectExceptionCode');
        return $methodCall;
    }
}
