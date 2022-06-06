<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Compatibility\ValueObject;

use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use RectorPrefix20220606\Rector\Core\Exception\ShouldNotHappenException;
final class PropertyWithPhpDocInfo
{
    /**
     * @readonly
     * @var string
     */
    private $propertyName;
    /**
     * @readonly
     * @var \PhpParser\Node\Stmt\Property
     */
    private $property;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo
     */
    private $phpDocInfo;
    public function __construct(string $propertyName, Property $property, PhpDocInfo $phpDocInfo)
    {
        $this->propertyName = $propertyName;
        $this->property = $property;
        $this->phpDocInfo = $phpDocInfo;
    }
    public function getProperty() : Property
    {
        return $this->property;
    }
    public function getPhpDocInfo() : PhpDocInfo
    {
        return $this->phpDocInfo;
    }
    public function getPropertyName() : string
    {
        return $this->propertyName;
    }
    public function getParamTagValueNode() : ParamTagValueNode
    {
        $varTagValueNode = $this->phpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            throw new ShouldNotHappenException();
        }
        return new ParamTagValueNode($varTagValueNode->type, \false, '$' . $this->propertyName, '');
    }
}
