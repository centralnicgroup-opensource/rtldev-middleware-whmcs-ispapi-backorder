<?php
//###########################################################
//################### HELPER FUNCTIONS ######################
//###########################################################

function formatPrice($number, $cur)
{
    $format = $cur["format"];
    if ($format == 1) {
        $number = number_format($number, 2, '.', '');
    }
    if ($format == 2) {
        $number = number_format($number, 2, '.', ',');
    }
    if ($format == 3) {
        $number = number_format($number, 2, ',', '.');
    }
    if ($format == 4) {
        $number = preg_replace('/\.?0+$/', '', number_format($number, 2, '.', ','));
    }
    $price = $cur["prefix"].$number." ".$cur["suffix"];
    if (function_exists('mb_detect_encoding')) {
        if (mb_detect_encoding($price, 'UTF-8, ISO-8859-1') === 'UTF-8') {
            return $price;
        } else {
            return utf8_encode($price);
        }
    } else {
        return $price;
    }
}
