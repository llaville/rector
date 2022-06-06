<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\StaticTypeMapper\Mapper;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr;
use RectorPrefix20220606\PhpParser\Node\Name;
use RectorPrefix20220606\PhpParser\Node\Name\FullyQualified;
use RectorPrefix20220606\PhpParser\Node\Scalar\String_;
use RectorPrefix20220606\PHPStan\Type\Type;
use RectorPrefix20220606\Rector\Core\Exception\NotImplementedYetException;
use RectorPrefix20220606\Rector\NodeTypeResolver\Node\AttributeKey;
use RectorPrefix20220606\Rector\StaticTypeMapper\Contract\PhpParser\PhpParserNodeMapperInterface;
final class PhpParserNodeMapper
{
    /**
     * @var PhpParserNodeMapperInterface[]
     * @readonly
     */
    private $phpParserNodeMappers;
    /**
     * @param PhpParserNodeMapperInterface[] $phpParserNodeMappers
     */
    public function __construct(array $phpParserNodeMappers)
    {
        $this->phpParserNodeMappers = $phpParserNodeMappers;
    }
    public function mapToPHPStanType(Node $node) : Type
    {
        if (\get_class($node) === Name::class && $node->hasAttribute(AttributeKey::NAMESPACED_NAME)) {
            $node = new FullyQualified($node->getAttribute(AttributeKey::NAMESPACED_NAME));
        }
        foreach ($this->phpParserNodeMappers as $phpParserNodeMapper) {
            if (!\is_a($node, $phpParserNodeMapper->getNodeType())) {
                continue;
            }
            // do not let Expr collect all the types
            // note: can be solve later with priorities on mapper interface, making this last
            if ($phpParserNodeMapper->getNodeType() !== Expr::class) {
                return $phpParserNodeMapper->mapToPHPStan($node);
            }
            if (!$node instanceof String_) {
                return $phpParserNodeMapper->mapToPHPStan($node);
            }
        }
        throw new NotImplementedYetException(\get_class($node));
    }
}
