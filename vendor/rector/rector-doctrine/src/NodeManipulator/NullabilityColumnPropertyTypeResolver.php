<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Doctrine\NodeManipulator;

use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\Value\ValueResolver;
use RectorPrefix20220606\Rector\Doctrine\NodeAnalyzer\AttributeFinder;
final class NullabilityColumnPropertyTypeResolver
{
    /**
     * @var string
     */
    private const COLUMN_CLASS = 'Doctrine\\ORM\\Mapping\\Column';
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory
     */
    private $phpDocInfoFactory;
    /**
     * @readonly
     * @var \Rector\Doctrine\NodeAnalyzer\AttributeFinder
     */
    private $attributeFinder;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\Value\ValueResolver
     */
    private $valueResolver;
    /**
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/basic-mapping.html#doctrine-mapping-types
     */
    public function __construct(PhpDocInfoFactory $phpDocInfoFactory, AttributeFinder $attributeFinder, ValueResolver $valueResolver)
    {
        $this->phpDocInfoFactory = $phpDocInfoFactory;
        $this->attributeFinder = $attributeFinder;
        $this->valueResolver = $valueResolver;
    }
    public function isNullable(Property $property) : bool
    {
        $nullableExpr = $this->attributeFinder->findAttributeByClassArgByName($property, self::COLUMN_CLASS, 'nullable');
        if ($nullableExpr instanceof Expr) {
            return $this->valueResolver->isTrue($nullableExpr);
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        return $this->isNullableColumn($phpDocInfo);
    }
    private function isNullableColumn(PhpDocInfo $phpDocInfo) : bool
    {
        $doctrineAnnotationTagValueNode = $phpDocInfo->findOneByAnnotationClass(self::COLUMN_CLASS);
        if (!$doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return \true;
        }
        $nullableValue = $doctrineAnnotationTagValueNode->getValue('nullable');
        return $nullableValue === null || $nullableValue instanceof ConstExprTrueNode;
    }
}
