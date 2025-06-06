<?php

declare (strict_types=1);
namespace Rector\ValueObject\Bootstrap;

final class BootstrapConfigs
{
    /**
     * @readonly
     */
    private ?string $mainConfigFile;
    /**
     * @var string[]
     * @readonly
     */
    private array $setConfigFiles;
    /**
     * @param string[] $setConfigFiles
     */
    public function __construct(?string $mainConfigFile, array $setConfigFiles)
    {
        $this->mainConfigFile = $mainConfigFile;
        $this->setConfigFiles = $setConfigFiles;
    }
    public function getMainConfigFile() : ?string
    {
        return $this->mainConfigFile;
    }
    /**
     * @return string[]
     */
    public function getConfigFiles() : array
    {
        $configFiles = [];
        if ($this->mainConfigFile !== null) {
            $configFiles[] = $this->mainConfigFile;
        }
        return \array_merge($configFiles, $this->setConfigFiles);
    }
}
