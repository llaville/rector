<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\NodeTypeResolver\TypeAnalyzer;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Array_;
use RectorPrefix20220606\PhpParser\Node\Expr\PropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Expr\StaticPropertyFetch;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassLike;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use RectorPrefix20220606\PHPStan\Reflection\Php\PhpPropertyReflection;
use RectorPrefix20220606\PHPStan\Type\Accessory\HasOffsetType;
use RectorPrefix20220606\PHPStan\Type\Accessory\NonEmptyArrayType;
use RectorPrefix20220606\PHPStan\Type\ArrayType;
use RectorPrefix20220606\PHPStan\Type\IntersectionType;
use RectorPrefix20220606\PHPStan\Type\IterableType;
use RectorPrefix20220606\PHPStan\Type\MixedType;
use RectorPrefix20220606\PHPStan\Type\Type;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\BetterNodeFinder;
use RectorPrefix20220606\Rector\Core\Reflection\ReflectionResolver;
use RectorPrefix20220606\Rector\NodeNameResolver\NodeNameResolver;
use RectorPrefix20220606\Rector\NodeTypeResolver\NodeTypeCorrector\PregMatchTypeCorrector;
use RectorPrefix20220606\Rector\NodeTypeResolver\NodeTypeResolver;
final class ArrayTypeAnalyzer
{
    /**
     * @readonly
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @readonly
     * @var \Rector\NodeTypeResolver\NodeTypeResolver
     */
    private $nodeTypeResolver;
    /**
     * @readonly
     * @var \Rector\NodeTypeResolver\NodeTypeCorrector\PregMatchTypeCorrector
     */
    private $pregMatchTypeCorrector;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\BetterNodeFinder
     */
    private $betterNodeFinder;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory
     */
    private $phpDocInfoFactory;
    /**
     * @readonly
     * @var \Rector\Core\Reflection\ReflectionResolver
     */
    private $reflectionResolver;
    public function __construct(NodeNameResolver $nodeNameResolver, NodeTypeResolver $nodeTypeResolver, PregMatchTypeCorrector $pregMatchTypeCorrector, BetterNodeFinder $betterNodeFinder, PhpDocInfoFactory $phpDocInfoFactory, ReflectionResolver $reflectionResolver)
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->pregMatchTypeCorrector = $pregMatchTypeCorrector;
        $this->betterNodeFinder = $betterNodeFinder;
        $this->phpDocInfoFactory = $phpDocInfoFactory;
        $this->reflectionResolver = $reflectionResolver;
    }
    public function isArrayType(Node $node) : bool
    {
        $nodeType = $this->nodeTypeResolver->getType($node);
        $nodeType = $this->pregMatchTypeCorrector->correct($node, $nodeType);
        if ($this->isIntersectionArrayType($nodeType)) {
            return \true;
        }
        // PHPStan false positive, when variable has type[] docblock, but default array is missing
        if ($node instanceof PropertyFetch || $node instanceof StaticPropertyFetch) {
            if ($this->isPropertyFetchWithArrayDefault($node)) {
                return \true;
            }
            if ($this->isPropertyFetchWithArrayDocblockWithoutDefault($node)) {
                return \false;
            }
        }
        if ($nodeType instanceof MixedType) {
            if ($nodeType->isExplicitMixed()) {
                return \false;
            }
            if ($this->isPropertyFetchWithArrayDefault($node)) {
                return \true;
            }
        }
        return $nodeType instanceof ArrayType;
    }
    private function isIntersectionArrayType(Type $nodeType) : bool
    {
        if (!$nodeType instanceof IntersectionType) {
            return \false;
        }
        foreach ($nodeType->getTypes() as $intersectionNodeType) {
            if ($intersectionNodeType instanceof ArrayType) {
                continue;
            }
            if ($intersectionNodeType instanceof HasOffsetType) {
                continue;
            }
            if ($intersectionNodeType instanceof NonEmptyArrayType) {
                continue;
            }
            return \false;
        }
        return \true;
    }
    private function isPropertyFetchWithArrayDocblockWithoutDefault(Node $node) : bool
    {
        if (!$node instanceof PropertyFetch && !$node instanceof StaticPropertyFetch) {
            return \false;
        }
        $classLike = $this->betterNodeFinder->findParentType($node, ClassLike::class);
        if (!$classLike instanceof ClassLike) {
            return \false;
        }
        $propertyName = $this->nodeNameResolver->getName($node->name);
        if ($propertyName === null) {
            return \false;
        }
        $property = $classLike->getProperty($propertyName);
        if (!$property instanceof Property) {
            return \false;
        }
        $propertyProperty = $property->props[0];
        if ($propertyProperty->default instanceof Array_) {
            return \false;
        }
        $propertyPhpDocInfo = $this->phpDocInfoFactory->createFromNode($property);
        if (!$propertyPhpDocInfo instanceof PhpDocInfo) {
            return \false;
        }
        $varType = $propertyPhpDocInfo->getVarType();
        return $varType instanceof ArrayType || $varType instanceof ArrayShapeNode || $varType instanceof IterableType;
    }
    /**
     * phpstan bug workaround - https://phpstan.org/r/0443f283-244c-42b8-8373-85e7deb3504c
     */
    private function isPropertyFetchWithArrayDefault(Node $node) : bool
    {
        if (!$node instanceof PropertyFetch && !$node instanceof StaticPropertyFetch) {
            return \false;
        }
        $classLike = $this->betterNodeFinder->findParentType($node, ClassLike::class);
        if (!$classLike instanceof ClassLike) {
            return \false;
        }
        $propertyName = $this->nodeNameResolver->getName($node->name);
        if ($propertyName === null) {
            return \false;
        }
        // A. local property
        $property = $classLike->getProperty($propertyName);
        if ($property !== null) {
            $propertyProperty = $property->props[0];
            return $propertyProperty->default instanceof Array_;
        }
        // B. another object property
        $phpPropertyReflection = $this->reflectionResolver->resolvePropertyReflectionFromPropertyFetch($node);
        if ($phpPropertyReflection instanceof PhpPropertyReflection) {
            $reflectionProperty = $phpPropertyReflection->getNativeReflection();
            return \is_array($reflectionProperty->getDefaultValue());
        }
        return \false;
    }
}
