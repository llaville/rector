<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DowngradePhp81\Rector\Array_;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Array_;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayItem;
use RectorPrefix20220606\PHPStan\Analyser\Scope;
use RectorPrefix20220606\PHPStan\Type\ArrayType;
use RectorPrefix20220606\PHPStan\Type\IntegerType;
use RectorPrefix20220606\Rector\Core\Rector\AbstractScopeAwareRector;
use RectorPrefix20220606\Rector\DowngradePhp81\NodeAnalyzer\ArraySpreadAnalyzer;
use RectorPrefix20220606\Rector\DowngradePhp81\NodeFactory\ArrayMergeFromArraySpreadFactory;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/array_unpacking_string_keys
 *
 * @see \Rector\Tests\DowngradePhp81\Rector\Array_\DowngradeArraySpreadStringKeyRector\DowngradeArraySpreadStringKeyRectorTest
 */
final class DowngradeArraySpreadStringKeyRector extends AbstractScopeAwareRector
{
    /**
     * @readonly
     * @var \Rector\DowngradePhp81\NodeFactory\ArrayMergeFromArraySpreadFactory
     */
    private $arrayMergeFromArraySpreadFactory;
    /**
     * @readonly
     * @var \Rector\DowngradePhp81\NodeAnalyzer\ArraySpreadAnalyzer
     */
    private $arraySpreadAnalyzer;
    public function __construct(ArrayMergeFromArraySpreadFactory $arrayMergeFromArraySpreadFactory, ArraySpreadAnalyzer $arraySpreadAnalyzer)
    {
        $this->arrayMergeFromArraySpreadFactory = $arrayMergeFromArraySpreadFactory;
        $this->arraySpreadAnalyzer = $arraySpreadAnalyzer;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Replace array spread with string key to array_merge function', [new CodeSample(<<<'CODE_SAMPLE'
$parts = ['a' => 'b'];
$parts2 = ['c' => 'd'];

$result = [...$parts, ...$parts2];
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$parts = ['a' => 'b'];
$parts2 = ['c' => 'd'];

$result = array_merge($parts, $parts2);
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Array_::class];
    }
    /**
     * @param Array_ $node
     */
    public function refactorWithScope(Node $node, Scope $scope) : ?Node
    {
        if ($this->shouldSkipArray($node)) {
            return null;
        }
        return $this->arrayMergeFromArraySpreadFactory->createFromArray($node, $scope, $this->file);
    }
    private function shouldSkipArray(Array_ $array) : bool
    {
        if (!$this->arraySpreadAnalyzer->isArrayWithUnpack($array)) {
            return \true;
        }
        foreach ($array->items as $item) {
            if (!$item instanceof ArrayItem) {
                continue;
            }
            $type = $this->nodeTypeResolver->getType($item->value);
            if (!$type instanceof ArrayType) {
                continue;
            }
            $keyType = $type->getKeyType();
            if ($keyType instanceof IntegerType) {
                return \true;
            }
        }
        return \false;
    }
}
