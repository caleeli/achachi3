<?php

namespace Achachi;

class CodeParser
{

    public $tokens = [];
    public static $EXPRESSION;
    private $initialI = 0;
    private $finalI = null;

    const EXPRESSION = '*EXPRESSION*';

    function __construct($source)
    {
        $this->tokens = token_get_all($source);
        CodeParser::$EXPRESSION = function(&$i, CodeParser $codeParser) {
            $res = $codeParser->getRightExpression($i);
            $i = $i + count($res) - 1;
            return count($res) > 0;
        };
    }

    private function row2i($row)
    {
        foreach ($this->tokens as $i => $t) {
            if ($row <= 1) {
                break;
            }
            $row -= count(explode("\n", is_array($t) ? $t[1] : $t)) - 1;
        }
        return $i;
    }

    public function setInitialRow($row)
    {
        $this->initialI = $this->row2i($row);
    }

    public function setFinalRow($row)
    {
        $this->finalI = $this->row2i($row);
    }

    public function getRightExpression($i)
    {
        $res = [];
        $start = false;
        $par = 0;
        $lla = 0;
        $bra = 0;
        $tri = 0;
        for (; $i < count($this->tokens); $i++) {
            $tk = $this->tokens[$i];
            if (is_array($tk)) {
                $code = $tk[0];
                $string = $tk[1];
            } else {
                $code = 0;
                $string = $tk;
            }
            if ($string === '(')
                $par++;
            if ($string === '{')
                break; //$lla++;
            if ($string === '[')
                $bra++;
            if ($string === '?')
                $tri++;
            if ($string === ')')
                $par--;
            if ($string === '}')
                break; //$lla--;
            if ($string === ']')
                $bra--;
            if ($string === ':')
                $tri--;
            if ($par < 0)
                break;
            if ($lla < 0)
                break;
            if ($bra < 0)
                break;
            if ($tri < 0)
                break;
            if ($code === T_OPEN_TAG)
                break;
            if ($code === T_CLOSE_TAG)
                break;
            if ($par === 0 && $lla === 0 && $bra === 0 && $tri === 0) {
                if ($string === ';')
                    break;
                if ($string === ',')
                    break;
                if ($string === '=')
                    break;
            }
            //$res[]=$tk;
            $res[$i] = $tk;
        }
        return $res;
    }

    function findCode(&$i, array $codeExp, &$j = null)
    {
        $j = $i;
        $l = $this->finalI === null ? count($this->tokens) : $this->finalI;
        while ($i < $l) {
            $matches = [];
            $i1 = $i;
            $j = $i;
            if ($this->matchCode($codeExp, $i1, $matches)) {
                foreach($matches as $key => $ma) {
                    $matches[$key] = $this->tokens2string($ma);
                }
                $matches[0] = $this->tokens2string($this->tokens, $i, $i1);
                $i = $i1;
                return $matches;
            }
            $i++;
        }
    }

    public function tokens2string($tokens, $from = 0, $to = -1)
    {
        ksort($tokens);
        $res = '';
        foreach ($tokens as $i => $tk) {
            if ($i >= $from && (($to == -1) || ($i <= $to))) {
                if (is_array($tk)) {
                    $code = $tk[0];
                    $string = $tk[1];
                } else {
                    $code = 0;
                    $string = $tk;
                }
                $res .= $string;
            }
        }
        return $res;
    }

    public function getPosition($i)
    {
        return strlen($this->tokens2string($this->tokens, 0, $i));
    }

    public function matchCode(array $codeExp, &$i, &$matches)
    {
        $multiplicity = isset($codeExp['multiplicity']) ? $codeExp['multiplicity'] : 1;
        $match = isset($codeExp['name']) ? $codeExp['name'] : null;
        unset($codeExp['name']);
        unset($codeExp['multiplicity']);
        $i0 = $i;
        $cnt = 0;
        while (true) {
            $i1 = $i;
            $ok = true;
            //foreach ($codeExp as $index => $c) {
            $offseti = 0;
            for ($index = 0, $l = count($codeExp); $index < $l; $index++) {
                //while (($c = current($codeExp)) !== false) {
                //$index = key($codeExp);
                //next($codeExp);
                $c = $codeExp[$index];
                if ($c === self::EXPRESSION) {
                    $exp = [];
                    foreach ($this->getRightExpression($i) as $e) {
                        $exp[] = is_array($e) ? $e[0] : $e;
                    }
                    array_splice($codeExp, $index, 1, $exp);
                    $index--;
                    $l = count($codeExp);
                    continue;
                }
                $tk = $this->tokens[$i];
                if (is_array($tk)) {
                    $code = $tk[0];
                    $string = $tk[1];
                } else {
                    $code = 0;
                    $string = $tk;
                }
                if (is_string($c) && $c !== $string) {
                    $ok = false;
                    break;
                } elseif (is_numeric($c) && $c !== $code) {
                    $ok = false;
                    break;
                } elseif (is_object($c) && !$c($i, $this)) {
                    $ok = false;
                    break;
                } elseif (is_array($c) && !$this->matchCode($c, $i, $matches)) {
                    $ok = false;
                    break;
                }
                $i++;
            }
            if ($ok && isset($match)) {
                if (!isset($matches[$match])) {
                    $matches[$match] = [];
                }
                $matches[$match][] = $this->tokens2string($this->tokens, $i1,
                    $i - 1);
            }
            switch ($multiplicity) {
                case 1:
                    if ($ok) {
                        $i--;
                        return true;
                    } else {
                        $i = $i0;
                        return false;
                    }
                    break;
                case '*':
                    if ($ok) {
                        $cnt++;
                        //continua con el while
                    } else {
                        if ($cnt === 0) {
                            $i = $i0 - 1; //-1 para que i++ de foreach ($codeExp as $index => $c) no se incremente
                            return true;
                        } else {
                            $i = $i1 - 1;
                            return true;
                        }
                    }
                    break;
                case '?':
                    if ($ok) {
                        $i = $i - 1;
                        return true;
                    } else {
                        $i = $i0 - 1; //-1 para que i++ de foreach ($codeExp as $index => $c) no se incremente
                        return true;
                    }
                    break;
                case '+':
                    if ($ok) {
                        $cnt++;
                        //continua con el while
                    } else {
                        if ($cnt === 0) {
                            $i = $i0;
                            return false;
                        } else {
                            $i = $i1 - 1;
                            return true;
                        }
                    }
                    break;
            }
        }
    }

    public function forEachMatches($codeExp, Callable $callback)
    {
        $i = $this->initialI;
        $l = $this->finalI === null ? count($this->tokens) : $this->finalI;
        while ($i < $l) {
            $cc = $this->findCode($i, $codeExp);
            if ($cc) {
                $callback($cc, $this);
            }
            $i++;
        }
    }

    /**
     * Tokens replaced
     * @param type $codeExp
     * @param Callable $callback
     */
    public function replaceMatches($codeExp, Callable $callback)
    {
        $result = [];
        $i = 0;
        $i1 = 0;
        $l = count($this->tokens);
        while ($i < $l) {
            $j = $i;
            $cc = $this->findCode($i, $codeExp, $i1);
            if ($cc) {
                $replace = $callback($cc, $this, $i, $i1);
            } else {
                $i1 = $l;
            }
            for ($u = $j; $u < $i1; $u++) {
                $result[] = $this->tokens[$u];
            }
            if ($cc) {
                foreach ($replace as $rep) {
                    $result[] = $rep;
                }
            }
            $i++;
        }
        return $result;
    }
}
