<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DowngradePhp70\Rector\Expr;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayDimFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\FuncCall;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Expr\PropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\StaticCall;
use RectorPrefix20220606\PhpParser\Node\Expr\StaticPropertyFetch;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\DowngradePhp70\Tokenizer\WrappedInParenthesesAnalyzer;
use RectorPrefix20220606\Rector\NodeTypeResolver\Node\AttributeKey;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/uniform_variable_syntax
 *
 * @see \Rector\Tests\DowngradePhp70\Rector\Expr\DowngradeUnnecessarilyParenthesizedExpressionRector\DowngradeUnnecessarilyParenthesizedExpressionRectorTest
 */
final class DowngradeUnnecessarilyParenthesizedExpressionRector extends AbstractRector
{
    /**
     * @var array<class-string<Expr>>
     */
    private const PARENTHESIZABLE_NODES = [ArrayDimFetch::class, PropertyFetch::class, MethodCall::class, StaticPropertyFetch::class, StaticCall::class, FuncCall::class];
    /**
     * @readonly
     * @var \Rector\DowngradePhp70\Tokenizer\WrappedInParenthesesAnalyzer
     */
    private $wrappedInParenthesesAnalyzer;
    public function __construct(WrappedInParenthesesAnalyzer $wrappedInParenthesesAnalyzer)
    {
        $this->wrappedInParenthesesAnalyzer = $wrappedInParenthesesAnalyzer;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Remove parentheses around expressions allowed by Uniform variable syntax RFC where they are not necessary to prevent parse errors on PHP 5.', [new CodeSample(<<<'CODE_SAMPLE'
($f)['foo'];
($f)->foo;
($f)->foo();
($f)::$foo;
($f)::foo();
($f)();
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$f['foo'];
$f->foo;
$f->foo();
$f::$foo;
$f::foo();
$f();
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [
            // TODO: Make PHPStan rules allow Expr namespace for its subclasses.
            Expr::class,
        ];
    }
    /**
     * @param ArrayDimFetch|PropertyFetch|MethodCall|StaticPropertyFetch|StaticCall|FuncCall $node
     */
    public function refactor(Node $node) : ?Expr
    {
        if (!\in_array(\get_class($node), self::PARENTHESIZABLE_NODES, \true)) {
            return null;
        }
        $leftSubNode = $this->getLeftSubNode($node);
        if (!$leftSubNode instanceof Node) {
            return null;
        }
        if (!$this->wrappedInParenthesesAnalyzer->isParenthesized($this->file, $leftSubNode)) {
            return null;
        }
        // Parenthesization is not part of the AST and Rector only re-generates code for AST nodes that changed.
        // Let’s remove the original node reference forcing the re-generation of the corresponding code.
        // The code generator will only put parentheses where strictly necessary, which other rules should handle.
        $node->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        return $node;
    }
    private function getLeftSubNode(Node $node) : ?Node
    {
        switch (\true) {
            case $node instanceof ArrayDimFetch:
                return $node->var;
            case $node instanceof PropertyFetch:
                return $node->var;
            case $node instanceof MethodCall:
                return $node->var;
            case $node instanceof StaticPropertyFetch:
                return $node->class;
            case $node instanceof StaticCall:
                return $node->class;
            case $node instanceof FuncCall:
                return $node->name;
            default:
                return null;
        }
    }
}
