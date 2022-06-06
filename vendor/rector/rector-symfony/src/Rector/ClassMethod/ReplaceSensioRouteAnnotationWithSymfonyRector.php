<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Symfony\Rector\ClassMethod;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Stmt\Class_;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use RectorPrefix20220606\Rector\Core\Configuration\RenamedClassesDataCollector;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Symfony\Enum\SymfonyAnnotation;
use RectorPrefix20220606\Rector\Symfony\PhpDocNode\SymfonyRouteTagValueNodeFactory;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://medium.com/@nebkam/symfony-deprecated-route-and-method-annotations-4d5e1d34556a
 * @see https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/routing.html#method-annotation
 *
 * @see \Rector\Symfony\Tests\Rector\ClassMethod\ReplaceSensioRouteAnnotationWithSymfonyRector\ReplaceSensioRouteAnnotationWithSymfonyRectorTest
 */
final class ReplaceSensioRouteAnnotationWithSymfonyRector extends AbstractRector
{
    /**
     * @readonly
     * @var \Rector\Symfony\PhpDocNode\SymfonyRouteTagValueNodeFactory
     */
    private $symfonyRouteTagValueNodeFactory;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover
     */
    private $phpDocTagRemover;
    /**
     * @readonly
     * @var \Rector\Core\Configuration\RenamedClassesDataCollector
     */
    private $renamedClassesDataCollector;
    public function __construct(SymfonyRouteTagValueNodeFactory $symfonyRouteTagValueNodeFactory, PhpDocTagRemover $phpDocTagRemover, RenamedClassesDataCollector $renamedClassesDataCollector)
    {
        $this->symfonyRouteTagValueNodeFactory = $symfonyRouteTagValueNodeFactory;
        $this->phpDocTagRemover = $phpDocTagRemover;
        $this->renamedClassesDataCollector = $renamedClassesDataCollector;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Replace Sensio @Route annotation with Symfony one', [new CodeSample(<<<'CODE_SAMPLE'
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

final class SomeClass
{
    /**
     * @Route()
     */
    public function run()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Routing\Annotation\Route;

final class SomeClass
{
    /**
     * @Route()
     */
    public function run()
    {
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
        return [ClassMethod::class, Class_::class];
    }
    /**
     * @param ClassMethod|Class_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        if ($phpDocInfo->hasByAnnotationClass(SymfonyAnnotation::ROUTE)) {
            return null;
        }
        $doctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClass('Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Route');
        if (!$doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return null;
        }
        $this->renamedClassesDataCollector->addOldToNewClasses(['Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Route' => SymfonyAnnotation::ROUTE]);
        $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $doctrineAnnotationTagValueNode);
        // unset service, that is deprecated
        $values = $doctrineAnnotationTagValueNode->getValues();
        $symfonyRouteTagValueNode = $this->symfonyRouteTagValueNodeFactory->createFromItems($values);
        $phpDocInfo->addTagValueNode($symfonyRouteTagValueNode);
        return $node;
    }
}
