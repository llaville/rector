<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\CodingStyle\Rector\ClassConst;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Const_;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassConst;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PhpParser\Node\Stmt\PropertyProperty;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\CodingStyle\Rector\ClassConst\SplitGroupedConstantsAndPropertiesRector\SplitGroupedConstantsAndPropertiesRectorTest
 */
final class SplitGroupedConstantsAndPropertiesRector extends AbstractRector
{
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Separate constant and properties to own lines', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    const HI = true, AHOJ = 'true';

    /**
     * @var string
     */
    public $isIt, $isIsThough;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    const HI = true;
    const AHOJ = 'true';

    /**
     * @var string
     */
    public $isIt;

    /**
     * @var string
     */
    public $isIsThough;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [ClassConst::class, Property::class];
    }
    /**
     * @param ClassConst|Property $node
     * @return Node[]|null
     */
    public function refactor(Node $node) : ?array
    {
        if ($node instanceof ClassConst) {
            if (\count($node->consts) < 2) {
                return null;
            }
            /** @var Const_[] $allConsts */
            $allConsts = $node->consts;
            /** @var Const_ $firstConst */
            $firstConst = \array_shift($allConsts);
            $node->consts = [$firstConst];
            $nextClassConsts = $this->createNextClassConsts($allConsts, $node);
            return \array_merge([$node], $nextClassConsts);
        }
        if (\count($node->props) < 2) {
            return null;
        }
        $allProperties = $node->props;
        /** @var PropertyProperty $firstPropertyProperty */
        $firstPropertyProperty = \array_shift($allProperties);
        $node->props = [$firstPropertyProperty];
        $nextProperties = [];
        foreach ($allProperties as $allProperty) {
            $nextProperties[] = new Property($node->flags, [$allProperty], $node->getAttributes());
        }
        $item0Unpacked = [$node];
        return \array_merge($item0Unpacked, $nextProperties);
    }
    /**
     * @param Const_[] $consts
     * @return ClassConst[]
     */
    private function createNextClassConsts(array $consts, ClassConst $classConst) : array
    {
        $decoratedConsts = [];
        foreach ($consts as $const) {
            $decoratedConsts[] = new ClassConst([$const], $classConst->flags, $classConst->getAttributes());
        }
        return $decoratedConsts;
    }
}
