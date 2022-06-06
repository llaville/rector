<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Naming;

use RectorPrefix20220606\Rector\Renaming\ValueObject\RenamedNamespace;
final class NamespaceMatcher
{
    /**
     * @param string[] $oldToNewNamespace
     */
    public function matchRenamedNamespace(string $name, array $oldToNewNamespace) : ?RenamedNamespace
    {
        \krsort($oldToNewNamespace);
        /** @var string $oldNamespace */
        foreach ($oldToNewNamespace as $oldNamespace => $newNamespace) {
            if (\strncmp($name, $oldNamespace, \strlen($oldNamespace)) === 0) {
                return new RenamedNamespace($name, $oldNamespace, $newNamespace);
            }
        }
        return null;
    }
}
