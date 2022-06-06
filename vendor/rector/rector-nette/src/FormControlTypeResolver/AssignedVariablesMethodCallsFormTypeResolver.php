<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Nette\FormControlTypeResolver;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Assign;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\BetterNodeFinder;
use RectorPrefix20220606\Rector\Nette\Contract\FormControlTypeResolverInterface;
use RectorPrefix20220606\Rector\Nette\NodeResolver\MethodNamesByInputNamesResolver;
use RectorPrefix20220606\Symfony\Contracts\Service\Attribute\Required;
final class AssignedVariablesMethodCallsFormTypeResolver implements FormControlTypeResolverInterface
{
    /**
     * @var \Rector\Nette\NodeResolver\MethodNamesByInputNamesResolver
     */
    private $methodNamesByInputNamesResolver;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\BetterNodeFinder
     */
    private $betterNodeFinder;
    public function __construct(BetterNodeFinder $betterNodeFinder)
    {
        $this->betterNodeFinder = $betterNodeFinder;
    }
    /**
     * @required
     */
    public function autowire(MethodNamesByInputNamesResolver $methodNamesByInputNamesResolver) : void
    {
        $this->methodNamesByInputNamesResolver = $methodNamesByInputNamesResolver;
    }
    /**
     * @return array<string, string>
     */
    public function resolve(Node $node) : array
    {
        if (!$node instanceof Variable) {
            return [];
        }
        $formVariableAssign = $this->betterNodeFinder->findPreviousAssignToExpr($node);
        if (!$formVariableAssign instanceof Assign) {
            return [];
        }
        return $this->methodNamesByInputNamesResolver->resolveExpr($formVariableAssign->expr);
    }
}
