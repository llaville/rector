<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DowngradePhp70\Rector\FuncCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Arg;
use RectorPrefix20220606\PhpParser\Node\Expr\Array_;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayItem;
use RectorPrefix20220606\PhpParser\Node\Expr\FuncCall;
use RectorPrefix20220606\PhpParser\Node\Name;
use RectorPrefix20220606\PhpParser\Node\Scalar\String_;
use RectorPrefix20220606\PhpParser\Node\Stmt\Expression;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\DowngradePhp70\Rector\FuncCall\DowngradeSessionStartArrayOptionsRector\DowngradeSessionStartArrayOptionsRectorTest
 */
final class DowngradeSessionStartArrayOptionsRector extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [FuncCall::class];
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Move array option of session_start($options) to before statement\'s ini_set()', [new CodeSample(<<<'CODE_SAMPLE'
session_start([
    'cache_limiter' => 'private',
]);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
ini_set('session.cache_limiter', 'private');
session_start();
CODE_SAMPLE
)]);
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node) : ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        if (!isset($node->args[0])) {
            return null;
        }
        if (!$node->args[0] instanceof Arg) {
            return null;
        }
        /** @var Array_ $options */
        $options = $node->args[0]->value;
        foreach ($options->items as $option) {
            if (!$option instanceof ArrayItem) {
                return null;
            }
            if (!$option->key instanceof String_) {
                return null;
            }
            if (!$this->valueResolver->isTrueOrFalse($option->value) && !$option->value instanceof String_) {
                return null;
            }
            $sessionKey = new String_('session.' . $option->key->value);
            $funcName = new Name('ini_set');
            $iniSet = new FuncCall($funcName, [new Arg($sessionKey), new Arg($option->value)]);
            $this->nodesToAddCollector->addNodeBeforeNode(new Expression($iniSet), $node, $this->file->getSmartFileInfo());
        }
        unset($node->args[0]);
        return $node;
    }
    private function shouldSkip(FuncCall $funcCall) : bool
    {
        if (!$this->isName($funcCall, 'session_start')) {
            return \true;
        }
        if (!isset($funcCall->args[0])) {
            return \true;
        }
        if (!$funcCall->args[0] instanceof Arg) {
            return \true;
        }
        return !$funcCall->args[0]->value instanceof Array_;
    }
}
