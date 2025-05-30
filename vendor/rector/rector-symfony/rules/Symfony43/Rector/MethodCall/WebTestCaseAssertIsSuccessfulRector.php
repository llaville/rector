<?php

declare (strict_types=1);
namespace Rector\Symfony\Symfony43\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use Rector\NodeAnalyzer\ExprAnalyzer;
use Rector\PhpParser\Node\Value\ValueResolver;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Rector\Rector\AbstractRector;
use Rector\Symfony\NodeAnalyzer\SymfonyTestCaseAnalyzer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://symfony.com/blog/new-in-symfony-4-3-better-test-assertions
 * @changelog https://github.com/symfony/symfony/pull/30813
 *
 * @see \Rector\Symfony\Tests\Symfony43\Rector\MethodCall\WebTestCaseAssertIsSuccessfulRector\WebTestCaseAssertIsSuccessfulRectorTest
 */
final class WebTestCaseAssertIsSuccessfulRector extends AbstractRector
{
    /**
     * @readonly
     */
    private SymfonyTestCaseAnalyzer $symfonyTestCaseAnalyzer;
    /**
     * @readonly
     */
    private TestsNodeAnalyzer $testsNodeAnalyzer;
    /**
     * @readonly
     */
    private ExprAnalyzer $exprAnalyzer;
    /**
     * @readonly
     */
    private ValueResolver $valueResolver;
    public function __construct(SymfonyTestCaseAnalyzer $symfonyTestCaseAnalyzer, TestsNodeAnalyzer $testsNodeAnalyzer, ExprAnalyzer $exprAnalyzer, ValueResolver $valueResolver)
    {
        $this->symfonyTestCaseAnalyzer = $symfonyTestCaseAnalyzer;
        $this->testsNodeAnalyzer = $testsNodeAnalyzer;
        $this->exprAnalyzer = $exprAnalyzer;
        $this->valueResolver = $valueResolver;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Simplify use of assertions in WebTestCase', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeClass extends TestCase
{
    public function test()
    {
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeClass extends TestCase
{
    public function test()
    {
        $this->assertResponseIsSuccessful();
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
        return [MethodCall::class, StaticCall::class];
    }
    /**
     * @param MethodCall|StaticCall $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (!$this->symfonyTestCaseAnalyzer->isInWebTestCase($node)) {
            return null;
        }
        if (!$this->testsNodeAnalyzer->isAssertMethodCallName($node, 'assertSame')) {
            return null;
        }
        $args = $node->getArgs();
        if ($this->valueResolver->getValue($args[0]->value, \true) !== 200) {
            return null;
        }
        $secondArg = $args[1]->value;
        if (!$secondArg instanceof MethodCall) {
            return null;
        }
        if (!$this->isName($secondArg->name, 'getStatusCode')) {
            return null;
        }
        $newArgs = [];
        // When we had a custom message argument we want to add it to the new assert.
        if (isset($args[2])) {
            if ($this->exprAnalyzer->isDynamicExpr($args[2]->value)) {
                $newArgs[] = $args[2]->value;
            } else {
                $newArgs[] = new Arg(new String_($this->valueResolver->getValue($args[2]->value, \true)));
            }
        }
        if ($node instanceof StaticCall) {
            return $this->nodeFactory->createStaticCall('self', 'assertResponseIsSuccessful', $newArgs);
        }
        return $this->nodeFactory->createLocalMethodCall('assertResponseIsSuccessful', $newArgs);
    }
}
