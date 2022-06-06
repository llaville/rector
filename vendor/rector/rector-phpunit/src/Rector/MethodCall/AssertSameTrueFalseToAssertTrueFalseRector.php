<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\PHPUnit\Rector\MethodCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Identifier;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use RectorPrefix20220606\Rector\PHPUnit\NodeManipulator\ArgumentMover;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\PHPUnit\Tests\Rector\MethodCall\AssertSameTrueFalseToAssertTrueFalseRector\AssertSameTrueFalseToAssertTrueFalseRectorTest
 */
final class AssertSameTrueFalseToAssertTrueFalseRector extends AbstractRector
{
    /**
     * @readonly
     * @var \Rector\PHPUnit\NodeManipulator\ArgumentMover
     */
    private $argumentMover;
    /**
     * @readonly
     * @var \Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer
     */
    private $testsNodeAnalyzer;
    public function __construct(ArgumentMover $argumentMover, TestsNodeAnalyzer $testsNodeAnalyzer)
    {
        $this->argumentMover = $argumentMover;
        $this->testsNodeAnalyzer = $testsNodeAnalyzer;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Change $this->assertSame(true, ...) to assertTrue()', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $value = (bool) mt_rand(0, 1);
        $this->assertSame(true, $value);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $value = (bool) mt_rand(0, 1);
        $this->assertTrue($value);
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($node, ['assertSame', 'assertEqual', 'assertNotSame', 'assertNotEqual'])) {
            return null;
        }
        if ($this->valueResolver->isTrue($node->args[0]->value)) {
            $this->argumentMover->removeFirst($node);
            $node->name = new Identifier('assertTrue');
            return $node;
        }
        if ($this->valueResolver->isFalse($node->args[0]->value)) {
            $this->argumentMover->removeFirst($node);
            $node->name = new Identifier('assertFalse');
            return $node;
        }
        return null;
    }
}
