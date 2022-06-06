<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DowngradePhp74\Rector\Coalesce;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\Expr\AssignOp\Coalesce as AssignCoalesce;
use RectorPrefix20220606\PhpParser\Node\Expr\BinaryOp\Coalesce;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/null_coalesce_equal_operator
 * @see \Rector\Tests\DowngradePhp74\Rector\Coalesce\DowngradeNullCoalescingOperatorRector\DowngradeNullCoalescingOperatorRectorTest
 */
final class DowngradeNullCoalescingOperatorRector extends AbstractRector
{
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Remove null coalescing operator ??=', [new CodeSample(<<<'CODE_SAMPLE'
$array = [];
$array['user_id'] ??= 'value';
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$array = [];
$array['user_id'] = $array['user_id'] ?? 'value';
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [AssignCoalesce::class];
    }
    /**
     * @param AssignCoalesce $node
     */
    public function refactor(Node $node) : Assign
    {
        return new Assign($node->var, new Coalesce($node->var, $node->expr));
    }
}
