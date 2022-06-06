<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php80\Rector\Class_;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\AttributeGroup;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrowFunction;
use RectorPrefix20220606\PhpParser\Node\Expr\Closure;
use RectorPrefix20220606\PhpParser\Node\Param;
use RectorPrefix20220606\PhpParser\Node\Stmt\Class_;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\PhpParser\Node\Stmt\Function_;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\Node as DocNode;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use RectorPrefix20220606\Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use RectorPrefix20220606\Rector\Core\Php\PhpVersionProvider;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\Naming\Naming\UseImportsResolver;
use RectorPrefix20220606\Rector\Php80\NodeFactory\AttrGroupsFactory;
use RectorPrefix20220606\Rector\Php80\NodeManipulator\AttributeGroupNamedArgumentManipulator;
use RectorPrefix20220606\Rector\Php80\PhpDoc\PhpDocNodeFinder;
use RectorPrefix20220606\Rector\Php80\ValueObject\AnnotationToAttribute;
use RectorPrefix20220606\Rector\Php80\ValueObject\DoctrineTagAndAnnotationToAttribute;
use RectorPrefix20220606\Rector\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use RectorPrefix20220606\Rector\PhpAttribute\RemovableAnnotationAnalyzer;
use RectorPrefix20220606\Rector\PhpAttribute\UnwrapableAnnotationAnalyzer;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\Astral\PhpDocParser\PhpDocNodeTraverser;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use RectorPrefix20220606\Webmozart\Assert\Assert;
/**
 * @changelog https://wiki.php.net/rfc/attributes_v2
 *
 * @see \Rector\Tests\Php80\Rector\Class_\AnnotationToAttributeRector\AnnotationToAttributeRectorTest
 */
