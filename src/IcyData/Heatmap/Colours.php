<?php

namespace IcyData\Heatmap;

class Colours {

    /**
     * Hex code for blue
     */
    CONST BLUE = '0000FF';

    /**
     * Hex code for green
     */
    CONST GREEN = '00FF00';

    /**
     * Hex code for yellow
     */
    CONST YELLOW = 'FFFF00';

    /**
     * Hex code for red
     */
    CONST RED = 'FF0000';

    /**
     * Hex code for white
     */
    CONST WHITE = 'FFFFFF';

    /**
     * Convert a hex string to RGB array
     *
     * Return matches that of GD's imagecolorsforindex
     *
     * @param string $hex
     * @return array
     */
    public static function hex2rgbArray(string $hex) {
        return [
            'red'   => hexdec(substr($hex, 0, 2)),
            'green' => hexdec(substr($hex, 2, 2)),
            'blue'  => hexdec(substr($hex, 4, 2))
        ];
    }
}