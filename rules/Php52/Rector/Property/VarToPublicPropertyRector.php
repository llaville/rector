<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php52\Rector\Property;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\Privatization\NodeManipulator\VisibilityManipulator;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\Php52\Rector\Property\VarToPublicPropertyRector\VarToPublicPropertyRectorTest
 */
final class VarToPublicPropertyRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @readonly
     * @var \Rector\Privatization\NodeManipulator\VisibilityManipulator
     */
    private $visibilityManipulator;
    public function __construct(VisibilityManipulator $visibilityManipulator)
    {
        $this->visibilityManipulator = $visibilityManipulator;
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::PROPERTY_MODIFIER;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Change property modifier from `var` to `public`', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeController
{
    var $name = 'Tom';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeController
{
    public $name = 'Tom';
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Property::class];
    }
    /**
     * @param Property $node
     */
    public function refactor(Node $node) : ?Node
    {
        // explicitly public
        if ($node->flags !== 0) {
            return null;
        }
        $this->visibilityManipulator->makePublic($node);
        return $node;
    }
}
