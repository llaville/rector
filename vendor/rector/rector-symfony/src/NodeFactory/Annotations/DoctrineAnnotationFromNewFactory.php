<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Symfony\NodeFactory\Annotations;

use RectorPrefix20220606\PhpParser\Node\Expr\New_;
use RectorPrefix20220606\PhpParser\Node\Name;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use RectorPrefix20220606\Rector\BetterPhpDocParser\ValueObject\Type\ShortenedIdentifierTypeNode;
use RectorPrefix20220606\Rector\Core\Exception\ShouldNotHappenException;
use RectorPrefix20220606\Rector\NodeTypeResolver\Node\AttributeKey;
final class DoctrineAnnotationFromNewFactory
{
    /**
     * @readonly
     * @var \Rector\Symfony\NodeFactory\Annotations\DoctrineAnnotationKeyToValuesResolver
     */
    private $doctrineAnnotationKeyToValuesResolver;
    public function __construct(DoctrineAnnotationKeyToValuesResolver $doctrineAnnotationKeyToValuesResolver)
    {
        $this->doctrineAnnotationKeyToValuesResolver = $doctrineAnnotationKeyToValuesResolver;
    }
    public function create(New_ $new) : DoctrineAnnotationTagValueNode
    {
        $annotationName = $this->resolveAnnotationName($new);
        $newArgs = $new->getArgs();
        if (isset($newArgs[0])) {
            $firstAnnotationArg = $newArgs[0]->value;
            $annotationKeyToValues = $this->doctrineAnnotationKeyToValuesResolver->resolveFromExpr($firstAnnotationArg);
        } else {
            $annotationKeyToValues = [];
        }
        return new DoctrineAnnotationTagValueNode(new ShortenedIdentifierTypeNode($annotationName), null, $annotationKeyToValues);
    }
    private function resolveAnnotationName(New_ $new) : string
    {
        $className = $new->class;
        $originalName = $className->getAttribute(AttributeKey::ORIGINAL_NAME);
        if (!$originalName instanceof Name) {
            throw new ShouldNotHappenException();
        }
        return $originalName->toString();
    }
}
