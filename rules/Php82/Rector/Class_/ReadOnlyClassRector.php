<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php82\Rector\Class_;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Param;
use RectorPrefix20220606\PhpParser\Node\Stmt\Class_;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\Rector\Core\NodeAnalyzer\ClassAnalyzer;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\MethodName;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\Core\ValueObject\Visibility;
use RectorPrefix20220606\Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use RectorPrefix20220606\Rector\Privatization\NodeManipulator\VisibilityManipulator;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/readonly_classes
 *
 * @see \Rector\Tests\Php82\Rector\Class_\ReadOnlyClassRector\ReadOnlyClassRectorTest
 */
final class ReadOnlyClassRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @var string
     */
    private const ATTRIBUTE = 'AllowDynamicProperties';
    /**
     * @readonly
     * @var \Rector\Core\NodeAnalyzer\ClassAnalyzer
     */
    private $classAnalyzer;
    /**
     * @readonly
     * @var \Rector\Privatization\NodeManipulator\VisibilityManipulator
     */
    private $visibilityManipulator;
    /**
     * @readonly
     * @var \Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer
     */
    private $phpAttributeAnalyzer;
    public function __construct(ClassAnalyzer $classAnalyzer, VisibilityManipulator $visibilityManipulator, PhpAttributeAnalyzer $phpAttributeAnalyzer)
    {
        $this->classAnalyzer = $classAnalyzer;
        $this->visibilityManipulator = $visibilityManipulator;
        $this->phpAttributeAnalyzer = $phpAttributeAnalyzer;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Decorate read-only class with `readonly` attribute', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function __construct(
        private readonly string $name
    ) {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final readonly class SomeClass
{
    public function __construct(
        private string $name
    ) {
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
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        $this->visibilityManipulator->makeReadonly($node);
        $constructClassMethod = $node->getMethod(MethodName::CONSTRUCT);
        if ($constructClassMethod instanceof ClassMethod) {
            foreach ($constructClassMethod->getParams() as $param) {
                $this->visibilityManipulator->removeReadonly($param);
            }
        }
        foreach ($node->getProperties() as $property) {
            $this->visibilityManipulator->removeReadonly($property);
        }
        return $node;
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::READONLY_CLASS;
    }
    private function shouldSkip(Class_ $class) : bool
    {
        if ($this->shouldSkipClass($class)) {
            return \true;
        }
        $properties = $class->getProperties();
        if ($this->hasWritableProperty($properties)) {
            return \true;
        }
        foreach ($properties as $property) {
            // properties of readonly class must always have type
            if ($property->type === null) {
                return \true;
            }
        }
        $constructClassMethod = $class->getMethod(MethodName::CONSTRUCT);
        if (!$constructClassMethod instanceof ClassMethod) {
            // no __construct means no property promotion, skip if class has no property defined
            return $properties === [];
        }
        $params = $constructClassMethod->getParams();
        if ($params === []) {
            // no params means no property promotion, skip if class has no property defined
            return $properties === [];
        }
        return $this->shouldSkipParams($params);
    }
    /**
     * @param Property[] $properties
     */
    private function hasWritableProperty(array $properties) : bool
    {
        foreach ($properties as $property) {
            if (!$property->isReadonly()) {
                return \true;
            }
        }
        return \false;
    }
    private function shouldSkipClass(Class_ $class) : bool
    {
        // need to have test fixture once feature added to  nikic/PHP-Parser
        if ($this->visibilityManipulator->hasVisibility($class, Visibility::READONLY)) {
            return \true;
        }
        if ($this->classAnalyzer->isAnonymousClass($class)) {
            return \true;
        }
        if (!$class->isFinal()) {
            return \true;
        }
        return $this->phpAttributeAnalyzer->hasPhpAttribute($class, self::ATTRIBUTE);
    }
    /**
     * @param Param[] $params
     */
    private function shouldSkipParams(array $params) : bool
    {
        foreach ($params as $param) {
            // has non-property promotion, skip
            if (!$this->visibilityManipulator->hasVisibility($param, Visibility::READONLY)) {
                return \true;
            }
            // type is missing, invalid syntax
            if ($param->type === null) {
                return \true;
            }
        }
        return \false;
    }
}
