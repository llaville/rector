<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Core\PhpParser\Printer;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Stmt;
use RectorPrefix20220606\PhpParser\Node\Stmt\Namespace_;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\CustomNode\FileWithoutNamespace;
use RectorPrefix20220606\Rector\Core\ValueObject\Application\File;
use Symplify\SmartFileSystem\SmartFileInfo;
use RectorPrefix20220606\Symplify\SmartFileSystem\SmartFileSystem;
/**
 * @see \Rector\Core\Tests\PhpParser\Printer\FormatPerservingPrinterTest
 */
final class FormatPerservingPrinter
{
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Printer\BetterStandardPrinter
     */
    private $betterStandardPrinter;
    /**
     * @readonly
     * @var \Symplify\SmartFileSystem\SmartFileSystem
     */
    private $smartFileSystem;
    public function __construct(BetterStandardPrinter $betterStandardPrinter, SmartFileSystem $smartFileSystem)
    {
        $this->betterStandardPrinter = $betterStandardPrinter;
        $this->smartFileSystem = $smartFileSystem;
    }
    /**
     * @param Node[] $newStmts
     * @param Node[] $oldStmts
     * @param Node[] $oldTokens
     */
    public function printToFile(SmartFileInfo $fileInfo, array $newStmts, array $oldStmts, array $oldTokens) : string
    {
        $newContent = $this->betterStandardPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
        $this->smartFileSystem->dumpFile($fileInfo->getRealPath(), $newContent);
        $this->smartFileSystem->chmod($fileInfo->getRealPath(), $fileInfo->getPerms());
        return $newContent;
    }
    public function printParsedStmstAndTokensToString(File $file) : string
    {
        $newStmts = $this->resolveNewStmts($file);
        return $this->betterStandardPrinter->printFormatPreserving($newStmts, $file->getOldStmts(), $file->getOldTokens());
    }
    public function printParsedStmstAndTokens(File $file) : string
    {
        $newStmts = $this->resolveNewStmts($file);
        return $this->printToFile($file->getSmartFileInfo(), $newStmts, $file->getOldStmts(), $file->getOldTokens());
    }
    /**
     * @return Stmt[]|mixed[]
     */
    private function resolveNewStmts(File $file) : array
    {
        $newStmts = $file->getNewStmts();
        if (\count($newStmts) !== 1) {
            return $newStmts;
        }
        /** @var Namespace_|FileWithoutNamespace $onlyStmt */
        $onlyStmt = $newStmts[0];
        if (!$onlyStmt instanceof FileWithoutNamespace) {
            return $newStmts;
        }
        return $onlyStmt->stmts;
    }
}
