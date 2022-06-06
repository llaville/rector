<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Symfony\NodeFactory\Annotations;

use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Expr\Array_;
use RectorPrefix20220606\PhpParser\Node\Expr\ArrayItem;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\Value\ValueResolver;
final class DoctrineAnnotationKeyToValuesResolver
{
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\Value\ValueResolver
     */
    private $valueResolver;
    public function __construct(ValueResolver $valueResolver)
    {
        $this->valueResolver = $valueResolver;
    }
    /**
     * @return array<string|null, mixed>
     */
    public function resolveFromExpr(Expr $expr) : array
    {
        $annotationKeyToValues = [];
        if ($expr instanceof Array_) {
            foreach ($expr->items as $arrayItem) {
                if (!$arrayItem instanceof ArrayItem) {
                    continue;
                }
                $key = $this->resolveKey($arrayItem);
                $value = $this->valueResolver->getValue($arrayItem->value);
                if (\is_string($value)) {
                    $value = '"' . $value . '"';
                }
                $annotationKeyToValues[$key] = $value;
            }
        }
        return $annotationKeyToValues;
    }
    private function resolveKey(ArrayItem $arrayItem) : ?string
    {
        if (!$arrayItem->key instanceof Expr) {
            return null;
        }
        return $this->valueResolver->getValue($arrayItem->key);
    }
}
