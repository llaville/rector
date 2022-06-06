<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Ssch\TYPO3Rector\Rector\v9\v0;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use RectorPrefix20220606\Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Ssch\TYPO3Rector\NodeFactory\ImportExtbaseAnnotationIfMissingFactory;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/9.0/Feature-83092-ReplaceTransientWithTYPO3CMSExtbaseAnnotationORMTransient.html
 * @see \Ssch\TYPO3Rector\Tests\Rector\v9\v0\ReplaceAnnotationRector\ReplaceAnnotationRectorTest
 */
final class ReplaceAnnotationRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @api
     * @var string
     */
    public const OLD_TO_NEW_ANNOTATIONS = 'old_to_new_annotations';
    /**
     * @var array<string, string>
     */
    private $oldToNewAnnotations = [];
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover
     */
    private $phpDocTagRemover;
    /**
     * @readonly
     * @var \Ssch\TYPO3Rector\NodeFactory\ImportExtbaseAnnotationIfMissingFactory
     */
    private $importExtbaseAnnotationIfMissingFactory;
    public function __construct(PhpDocTagRemover $phpDocTagRemover, ImportExtbaseAnnotationIfMissingFactory $importExtbaseAnnotationIfMissingFactory)
    {
        $this->phpDocTagRemover = $phpDocTagRemover;
        $this->importExtbaseAnnotationIfMissingFactory = $importExtbaseAnnotationIfMissingFactory;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Property::class, ClassMethod::class];
    }
    /**
     * @param Property|ClassMethod $node
     */
    public function refactor(Node $node) : ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $annotationChanged = \false;
        foreach ($this->oldToNewAnnotations as $oldAnnotation => $newAnnotation) {
            if (!$phpDocInfo->hasByName($oldAnnotation)) {
                continue;
            }
            $this->phpDocTagRemover->removeByName($phpDocInfo, $oldAnnotation);
            $tag = $this->prepareNewAnnotation($newAnnotation);
            $phpDocTagNode = new PhpDocTagNode($tag, new GenericTagValueNode(''));
            $phpDocInfo->addPhpDocTagNode($phpDocTagNode);
            $annotationChanged = \true;
        }
        if (!$annotationChanged) {
            return null;
        }
        $this->importExtbaseAnnotationIfMissingFactory->addExtbaseAliasAnnotationIfMissing($node);
        return $node;
    }
    /**
     * @codeCoverageIgnore
     */
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Replace old annotation by new one', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
/**
 * @transient
 */
private $someProperty;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use TYPO3\CMS\Extbase\Annotation as Extbase;
/**
 * @Extbase\ORM\Transient
 */
private $someProperty;

CODE_SAMPLE
, [self::OLD_TO_NEW_ANNOTATIONS => ['transient' => 'TYPO3\\CMS\\Extbase\\Annotation\\ORM\\Transient']])]);
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration) : void
    {
        $this->oldToNewAnnotations = $configuration[self::OLD_TO_NEW_ANNOTATIONS] ?? $configuration;
    }
    private function prepareNewAnnotation(string $newAnnotation) : string
    {
        $newAnnotation = '@' . \ltrim($newAnnotation, '@');
        if (\strncmp($newAnnotation, '@TYPO3\\CMS\\Extbase\\Annotation', \strlen('@TYPO3\\CMS\\Extbase\\Annotation')) === 0) {
            $newAnnotation = \str_replace('TYPO3\\CMS\\Extbase\\Annotation', 'Extbase', $newAnnotation);
        }
        return '@' . \ltrim($newAnnotation, '@');
    }
}
