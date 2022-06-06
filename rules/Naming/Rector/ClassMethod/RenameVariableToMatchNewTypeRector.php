<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Naming\Rector\ClassMethod;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\Expr\New_;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Naming\Guard\BreakingVariableRenameGuard;
use RectorPrefix20220606\Rector\Naming\Naming\ExpectedNameResolver;
use RectorPrefix20220606\Rector\Naming\VariableRenamer;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector\RenameVariableToMatchNewTypeRectorTest
 */
final class RenameVariableToMatchNewTypeRector extends AbstractRector
{
    /**
     * @readonly
     * @var \Rector\Naming\Guard\BreakingVariableRenameGuard
     */
    private $breakingVariableRenameGuard;
    /**
     * @readonly
     * @var \Rector\Naming\Naming\ExpectedNameResolver
     */
    private $expectedNameResolver;
    /**
     * @readonly
     * @var \Rector\Naming\VariableRenamer
     */
    private $variableRenamer;
    public function __construct(BreakingVariableRenameGuard $breakingVariableRenameGuard, ExpectedNameResolver $expectedNameResolver, VariableRenamer $variableRenamer)
    {
        $this->breakingVariableRenameGuard = $breakingVariableRenameGuard;
        $this->expectedNameResolver = $expectedNameResolver;
        $this->variableRenamer = $variableRenamer;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Rename variable to match new ClassType', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        $search = new DreamSearch();
        $search->advance();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        $dreamSearch = new DreamSearch();
        $dreamSearch->advance();
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
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node) : ?Node
    {
        $hasChanged = \false;
        $assignsOfNew = $this->getAssignsOfNew($node);
        foreach ($assignsOfNew as $assignOfNew) {
            $expectedName = $this->expectedNameResolver->resolveForAssignNew($assignOfNew);
            /** @var Variable $variable */
            $variable = $assignOfNew->var;
            if ($expectedName === null) {
                continue;
            }
            if ($this->isName($variable, $expectedName)) {
                continue;
            }
            $currentName = $this->getName($variable);
            if ($currentName === null) {
                continue;
            }
            if ($this->breakingVariableRenameGuard->shouldSkipVariable($currentName, $expectedName, $node, $variable)) {
                continue;
            }
            $hasChanged = \true;
            // 1. rename assigned variable
            $assignOfNew->var = new Variable($expectedName);
            // 2. rename variable in the
            $this->variableRenamer->renameVariableInFunctionLike($node, $currentName, $expectedName, $assignOfNew);
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @return Assign[]
     */
    private function getAssignsOfNew(ClassMethod $classMethod) : array
    {
        /** @var Assign[] $assigns */
        $assigns = $this->betterNodeFinder->findInstanceOf((array) $classMethod->stmts, Assign::class);
        return \array_filter($assigns, function (Assign $assign) : bool {
            return $assign->expr instanceof New_;
        });
    }
}
