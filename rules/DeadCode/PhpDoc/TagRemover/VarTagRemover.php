<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DeadCode\PhpDoc\TagRemover;

use RectorPrefix20220606\PhpParser\Node\Param;
use RectorPrefix20220606\PhpParser\Node\Stmt\Expression;
use RectorPrefix20220606\PhpParser\Node\Stmt\Property;
use RectorPrefix20220606\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use RectorPrefix20220606\PHPStan\Type\Generic\TemplateObjectWithoutClassType;
use RectorPrefix20220606\PHPStan\Type\Type;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use RectorPrefix20220606\Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use RectorPrefix20220606\Rector\DeadCode\PhpDoc\DeadVarTagValueNodeAnalyzer;
use RectorPrefix20220606\Rector\PHPStanStaticTypeMapper\DoctrineTypeAnalyzer;
final class VarTagRemover
{
    /**
     * @readonly
     * @var \Rector\PHPStanStaticTypeMapper\DoctrineTypeAnalyzer
     */
    private $doctrineTypeAnalyzer;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory
     */
    private $phpDocInfoFactory;
    /**
     * @readonly
     * @var \Rector\DeadCode\PhpDoc\DeadVarTagValueNodeAnalyzer
     */
    private $deadVarTagValueNodeAnalyzer;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger
     */
    private $phpDocTypeChanger;
    public function __construct(DoctrineTypeAnalyzer $doctrineTypeAnalyzer, PhpDocInfoFactory $phpDocInfoFactory, DeadVarTagValueNodeAnalyzer $deadVarTagValueNodeAnalyzer, PhpDocTypeChanger $phpDocTypeChanger)
    {
        $this->doctrineTypeAnalyzer = $doctrineTypeAnalyzer;
        $this->phpDocInfoFactory = $phpDocInfoFactory;
        $this->deadVarTagValueNodeAnalyzer = $deadVarTagValueNodeAnalyzer;
        $this->phpDocTypeChanger = $phpDocTypeChanger;
    }
    public function removeVarTagIfUseless(PhpDocInfo $phpDocInfo, Property $property) : void
    {
        $varTagValueNode = $phpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            return;
        }
        $isVarTagValueDead = $this->deadVarTagValueNodeAnalyzer->isDead($varTagValueNode, $property);
        if (!$isVarTagValueDead) {
            return;
        }
        if ($this->phpDocTypeChanger->isAllowed($varTagValueNode->type)) {
            return;
        }
        $phpDocInfo->removeByType(VarTagValueNode::class);
    }
    /**
     * @param \PhpParser\Node\Stmt\Expression|\PhpParser\Node\Stmt\Property|\PhpParser\Node\Param $node
     */
    public function removeVarPhpTagValueNodeIfNotComment($node, Type $type) : void
    {
        if ($type instanceof TemplateObjectWithoutClassType) {
            return;
        }
        // keep doctrine collection narrow type
        if ($this->doctrineTypeAnalyzer->isDoctrineCollectionWithIterableUnionType($type)) {
            return;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $varTagValueNode = $phpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            return;
        }
        // has description? keep it
        if ($varTagValueNode->description !== '') {
            return;
        }
        // keep string[] etc.
        if ($this->phpDocTypeChanger->isAllowed($varTagValueNode->type)) {
            return;
        }
        $phpDocInfo->removeByType(VarTagValueNode::class);
    }
}
