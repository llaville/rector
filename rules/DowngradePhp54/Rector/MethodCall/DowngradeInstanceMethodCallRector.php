<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DowngradePhp54\Rector\MethodCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayDimFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\Expr\Clone_;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Expr\New_;
use RectorPrefix20220606\PhpParser\Node\Expr\PropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Stmt\Expression;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\NamedVariableFactory;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\NodeTypeResolver\Node\AttributeKey;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/instance-method-call
 *
 * @see \Rector\Tests\DowngradePhp54\Rector\MethodCall\DowngradeInstanceMethodCallRector\DowngradeInstanceMethodCallRectorTest
 */
final class DowngradeInstanceMethodCallRector extends AbstractRector
{
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\NamedVariableFactory
     */
    private $namedVariableFactory;
    public function __construct(NamedVariableFactory $namedVariableFactory)
    {
        $this->namedVariableFactory = $namedVariableFactory;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Downgrade instance and method call/property access', [new CodeSample(<<<'CODE_SAMPLE'
echo (new \ReflectionClass('\\stdClass'))->getName();
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$object = new \ReflectionClass('\\stdClass');
echo $object->getName();
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [MethodCall::class, PropertyFetch::class, ArrayDimFetch::class];
    }
    /**
     * @param ArrayDimFetch|MethodCall|PropertyFetch $node
     */
    public function refactor(Node $node) : ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        $variable = $this->namedVariableFactory->createVariable($node, 'object');
        $expression = new Expression(new Assign($variable, $node->var));
        $this->nodesToAddCollector->addNodeBeforeNode($expression, $node, $this->file->getSmartFileInfo());
        $node->var = $variable;
        // necessary to remove useless parentheses
        $node->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        return $node;
    }
    /**
     * @param \PhpParser\Node\Expr\ArrayDimFetch|\PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\PropertyFetch $node
     */
    private function shouldSkip($node) : bool
    {
        if ($node->var instanceof New_) {
            return \false;
        }
        return !$node->var instanceof Clone_;
    }
}
