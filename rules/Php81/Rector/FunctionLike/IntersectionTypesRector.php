<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php81\Rector\FunctionLike;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrowFunction;
use RectorPrefix20220606\PhpParser\Node\Expr\Closure;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\PhpParser\Node\Stmt\Function_;
use RectorPrefix20220606\PHPStan\Type\IntersectionType;
use RectorPrefix20220606\PHPStan\Type\TypeWithClassName;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\Php81\Rector\FunctionLike\IntersectionTypesRector\IntersectionTypesRectorTest
 */
final class IntersectionTypesRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @var bool
     */
    private $hasChanged = \false;
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Change docs to intersection types, where possible (properties are covered by TypedPropertyRector (@todo))', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @param Foo&Bar $types
     */
    public function process($types)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function process(Foo&Bar $types)
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
        return [ArrowFunction::class, Closure::class, ClassMethod::class, Function_::class];
    }
    /**
     * @param ArrowFunction|Closure|ClassMethod|Function_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        $this->hasChanged = \false;
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $this->refactorParamTypes($node, $phpDocInfo);
        // $this->refactorReturnType($node, $phpDocInfo);
        if ($this->hasChanged) {
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::INTERSECTION_TYPES;
    }
    /**
     * @param \PhpParser\Node\Expr\ArrowFunction|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $functionLike
     */
    private function refactorParamTypes($functionLike, PhpDocInfo $phpDocInfo) : void
    {
        foreach ($functionLike->params as $param) {
            if ($param->type !== null) {
                continue;
            }
            /** @var string $paramName */
            $paramName = $this->getName($param->var);
            $paramType = $phpDocInfo->getParamType($paramName);
            if (!$paramType instanceof IntersectionType) {
                continue;
            }
            if (!$this->isIntersectionableType($paramType)) {
                continue;
            }
            $phpParserIntersectionType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($paramType, TypeKind::PARAM);
            if (!$phpParserIntersectionType instanceof Node\IntersectionType) {
                continue;
            }
            $param->type = $phpParserIntersectionType;
            $this->hasChanged = \true;
        }
    }
    /**
     * Only class-type are supported https://wiki.php.net/rfc/pure-intersection-types#supported_types
     */
    private function isIntersectionableType(IntersectionType $intersectionType) : bool
    {
        foreach ($intersectionType->getTypes() as $intersectionedType) {
            if ($intersectionedType instanceof TypeWithClassName) {
                continue;
            }
            return \false;
        }
        return \true;
    }
}
