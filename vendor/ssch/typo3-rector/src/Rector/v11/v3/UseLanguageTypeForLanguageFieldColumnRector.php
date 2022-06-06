<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Ssch\TYPO3Rector\Rector\v11\v3;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\Array_;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayItem;
use RectorPrefix20220606\PhpParser\Node\Scalar\String_;
use RectorPrefix20220606\Ssch\TYPO3Rector\Rector\Tca\AbstractTcaRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/11.3/Deprecation-94165-SysLanguageDatabaseTable.html
 * @see \Ssch\TYPO3Rector\Tests\Rector\v11\v3\UseLanguageTypeForLanguageFieldColumnRector\UseLanguageTypeForLanguageFieldColumnRectorTest
 */
final class UseLanguageTypeForLanguageFieldColumnRector extends AbstractTcaRector
{
    /**
     * @var string|null
     */
    private $languageField;
    /**
     * @codeCoverageIgnore
     */
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('use the new TCA type language instead of foreign_table => sys_language for selecting a records', [new CodeSample(<<<'CODE_SAMPLE'
return [
    'ctrl' => [
        'languageField' => 'sys_language_uid',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'Language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'eval' => 'int',
                'items' => [
                    [$_LLL_general . ':LGL.allLanguages', -1],
                    [$_LLL_general . ':LGL.default_value', 0]

                ],
            ],
        ],
    ],
];
CODE_SAMPLE
, <<<'CODE_SAMPLE'
return [
    'ctrl' => [
        'languageField' => 'sys_language_uid',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'Language',
            'config' => [
                'type' => 'language'
            ],
        ],
    ],
];
CODE_SAMPLE
)]);
    }
    /**
     * @param Array_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        $this->hasAstBeenChanged = \false;
        if (!$this->isFullTcaDefinition($node)) {
            return null;
        }
        $ctrlArray = $this->extractSubArrayByKey($node, 'ctrl');
        if (!$ctrlArray instanceof Array_) {
            return null;
        }
        $value = $this->extractArrayValueByKey($ctrlArray, 'languageField');
        if (!$value instanceof String_) {
            return null;
        }
        $this->languageField = $this->valueResolver->getValue($value);
        if (null === $this->languageField) {
            return null;
        }
        // we found a tca definition of a full table. Process it as a whole:
        $columnsArray = $this->extractSubArrayByKey($node, 'columns');
        if (null !== $columnsArray) {
            $this->refactorColumnList($columnsArray);
        }
        return $this->hasAstBeenChanged ? $node : null;
    }
    protected function refactorColumn(Expr $columnName, Expr $columnTca) : void
    {
        $column = $this->valueResolver->getValue($columnName);
        if ($column !== $this->languageField) {
            return;
        }
        $configuration = $this->extractArrayItemByKey($columnTca, 'config');
        if (!$configuration instanceof ArrayItem) {
            return;
        }
        $foreignTable = $this->extractArrayItemByKey($configuration->value, 'foreign_table');
        if (!$foreignTable instanceof ArrayItem) {
            return;
        }
        if (!$this->valueResolver->isValue($foreignTable->value, 'sys_language')) {
            return;
        }
        $configuration->value = $this->nodeFactory->createArray(['type' => 'language']);
        $this->hasAstBeenChanged = \true;
    }
}
