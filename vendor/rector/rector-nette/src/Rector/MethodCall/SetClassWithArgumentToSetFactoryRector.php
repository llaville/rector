<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Nette\Rector\MethodCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Identifier;
use RectorPrefix20220606\PHPStan\Type\ObjectType;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://github.com/nette/di/pull/146/files
 *
 * @see \Rector\Nette\Tests\Rector\MethodCall\SetClassWithArgumentToSetFactoryRector\SetClassWithArgumentToSetFactoryRectorTest
 */
final class SetClassWithArgumentToSetFactoryRector extends AbstractRector
{
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Change setClass with class and arguments to separated methods', [new CodeSample(<<<'CODE_SAMPLE'
use Nette\DI\ContainerBuilder;

class SomeClass
{
    public function run(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addDefinition('...')
            ->setClass('SomeClass', [1, 2]);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Nette\DI\ContainerBuilder;

class SomeClass
{
    public function run(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addDefinition('...')
            ->setFactory('SomeClass', [1, 2]);
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
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (!$this->isName($node->name, 'setClass')) {
            return null;
        }
        if (\count($node->args) !== 2) {
            return null;
        }
        if (!$this->isObjectType($node->var, new ObjectType('Nette\\DI\\Definitions\\ServiceDefinition'))) {
            return null;
        }
        $node->name = new Identifier('setFactory');
        return $node;
    }
}
