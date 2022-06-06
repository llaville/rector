<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Symfony\Rector\MethodCall;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Arg;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PhpParser\Node\Expr\New_;
use RectorPrefix20220606\PhpParser\Node\Name\FullyQualified;
use RectorPrefix20220606\PHPStan\Type\ObjectType;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://github.com/symfony/symfony/blob/5.x/UPGRADE-5.2.md#form
 * @see \Rector\Symfony\Tests\Rector\MethodCall\FormBuilderSetDataMapperRector\FormBuilderSetDataMapperRectorTest
 */
final class FormBuilderSetDataMapperRector extends AbstractRector
{
    /**
     * @readonly
     * @var \PHPStan\Type\ObjectType
     */
    private $dataMapperObjectType;
    public function __construct()
    {
        $this->dataMapperObjectType = new ObjectType('Symfony\\Component\\Form\\Extension\\Core\\DataMapper\\DataMapper');
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Migrates from deprecated Form Builder->setDataMapper(new PropertyPathMapper()) to Builder->setDataMapper(new DataMapper(new PropertyPathAccessor()))', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormConfigBuilderInterface;

class SomeClass
{
    public function run(FormConfigBuilderInterface $builder)
    {
        $builder->setDataMapper(new PropertyPathMapper());
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormConfigBuilderInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\Extension\Core\DataAccessor\PropertyPathAccessor;

class SomeClass
{
    public function run(FormConfigBuilderInterface $builder)
    {
        $builder->setDataMapper(new DataMapper(new PropertyPathAccessor()));
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
        if (!$this->isObjectType($node->var, new ObjectType('Symfony\\Component\\Form\\FormConfigBuilderInterface'))) {
            return null;
        }
        if (!$this->isName($node->name, 'setDataMapper')) {
            return null;
        }
        $argumentValue = $node->getArgs()[0]->value;
        if ($this->isObjectType($argumentValue, $this->dataMapperObjectType)) {
            return null;
        }
        $propertyPathAccessor = new New_(new FullyQualified('Symfony\\Component\\Form\\Extension\\Core\\DataAccessor\\PropertyPathAccessor'));
        $newArgumentValue = new New_(new FullyQualified($this->dataMapperObjectType->getClassName()), [new Arg($propertyPathAccessor)]);
        $node->getArgs()[0]->value = $newArgumentValue;
        return $node;
    }
}
