<?php
$expression = '{order:purchase,abc}';
const PAYLOAD_EXPRESSION = '/\{([\w.,:\s]+)\}/';
const MAP_EXPRESSION = '/^\s*(\w+)\s*\:\s*([\w.]+)\s*$/';
const ITEM_EXPRESSION = '/^\s*(\w+)\s*$/';

if (preg_match(PAYLOAD_EXPRESSION, $expression, $match)) {
    $elements = explode(',', $match[1]);
    foreach ($elements as $element) {
        if (preg_match(MAP_EXPRESSION, $element, $map)) {
            $name = $map[1];
            $value = $map[2];
        } elseif (preg_match(ITEM_EXPRESSION, $element, $map)) {
            $name = $map[1];
            $value = $map[1];
        }
        var_dump([$name => $value]);
    }
}
