<?php

/*
 * @author M.paraiso
 */

namespace Mparaiso\Doctrine\ORM\Functions;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

class Date extends FunctionNode
{
    public $date;
    public $modifier;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        if (NULL !== $this->modifier) {
            return "DATE(" . $this->date->dispatch($sqlWalker) . "," . $this->modifier->dispatch($sqlWalker) . ")";
        }
        return "DATE(" . $this->date->dispatch($sqlWalker) . ")";
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->date = $parser->Literal();

        if (Lexer::T_COMMA === $lexer->lookahead['type']) {
            $parser->match(Lexer::T_COMMA);
            $this->modifier = $parser->Literal();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);


    }
}
