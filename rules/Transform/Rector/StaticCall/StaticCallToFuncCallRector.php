<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Transform\Rector\StaticCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\FuncCall;
use RectorPrefix20220606\PhpParser\Node\Expr\StaticCall;
use RectorPrefix20220606\PhpParser\Node\Name\FullyQualified;
use RectorPrefix20220606\Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Transform\ValueObject\StaticCallToFuncCall;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use RectorPrefix20220606\Webmozart\Assert\Assert;
/**
 * @see \Rector\Tests\Transform\Rector\StaticCall\StaticCallToFuncCallRector\StaticCallToFuncCallRectorTest
 */
final class StaticCallToFuncCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var StaticCallToFuncCall[]
     */
    private $staticCallsToFunctions = [];
    /**
     * @param StaticCallToFuncCall[] $staticCallsToFunctions
     */
    public function __construct(array $staticCallsToFunctions = [])
    {
        $this->staticCallsToFunctions = $staticCallsToFunctions;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Turns static call to function call.', [new ConfiguredCodeSample('OldClass::oldMethod("args");', 'new_function("args");', [new StaticCallToFuncCall('OldClass', 'oldMethod', 'new_function')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [StaticCall::class];
    }
    /**
     * @param StaticCall $node
     */
    public function refactor(Node $node) : ?Node
    {
        foreach ($this->staticCallsToFunctions as $staticCallToFunction) {
            if (!$this->isObjectType($node->class, $staticCallToFunction->getObjectType())) {
                continue;
            }
            if (!$this->isName($node->name, $staticCallToFunction->getMethod())) {
                continue;
            }
            return new FuncCall(new FullyQualified($staticCallToFunction->getFunction()), $node->args);
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration) : void
    {
        Assert::allIsAOf($configuration, StaticCallToFuncCall::class);
        $this->staticCallsToFunctions = $configuration;
    }
}