final class AnnotationToAttributeRector extends AbstractRector implements ConfigurableRectorInterface, MinPhpVersionInterface
{
    /**
     * @var AnnotationToAttribute[]
     */
    private $annotationsToAttributes = [];
    /**
     * @readonly
     * @var \Rector\PhpAttribute\NodeFactory\PhpAttributeGroupFactory
     */
    private $phpAttributeGroupFactory;
    /**
     * @readonly
     * @var \Rector\Php80\NodeFactory\AttrGroupsFactory
     */
    private $attrGroupsFactory;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover
     */
    private $phpDocTagRemover;
    /**
     * @readonly
     * @var \Rector\Php80\PhpDoc\PhpDocNodeFinder
     */
    private $phpDocNodeFinder;
    /**
     * @readonly
     * @var \Rector\PhpAttribute\UnwrapableAnnotationAnalyzer
     */
    private $unwrapableAnnotationAnalyzer;
    /**
     * @readonly
     * @var \Rector\PhpAttribute\RemovableAnnotationAnalyzer
     */
    private $removableAnnotationAnalyzer;
    /**
     * @readonly
     * @var \Rector\Php80\NodeManipulator\AttributeGroupNamedArgumentManipulator
     */
    private $attributeGroupNamedArgumentManipulator;
    /**
     * @readonly
     * @var \Rector\Core\Php\PhpVersionProvider
     */
    private $phpVersionProvider;
    /**
     * @readonly
     * @var \Rector\Naming\Naming\UseImportsResolver
     */
    private $useImportsResolver;
    public function __construct(PhpAttributeGroupFactory $phpAttributeGroupFactory, AttrGroupsFactory $attrGroupsFactory, PhpDocTagRemover $phpDocTagRemover, PhpDocNodeFinder $phpDocNodeFinder, UnwrapableAnnotationAnalyzer $unwrapableAnnotationAnalyzer, RemovableAnnotationAnalyzer $removableAnnotationAnalyzer, AttributeGroupNamedArgumentManipulator $attributeGroupNamedArgumentManipulator, PhpVersionProvider $phpVersionProvider, UseImportsResolver $useImportsResolver)
    {
        $this->phpAttributeGroupFactory = $phpAttributeGroupFactory;
        $this->attrGroupsFactory = $attrGroupsFactory;
        $this->phpDocTagRemover = $phpDocTagRemover;
        $this->phpDocNodeFinder = $phpDocNodeFinder;
        $this->unwrapableAnnotationAnalyzer = $unwrapableAnnotationAnalyzer;
        $this->removableAnnotationAnalyzer = $removableAnnotationAnalyzer;
        $this->attributeGroupNamedArgumentManipulator = $attributeGroupNamedArgumentManipulator;
        $this->phpVersionProvider = $phpVersionProvider;
        $this->useImportsResolver = $useImportsResolver;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Change annotation to attribute', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\Routing\Annotation\Route;

class SymfonyRoute
{
    /**
     * @Route("/path", name="action")
     */
    public function action()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Routing\Annotation\Route;

class SymfonyRoute
{
    #[Route(path: '/path', name: 'action')]
    public function action()
    {
    }
}
CODE_SAMPLE
, [new AnnotationToAttribute('Symfony\\Component\\Routing\\Annotation\\Route')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Class_::class, Property::class, Param::class, ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }
    /**
     * @param Class_|Property|Param|ClassMethod|Function_|Closure|ArrowFunction $node
     */
    public function refactor(Node $node) : ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $uses = $this->useImportsResolver->resolveBareUsesForNode($node);
        // 1. generic tags
        $genericAttributeGroups = $this->processGenericTags($phpDocInfo);
        // 2. Doctrine annotation classes
        $annotationAttributeGroups = $this->processDoctrineAnnotationClasses($phpDocInfo, $uses);
        $attributeGroups = \array_merge($genericAttributeGroups, $annotationAttributeGroups);
        if ($attributeGroups === []) {
            return null;
        }
        $attributeGroups = $this->attributeGroupNamedArgumentManipulator->processSpecialClassTypes($attributeGroups);
        $node->attrGroups = \array_merge($node->attrGroups, $attributeGroups);
        return $node;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration) : void
    {
        Assert::allIsAOf($configuration, AnnotationToAttribute::class);
        $this->annotationsToAttributes = $configuration;
        $this->unwrapableAnnotationAnalyzer->configure($configuration);
        $this->removableAnnotationAnalyzer->configure($configuration);
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }
    /**
     * @return AttributeGroup[]
     */
    private function processGenericTags(PhpDocInfo $phpDocInfo) : array
    {
        $attributeGroups = [];
        $phpDocNodeTraverser = new PhpDocNodeTraverser();
        $phpDocNodeTraverser->traverseWithCallable($phpDocInfo->getPhpDocNode(), '', function (DocNode $docNode) use(&$attributeGroups, $phpDocInfo) : ?int {
            if (!$docNode instanceof PhpDocTagNode) {
                return null;
            }
            if (!$docNode->value instanceof GenericTagValueNode) {
                return null;
            }
            $tag = \trim($docNode->name, '@');
            // not a basic one
            if (\strpos($tag, '\\') !== \false) {
                return null;
            }
            foreach ($this->annotationsToAttributes as $annotationToAttribute) {
                $desiredTag = $annotationToAttribute->getTag();
                if ($desiredTag !== $tag) {
                    continue;
                }
                $attributeGroups[] = $this->phpAttributeGroupFactory->createFromSimpleTag($annotationToAttribute);
                $phpDocInfo->markAsChanged();
                return PhpDocNodeTraverser::NODE_REMOVE;
            }
            return null;
        });
        return $attributeGroups;
    }
    /**
     * @param Node\Stmt\Use_[] $uses
     * @return AttributeGroup[]
     */
    private function processDoctrineAnnotationClasses(PhpDocInfo $phpDocInfo, array $uses) : array
    {
        if ($phpDocInfo->getPhpDocNode()->children === []) {
            return [];
        }
        $doctrineTagAndAnnotationToAttributes = [];
        foreach ($phpDocInfo->getPhpDocNode()->children as $phpDocChildNode) {
            if (!$phpDocChildNode instanceof PhpDocTagNode) {
                continue;
            }
            if (!$phpDocChildNode->value instanceof DoctrineAnnotationTagValueNode) {
                continue;
            }
            $doctrineTagValueNode = $phpDocChildNode->value;
            $annotationToAttribute = $this->matchAnnotationToAttribute($doctrineTagValueNode);
            if (!$annotationToAttribute instanceof AnnotationToAttribute) {
                continue;
            }
            $nestedDoctrineAnnotationTagValueNodes = $this->phpDocNodeFinder->findByType($doctrineTagValueNode, DoctrineAnnotationTagValueNode::class);
            $shouldInlinedNested = \false;
            // depends on PHP 8.1+ - nested values, skip for now
            if ($nestedDoctrineAnnotationTagValueNodes !== [] && !$this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::NEW_INITIALIZERS)) {
                if (!$this->unwrapableAnnotationAnalyzer->areUnwrappable($nestedDoctrineAnnotationTagValueNodes)) {
                    continue;
                }
                $shouldInlinedNested = \true;
            }
            if (!$this->removableAnnotationAnalyzer->isRemovable($doctrineTagValueNode)) {
                $doctrineTagAndAnnotationToAttributes[] = new DoctrineTagAndAnnotationToAttribute($doctrineTagValueNode, $annotationToAttribute);
            } else {
                $shouldInlinedNested = \true;
            }
            if ($shouldInlinedNested) {
                // inline nested
                foreach ($nestedDoctrineAnnotationTagValueNodes as $nestedDoctrineAnnotationTagValueNode) {
                    $doctrineTagAndAnnotationToAttributes[] = new DoctrineTagAndAnnotationToAttribute($nestedDoctrineAnnotationTagValueNode, $annotationToAttribute);
                }
            }
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $doctrineTagValueNode);
        }
        return $this->attrGroupsFactory->create($doctrineTagAndAnnotationToAttributes, $uses);
    }
    /**
     * @return \Rector\Php80\ValueObject\AnnotationToAttribute|null
     */
    private function matchAnnotationToAttribute(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode)
    {
        foreach ($this->annotationsToAttributes as $annotationToAttribute) {
            if (!$doctrineAnnotationTagValueNode->hasClassName($annotationToAttribute->getTag())) {
                continue;
            }
            return $annotationToAttribute;
        }
        return null;
    }
}
