<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Nette\FormControlTypeResolver;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\Rector\Core\Exception\ShouldNotHappenException;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\Value\ValueResolver;
use RectorPrefix20220606\Rector\Nette\Contract\FormControlTypeResolverInterface;
use RectorPrefix20220606\Rector\Nette\Enum\NetteFormMethodNameToControlType;
use RectorPrefix20220606\Rector\Nette\NodeAnalyzer\MethodCallManipulator;
use RectorPrefix20220606\Rector\NodeNameResolver\NodeNameResolver;
final class OnVariableMethodCallsFormControlTypeResolver implements FormControlTypeResolverInterface
{
    /**
     * @readonly
     * @var \Rector\Nette\NodeAnalyzer\MethodCallManipulator
     */
    private $methodCallManipulator;
    /**
     * @readonly
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\Value\ValueResolver
     */
    private $valueResolver;
    public function __construct(MethodCallManipulator $methodCallManipulator, NodeNameResolver $nodeNameResolver, ValueResolver $valueResolver)
    {
        $this->methodCallManipulator = $methodCallManipulator;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->valueResolver = $valueResolver;
    }
    /**
     * @return array<string, string>
     */
    public function resolve(Node $node) : array
    {
        if (!$node instanceof Variable) {
            return [];
        }
        $onFormMethodCalls = $this->methodCallManipulator->findMethodCallsOnVariable($node);
        $methodNamesByInputNames = [];
        foreach ($onFormMethodCalls as $onFormMethodCall) {
            $methodName = $this->nodeNameResolver->getName($onFormMethodCall->name);
            if ($methodName === null) {
                continue;
            }
            if (!isset(NetteFormMethodNameToControlType::METHOD_NAME_TO_CONTROL_TYPE[$methodName])) {
                continue;
            }
            if (!isset($onFormMethodCall->args[0])) {
                continue;
            }
            $addedInputName = $this->valueResolver->getValue($onFormMethodCall->args[0]->value);
            if (!\is_string($addedInputName)) {
                throw new ShouldNotHappenException();
            }
            $methodNamesByInputNames[$addedInputName] = $methodName;
        }
        return $methodNamesByInputNames;
    }
}
