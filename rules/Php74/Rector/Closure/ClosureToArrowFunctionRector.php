<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php74\Rector\Closure;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrowFunction;
use RectorPrefix20220606\PhpParser\Node\Expr\Closure;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\NodeTypeResolver\Node\AttributeKey;
use RectorPrefix20220606\Rector\Php74\NodeAnalyzer\ClosureArrowFunctionAnalyzer;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/arrow_functions_v2
 *
 * @see \Rector\Tests\Php74\Rector\Closure\ClosureToArrowFunctionRector\ClosureToArrowFunctionRectorTest
 */
final class ClosureToArrowFunctionRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @readonly
     * @var \Rector\Php74\NodeAnalyzer\ClosureArrowFunctionAnalyzer
     */
    private $closureArrowFunctionAnalyzer;
    public function __construct(ClosureArrowFunctionAnalyzer $closureArrowFunctionAnalyzer)
    {
        $this->closureArrowFunctionAnalyzer = $closureArrowFunctionAnalyzer;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Change closure to arrow function', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($meetups)
    {
        return array_filter($meetups, function (Meetup $meetup) {
            return is_object($meetup);
        });
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($meetups)
    {
        return array_filter($meetups, fn(Meetup $meetup) => is_object($meetup));
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
        return [Closure::class];
    }
    /**
     * @param Closure $node
     */
    public function refactor(Node $node) : ?Node
    {
        $returnExpr = $this->closureArrowFunctionAnalyzer->matchArrowFunctionExpr($node);
        if (!$returnExpr instanceof Expr) {
            return null;
        }
        $arrowFunction = new ArrowFunction(['params' => $node->params, 'returnType' => $node->returnType, 'byRef' => $node->byRef, 'expr' => $returnExpr]);
        if ($node->static) {
            $arrowFunction->static = \true;
        }
        $comments = $node->stmts[0]->getAttribute(AttributeKey::COMMENTS) ?? [];
        if ($comments !== []) {
            $this->mirrorComments($arrowFunction->expr, $node->stmts[0]);
            $arrowFunction->setAttribute(AttributeKey::COMMENT_CLOSURE_RETURN_MIRRORED, \true);
        }
        return $arrowFunction;
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::ARROW_FUNCTION;
    }
}
