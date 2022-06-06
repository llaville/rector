<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Core\PhpParser;

use RectorPrefix20220606\PhpParser\Node\Stmt\Class_;
use RectorPrefix20220606\PhpParser\Node\Stmt\ClassLike;
use RectorPrefix20220606\PhpParser\Node\Stmt\Enum_;
use RectorPrefix20220606\PhpParser\Node\Stmt\Interface_;
use RectorPrefix20220606\PhpParser\Node\Stmt\Trait_;
use RectorPrefix20220606\PHPStan\Reflection\ClassReflection;
use RectorPrefix20220606\Rector\Core\PhpParser\Node\BetterNodeFinder;
use RectorPrefix20220606\Rector\Core\ValueObject\Application\File;
use RectorPrefix20220606\Symplify\Astral\PhpParser\SmartPhpParser;
final class ClassLikeAstResolver
{
    /**
     * Parsing files is very heavy performance, so this will help to leverage it
     * The value can be also null, as the method might not exist in the class.
     *
     * @var array<class-string, Class_|Trait_|Interface_|Enum_|null>
     */
    private $classLikesByName = [];
    /**
     * @readonly
     * @var \Symplify\Astral\PhpParser\SmartPhpParser
     */
    private $smartPhpParser;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\BetterNodeFinder
     */
    private $betterNodeFinder;
    public function __construct(SmartPhpParser $smartPhpParser, BetterNodeFinder $betterNodeFinder)
    {
        $this->smartPhpParser = $smartPhpParser;
        $this->betterNodeFinder = $betterNodeFinder;
    }
    /**
     * @return \PhpParser\Node\Stmt\Trait_|\PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Interface_|\PhpParser\Node\Stmt\Enum_|null
     */
    public function resolveClassFromClassReflection(ClassReflection $classReflection, string $desiredClassName)
    {
        if ($classReflection->isBuiltin()) {
            return null;
        }
        if (isset($this->classLikesByName[$classReflection->getName()])) {
            return $this->classLikesByName[$classReflection->getName()];
        }
        $fileName = $classReflection->getFileName();
        // probably internal class
        if ($fileName === null) {
            // avoid parsing falsy-file again
            $this->classLikesByName[$classReflection->getName()] = null;
            return null;
        }
        $stmts = $this->smartPhpParser->parseFile($fileName);
        if ($stmts === []) {
            // avoid parsing falsy-file again
            $this->classLikesByName[$classReflection->getName()] = null;
            return null;
        }
        /** @var array<Class_|Trait_|Interface_|Enum_> $classLikes */
        $classLikes = $this->betterNodeFinder->findInstanceOf($stmts, ClassLike::class);
        $reflectionClassName = $classReflection->getName();
        foreach ($classLikes as $classLike) {
            if ($reflectionClassName !== $desiredClassName) {
                continue;
            }
            $this->classLikesByName[$classReflection->getName()] = $classLike;
            return $classLike;
        }
        $this->classLikesByName[$classReflection->getName()] = null;
        return null;
    }
}
