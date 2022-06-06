<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Symfony\Rector\MethodCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Expr\StaticCall;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use RectorPrefix20220606\Rector\Symfony\NodeAnalyzer\SymfonyTestCaseAnalyzer;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://symfony.com/blog/new-in-symfony-4-3-better-test-assertions
 * @changelog https://github.com/symfony/symfony/pull/30813
 *
 * @see \Rector\Symfony\Tests\Rector\MethodCall\WebTestCaseAssertSelectorTextContainsRector\WebTestCaseAssertSelectorTextContainsRectorTest
 */
final class WebTestCaseAssertSelectorTextContainsRector extends AbstractRector
{
    /**
     * @readonly
     * @var \Rector\Symfony\NodeAnalyzer\SymfonyTestCaseAnalyzer
     */
    private $symfonyTestCaseAnalyzer;
    /**
     * @readonly
     * @var \Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer
     */
    private $testsNodeAnalyzer;
    public function __construct(SymfonyTestCaseAnalyzer $symfonyTestCaseAnalyzer, TestsNodeAnalyzer $testsNodeAnalyzer)
    {
        $this->symfonyTestCaseAnalyzer = $symfonyTestCaseAnalyzer;
        $this->testsNodeAnalyzer = $testsNodeAnalyzer;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Simplify use of assertions in WebTestCase to assertSelectorTextContains()', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

final class SomeTest extends WebTestCase
{
    public function testContains()
    {
        $crawler = new Symfony\Component\DomCrawler\Crawler();
        $this->assertContains('Hello World', $crawler->filter('h1')->text());
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

final class SomeTest extends WebTestCase
{
    public function testContains()
    {
        $crawler = new Symfony\Component\DomCrawler\Crawler();
        $this->assertSelectorTextContains('h1', 'Hello World');
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
        if (!$this->testsNodeAnalyzer->isAssertMethodCallName($node, 'assertContains')) {
            return null;
        }
        $args = $node->getArgs();
        $firstArgValue = $args[1]->value;
        if (!$firstArgValue instanceof MethodCall) {
            return null;
        }
        $methodCall = $firstArgValue;
        if (!$this->isName($methodCall->name, 'text')) {
            return null;
        }
        if (!$methodCall->var instanceof MethodCall) {
            return null;
        }
        $nestedMethodCall = $methodCall->var;
        if (!$this->isName($nestedMethodCall->name, 'filter')) {
            return null;
        }
        $newArgs = [$nestedMethodCall->args[0], $args[0]];
        return $this->nodeFactory->createLocalMethodCall('assertSelectorTextContains', $newArgs);
    }
}
