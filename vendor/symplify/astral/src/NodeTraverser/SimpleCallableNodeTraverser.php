<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Symplify\Astral\NodeTraverser;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\NodeTraverser;
use RectorPrefix20220606\Symplify\Astral\NodeVisitor\CallableNodeVisitor;
/**
 * @api
 */
final class SimpleCallableNodeTraverser
{
    /**
     * @param callable(Node $node): (int|Node|null) $callable
     * @param \PhpParser\Node|mixed[]|null $nodes
     */
    public function traverseNodesWithCallable($nodes, callable $callable) : void
    {
        if ($nodes === null) {
            return;
        }
        if ($nodes === []) {
            return;
        }
        if (!\is_array($nodes)) {
            $nodes = [$nodes];
        }
        $nodeTraverser = new NodeTraverser();
        $callableNodeVisitor = new CallableNodeVisitor($callable);
        $nodeTraverser->addVisitor($callableNodeVisitor);
        $nodeTraverser->traverse($nodes);
    }
}
