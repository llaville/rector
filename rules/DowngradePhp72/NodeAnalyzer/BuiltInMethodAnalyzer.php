<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\DowngradePhp72\NodeAnalyzer;

use RectorPrefix20220606\PhpParser\Node\Stmt\ClassMethod;
use RectorPrefix20220606\PHPStan\Reflection\ClassReflection;
use RectorPrefix20220606\Rector\FamilyTree\NodeAnalyzer\ClassChildAnalyzer;
use RectorPrefix20220606\Rector\NodeNameResolver\NodeNameResolver;
final class BuiltInMethodAnalyzer
{
    /**
     * @readonly
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @readonly
     * @var \Rector\FamilyTree\NodeAnalyzer\ClassChildAnalyzer
     */
    private $classChildAnalyzer;
    public function __construct(NodeNameResolver $nodeNameResolver, ClassChildAnalyzer $classChildAnalyzer)
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->classChildAnalyzer = $classChildAnalyzer;
    }
    public function isImplementsBuiltInInterface(ClassReflection $classReflection, ClassMethod $classMethod) : bool
    {
        if (!$classReflection->isClass()) {
            return \false;
        }
        $methodName = $this->nodeNameResolver->getName($classMethod);
        if ($this->classChildAnalyzer->hasChildClassMethod($classReflection, $methodName)) {
            return \false;
        }
        foreach ($classReflection->getInterfaces() as $interfaceReflection) {
            if (!$interfaceReflection->isBuiltin()) {
                continue;
            }
            if (!$interfaceReflection->hasMethod($methodName)) {
                continue;
            }
            return \true;
        }
        return \false;
    }
}
