<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Nette\Rector\MethodCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\BinaryOp\Coalesce;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PHPStan\Type\ObjectType;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Nette\Tests\Rector\MethodCall\RequestGetCookieDefaultArgumentToCoalesceRector\RequestGetCookieDefaultArgumentToCoalesceRectorTest
 */
final class RequestGetCookieDefaultArgumentToCoalesceRector extends AbstractRector
{
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Add removed Nette\\Http\\Request::getCookies() default value as coalesce', [new CodeSample(<<<'CODE_SAMPLE'
use Nette\Http\Request;

class SomeClass
{
    public function run(Request $request)
    {
        return $request->getCookie('name', 'default');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Nette\Http\Request;

class SomeClass
{
    public function run(Request $request)
    {
        return $request->getCookie('name') ?? 'default';
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
        if (!$this->isObjectType($node->var, new ObjectType('Nette\\Http\\Request'))) {
            return null;
        }
        if (!$this->isName($node->name, 'getCookie')) {
            return null;
        }
        // no default value
        if (!isset($node->args[1])) {
            return null;
        }
        $defaultValue = $node->args[1]->value;
        unset($node->args[1]);
        return new Coalesce($node, $defaultValue);
    }
}
