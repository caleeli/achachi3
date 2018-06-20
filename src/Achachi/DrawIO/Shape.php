<?php

namespace Achachi\DrawIO;

/**
 * Description of Shape
 *
 */
class Shape
{
    public $id;

    /**
     * @var Shape
     */
    public $parent;

    /**
     * @var Shape
     */
    public $source;

    /**
     * @var Shape
     */
    public $target;

    public function __construct($data)
    {
        foreach ($data as $a => $v) {
            $this->$a = $v;
        }
    }
}
