<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Doctrine\Rector\Class_;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Stmt\Class_;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Doctrine\Tests\Rector\Class_\RemoveRepositoryFromEntityAnnotationRector\RemoveRepositoryFromEntityAnnotationRectorTest
 */
final class RemoveRepositoryFromEntityAnnotationRector extends AbstractRector
{
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Removes repository class from @Entity annotation', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ProductRepository")
 */
class Product
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Product
{
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $doctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClass('Doctrine\\ORM\\Mapping\\Entity');
        if (!$doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return null;
        }
        $repositoryClass = $doctrineAnnotationTagValueNode->getValue('repositoryClass');
        if (!\is_string($repositoryClass)) {
            return null;
        }
        $doctrineAnnotationTagValueNode->removeValue('repositoryClass');
        $phpDocInfo->markAsChanged();
        return $node;
    }
}
