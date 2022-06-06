<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\CodingStyle\ClassNameImport\ClassNameImportSkipVoter;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\Rector\CodingStyle\Contract\ClassNameImport\ClassNameImportSkipVoterInterface;
use RectorPrefix20220606\Rector\Core\Configuration\RenamedClassesDataCollector;
use RectorPrefix20220606\Rector\Core\ValueObject\Application\File;
use RectorPrefix20220606\Rector\PostRector\Collector\UseNodesToAddCollector;
use RectorPrefix20220606\Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
/**
 * This prevents importing:
 * - App\Some\Product
 *
 * if there is already:
 * - use App\Another\Product
 */
final class UsesClassNameImportSkipVoter implements ClassNameImportSkipVoterInterface
{
    /**
     * @readonly
     * @var \Rector\PostRector\Collector\UseNodesToAddCollector
     */
    private $useNodesToAddCollector;
    /**
     * @readonly
     * @var \Rector\Core\Configuration\RenamedClassesDataCollector
     */
    private $renamedClassesDataCollector;
    public function __construct(UseNodesToAddCollector $useNodesToAddCollector, RenamedClassesDataCollector $renamedClassesDataCollector)
    {
        $this->useNodesToAddCollector = $useNodesToAddCollector;
        $this->renamedClassesDataCollector = $renamedClassesDataCollector;
    }
    public function shouldSkip(File $file, FullyQualifiedObjectType $fullyQualifiedObjectType, Node $node) : bool
    {
        $useImportTypes = $this->useNodesToAddCollector->getUseImportTypesByNode($file, $node);
        foreach ($useImportTypes as $useImportType) {
            // if the class is renamed, the use import is no longer blocker
            if ($this->renamedClassesDataCollector->hasOldClass($useImportType->getClassName())) {
                continue;
            }
            if (!$useImportType->equals($fullyQualifiedObjectType) && $useImportType->areShortNamesEqual($fullyQualifiedObjectType)) {
                return \true;
            }
            if ($useImportType->equals($fullyQualifiedObjectType)) {
                return \false;
            }
        }
        return \false;
    }
}
