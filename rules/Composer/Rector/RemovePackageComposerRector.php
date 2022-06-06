<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Composer\Rector;

use RectorPrefix20220606\Rector\Composer\Contract\Rector\ComposerRectorInterface;
use RectorPrefix20220606\Symplify\ComposerJsonManipulator\ValueObject\ComposerJson;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use RectorPrefix20220606\Webmozart\Assert\Assert;
/**
 * @see \Rector\Tests\Composer\Rector\RemovePackageComposerRector\RemovePackageComposerRectorTest
 */
final class RemovePackageComposerRector implements ComposerRectorInterface
{
    /**
     * @var string[]
     */
    private $packageNames = [];
    public function refactor(ComposerJson $composerJson) : void
    {
        foreach ($this->packageNames as $packageName) {
            $composerJson->removePackage($packageName);
        }
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Remove package from "require" and "require-dev" in `composer.json`', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
{
    "require": {
        "symfony/console": "^3.4"
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
{
}
CODE_SAMPLE
, ['symfony/console'])]);
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration) : void
    {
        Assert::allString($configuration);
        $this->packageNames = $configuration;
    }
}
