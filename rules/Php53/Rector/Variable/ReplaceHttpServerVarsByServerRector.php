<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Php53\Rector\Variable;

use RectorPrefix20220606\PhpParser\Node;
use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\Rector\Core\Rector\AbstractRector;
use RectorPrefix20220606\Rector\Core\ValueObject\PhpVersionFeature;
use RectorPrefix20220606\Rector\VersionBonding\Contract\MinPhpVersionInterface;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use RectorPrefix20220606\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\Php53\Rector\Variable\ReplaceHttpServerVarsByServerRector\ReplaceHttpServerVarsByServerRectorTest
 * @changelog https://blog.tigertech.net/posts/php-5-3-http-server-vars/
 */
final class ReplaceHttpServerVarsByServerRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @var array<string, string>
     */
    private const VARIABLE_RENAME_MAP = ['HTTP_SERVER_VARS' => '_SERVER', 'HTTP_GET_VARS' => '_GET', 'HTTP_POST_VARS' => '_POST', 'HTTP_POST_FILES' => '_FILES', 'HTTP_SESSION_VARS' => '_SESSION', 'HTTP_ENV_VARS' => '_ENV', 'HTTP_COOKIE_VARS' => '_COOKIE'];
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::SERVER_VAR;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Rename old $HTTP_* variable names to new replacements', [new CodeSample('$serverVars = $HTTP_SERVER_VARS;', '$serverVars = $_SERVER;')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Variable::class];
    }
    /**
     * @param Variable $node
     */
    public function refactor(Node $node) : ?Node
    {
        foreach (self::VARIABLE_RENAME_MAP as $oldName => $newName) {
            if (!$this->isName($node, $oldName)) {
                continue;
            }
            $node->name = $newName;
            return $node;
        }
        return null;
    }
}
