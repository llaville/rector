<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DowngradePhp54\Rector\FuncCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Arg;
use RectorPrefix20220606\PhpParser\Node\Expr\FuncCall;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\PhpParser\Node\Name;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\NodeTypeResolver\TypeAnalyzer\StringTypeAnalyzer;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/indirect-method-call-by-array-var
 *
 * @see \Rector\Tests\DowngradePhp54\Rector\FuncCall\DowngradeIndirectCallByArrayRector\DowngradeIndirectCallByArrayRectorTest
 */
final class DowngradeIndirectCallByArrayRector extends AbstractRector
{
    /**
     * @readonly
     * @var \Rector\NodeTypeResolver\TypeAnalyzer\StringTypeAnalyzer
     */
    private $stringTypeAnalyzer;
    public function __construct(StringTypeAnalyzer $stringTypeAnalyzer)
    {
        $this->stringTypeAnalyzer = $stringTypeAnalyzer;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Downgrade indirect method call by array variable', [new CodeSample(<<<'CODE_SAMPLE'
class Hello {
    public static function world($x) {
        echo "Hello, $x\n";
    }
}

$func = array('Hello','world');
$func('you');
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class Hello {
    public static function world($x) {
        echo "Hello, $x\n";
    }
}

$func = array('Hello','world');
call_user_func($func, 'you');
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node) : ?FuncCall
    {
        if (!$node->name instanceof Variable) {
            return null;
        }
        if ($this->stringTypeAnalyzer->isStringOrUnionStringOnlyType($node->name)) {
            return null;
        }
        $args = \array_merge([new Arg($node->name)], $node->args);
        return new FuncCall(new Name('call_user_func'), $args);
    }
}
