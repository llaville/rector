<?php

declare (strict_types=1);
namespace RectorPrefix20220606\Rector\Nette\NodeAnalyzer;

use RectorPrefix20220606\PhpParser\Node\Expr\Variable;
use RectorPrefix20220606\Rector\Nette\ValueObject\TemplateParametersAssigns;
/**
 * Replaces:
 *
 * if (...) { $this->template->key = 'some'; } else { $this->template->key = 'another'; }
 *
 * ↓
 *
 * if (...) { $key = 'some'; } else { $key = 'another'; }
 */
final class ConditionalTemplateAssignReplacer
{
    public function processClassMethod(TemplateParametersAssigns $templateParametersAssigns) : void
    {
        foreach ($templateParametersAssigns->getNonSingleParameterAssigns() as $parameterAssign) {
            $assign = $parameterAssign->getAssign();
            $assign->var = new Variable($parameterAssign->getParameterName());
        }
    }
}
