<?php
// MergeSort.php
function mergeSort(&$array, $key) {
    if (count($array) <= 1) return;
    $mid = count($array) / 2;
    $left = array_slice($array, 0, $mid);
    $right = array_slice($array, $mid);
    mergeSort($left, $key);
    mergeSort($right, $key);
    $array = merge($left, $right, $key);
}

function merge($left, $right, $key) {
    $result = []; $i = 0; $j = 0;
    while ($i < count($left) && $j < count($right)) {
        if (strtolower($left[$i][$key]) <= strtolower($right[$j][$key])) {
            $result[] = $left[$i]; $i++;
        } else {
            $result[] = $right[$j]; $j++;
        }
    }
    while ($i < count($left)) { $result[] = $left[$i]; $i++; }
    while ($j < count($right)) { $result[] = $right[$j]; $j++; }
    return $result;
}
?>