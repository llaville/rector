<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Laravel\Rector\MethodCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\FuncCall;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Expr\StaticCall;
use RectorPrefix20220606\PhpParser\Node\Name;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Defluent\NodeAnalyzer\FluentChainMethodCallNodeAnalyzer;
use RectorPrefix20220606\Rector\NodeTypeResolver\Node\AttributeKey;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Laravel\Tests\Rector\MethodCall\RedirectRouteToToRouteHelperRector\RedirectRouteToToRouteHelperRectorTest
 */
final class RedirectRouteToToRouteHelperRector extends AbstractRector
{
    /**
     * @readonly
     * @var \Rector\Defluent\NodeAnalyzer\FluentChainMethodCallNodeAnalyzer
     */
    private $fluentChainMethodCallNodeAnalyzer;
    public function __construct(FluentChainMethodCallNodeAnalyzer $fluentChainMethodCallNodeAnalyzer)
    {
        $this->fluentChainMethodCallNodeAnalyzer = $fluentChainMethodCallNodeAnalyzer;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Replace `redirect()->route("home")` and `Redirect::route("home")` with `to_route("home")`', [new CodeSample(<<<'CODE_SAMPLE'
use Illuminate\Support\Facades\Redirect;

class MyController
{
    public function store()
    {
        return redirect()->route('home')->with('error', 'Incorrect Details.')
    }

    public function update()
    {
        return Redirect::route('home')->with('error', 'Incorrect Details.')
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Illuminate\Support\Facades\Redirect;

class MyController
{
    public function store()
    {
        return to_route('home')->with('error', 'Incorrect Details.')
    }

    public function update()
    {
        return to_route('home')->with('error', 'Incorrect Details.')
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
        if ($node instanceof MethodCall) {
            return $this->updateRedirectHelperCall($node);
        }
        return $this->updateRedirectStaticCall($node);
    }
    private function updateRedirectHelperCall(MethodCall $methodCall) : ?MethodCall
    {
        if (!$this->isName($methodCall->name, 'route')) {
            return null;
        }
        $rootExpr = $this->fluentChainMethodCallNodeAnalyzer->resolveRootExpr($methodCall);
        $parentNode = $rootExpr->getAttribute(AttributeKey::PARENT_NODE);
        if (!$parentNode instanceof MethodCall) {
            return null;
        }
        if (!$parentNode->var instanceof FuncCall) {
            return null;
        }
        if ($parentNode->var->getArgs() !== []) {
            return null;
        }
        if (!$this->isName($parentNode->var->name, 'redirect')) {
            return null;
        }
        $this->removeNode($methodCall);
        $parentNode->var->name = new Name('to_route');
        $parentNode->var->args = $methodCall->getArgs();
        return $parentNode;
    }
    private function updateRedirectStaticCall(StaticCall $staticCall) : ?FuncCall
    {
        if (!$this->isName($staticCall->class, 'Illuminate\\Support\\Facades\\Redirect')) {
            return null;
        }
        if (!$this->isName($staticCall->name, 'route')) {
            return null;
        }
        return new FuncCall(new Name('to_route'), $staticCall->args);
    }
}
