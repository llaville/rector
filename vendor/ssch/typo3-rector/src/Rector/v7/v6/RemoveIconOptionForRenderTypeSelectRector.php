<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Ssch\TYPO3Rector\Rector\v7\v6;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Array_;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayItem;
use RectorPrefix20220606\PhpParser\Node\Scalar\String_;
use RectorPrefix20220606\PhpParser\Node\Stmt\Return_;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Ssch\TYPO3Rector\Helper\TcaHelperTrait;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/7.6/Breaking-70033-TcaIconOptionsForSelectFields.html
 * @see \Ssch\TYPO3Rector\Tests\Rector\v7\v6\RemoveIconOptionForRenderTypeSelectRector\RemoveIconOptionForRenderTypeSelectRectorTest
 */
final class RemoveIconOptionForRenderTypeSelectRector extends AbstractRector
{
    use TcaHelperTrait;
    /**
     * @var string
     */
    private const SHOW_ICON_TABLE = 'showIconTable';
    /**
     * @codeCoverageIgnore
     */
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('TCA icon options have been removed', [new CodeSample(<<<'CODE_SAMPLE'
return [
    'columns' => [
        'foo' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'noIconsBelowSelect' => false,
            ],
        ],
    ],
];
CODE_SAMPLE
, <<<'CODE_SAMPLE'
return [
    'columns' => [
        'foo' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'showIconTable' => true,
            ],
        ],
    ],
];
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Return_::class];
    }
    /**
     * @param Return_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (!$this->isFullTca($node)) {
            return null;
        }
        $columnsArrayItem = $this->extractColumns($node);
        if (!$columnsArrayItem instanceof ArrayItem) {
            return null;
        }
        $items = $columnsArrayItem->value;
        if (!$items instanceof Array_) {
            return null;
        }
        $hasAstBeenChanged = \false;
        foreach ($items->items as $fieldValue) {
            if (!$fieldValue instanceof ArrayItem) {
                continue;
            }
            if (null === $fieldValue->key) {
                continue;
            }
            $fieldName = $this->valueResolver->getValue($fieldValue->key);
            if (null === $fieldName) {
                continue;
            }
            if (!$fieldValue->value instanceof Array_) {
                continue;
            }
            foreach ($fieldValue->value->items as $configValue) {
                if (null === $configValue) {
                    continue;
                }
                if (!$configValue->value instanceof Array_) {
                    continue;
                }
                $renderType = null;
                $selicon_cols = null;
                $showIconTable = null;
                $noIconsBelowSelect = null;
                $doSomething = \false;
                foreach ($configValue->value->items as $configItemValue) {
                    if (!$configItemValue instanceof ArrayItem) {
                        continue;
                    }
                    if (null === $configItemValue->key) {
                        continue;
                    }
                    if ($this->valueResolver->isValue($configItemValue->key, 'renderType')) {
                        $renderType = $this->valueResolver->getValue($configItemValue->value);
                    } elseif ($this->valueResolver->isValue($configItemValue->key, 'selicon_cols')) {
                        $selicon_cols = $this->valueResolver->getValue($configItemValue->value);
                        $doSomething = \true;
                    } elseif ($this->valueResolver->isValue($configItemValue->key, self::SHOW_ICON_TABLE)) {
                        $showIconTable = $this->valueResolver->getValue($configItemValue->value);
                    } elseif ($this->valueResolver->isValue($configItemValue->key, 'suppress_icons')) {
                        $this->removeNode($configItemValue);
                        $hasAstBeenChanged = \true;
                    } elseif ($this->valueResolver->isValue($configItemValue->key, 'noIconsBelowSelect')) {
                        $noIconsBelowSelect = $this->valueResolver->getValue($configItemValue->value);
                        $doSomething = \true;
                        $this->removeNode($configItemValue);
                        $hasAstBeenChanged = \true;
                    } elseif ($this->valueResolver->isValue($configItemValue->key, 'foreign_table_loadIcons')) {
                        $this->removeNode($configItemValue);
                        $hasAstBeenChanged = \true;
                    }
                }
                if (!$doSomething) {
                    continue;
                }
                if (null === $renderType || 'selectSingle' !== $renderType) {
                    continue;
                }
                if (null !== $selicon_cols && null === $showIconTable) {
                    $configValue->value->items[] = new ArrayItem($this->nodeFactory->createTrue(), new String_(self::SHOW_ICON_TABLE));
                    $hasAstBeenChanged = \true;
                } elseif (!$noIconsBelowSelect && null === $showIconTable) {
                    $configValue->value->items[] = new ArrayItem($this->nodeFactory->createTrue(), new String_(self::SHOW_ICON_TABLE));
                    $hasAstBeenChanged = \true;
                }
            }
        }
        return $hasAstBeenChanged ? $node : null;
    }
}
