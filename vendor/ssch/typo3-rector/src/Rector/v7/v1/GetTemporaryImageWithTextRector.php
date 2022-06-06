<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Ssch\TYPO3Rector\Rector\v7\v1;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\MethodCall;
use RectorPrefix20220606\PHPStan\Type\ObjectType;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/7.1/Deprecation-46770-LocalImageProcessorGraphicalFunctions.html
 * @see \Ssch\TYPO3Rector\Tests\Rector\v7\v1\GetTemporaryImageWithTextRector\GetTemporaryImageWithTextRectorTest
 */
final class GetTemporaryImageWithTextRector extends AbstractRector
{
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
        if (!$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, new ObjectType('TYPO3\\CMS\\Core\\Resource\\Processing\\LocalImageProcessor'))) {
            return null;
        }
        if (!$this->isName($node->name, 'getTemporaryImageWithText')) {
            return null;
        }
        return $this->nodeFactory->createMethodCall($this->nodeFactory->createStaticCall('TYPO3\\CMS\\Core\\Utility\\GeneralUtility', 'makeInstance', [$this->nodeFactory->createClassConstReference('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions')]), 'getTemporaryImageWithText', $node->args);
    }
    /**
     * @codeCoverageIgnore
     */
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Use GraphicalFunctions->getTemporaryImageWithText instead of LocalImageProcessor->getTemporaryImageWithText', [new CodeSample('GeneralUtility::makeInstance(LocalImageProcessor::class)->getTemporaryImageWithText("foo", "bar", "baz", "foo")', 'GeneralUtility::makeInstance(GraphicalFunctions::class)->getTemporaryImageWithText("foo", "bar", "baz", "foo")')]);
    }
}
