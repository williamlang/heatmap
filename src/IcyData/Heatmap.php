<?php

namespace IcyData;

use IcyData\Heatmap\Colours;

class Heatmap {

    /**
     * A list of available configuration options
     *
     * @var array
     */
    private static $options = [
        'width',
        'height',
        'backgroundImg',
        'radius',
        'cacheDir',
        'gradientFile',
        'gradientColours',
        'quality'
    ];

    /**
     * How large the points are on the image
     *
     * @var integer
     */
    private $radius = 15;

    /**
     * Cache directory
     *
     * @var string
     */
    private $cacheDir;

    /**
     * Name of the gradient file
     *
     * @var string
     */
    private $gradientFile = "gradient.png";

    /**
     * Array of points
     *
     * @var array
     */
    private $points = [];

    /**
     * Colours for each pixel in the gradient
     *
     * @var array
     */
    private $gradients = [];

    /**
     * Colours used in our gradient
     *
     * @var array
     */
    private $gradientColours = [
        Colours::BLUE,
        Colours::GREEN,
        Colours::YELLOW,
        Colours::RED,
        Colours::WHITE
    ];

    /**
     * Background image file path
     *
     * @var string
     */
    private $backgroundImg;

    /**
     * Width of the heatmap
     *
     * @var integer
     */
    private $width = 100;

    /**
     * Height of the heatmap
     *
     * @var integer
     */
    private $height = 100;

    /**
     * Heatmap alpha channel image
     *
     * @var Resource
     */
    private $heatmapAlpha;

    /**
     * The resulting heatmap quality
     *
     * @var integer
     */
    private $quality = 0;

    /**
     * Constructor
     *
     * Takes an array of options
     *
     * @param array $opts
     */
    public function __construct($opts = []) {
        $this->cacheDir = sys_get_temp_dir();

        foreach (self::$options as $option) {
            if (!empty($opts[$option])) {
                $this->{$option} = $opts[$option];
            }
        }
    }

    /**
     * Add a point to the heatmap
     *
     * @param integer $x
     * @param integer $y
     * @return void
     */
    public function addPoint(int $x, int $y) {
        $this->points[] = ['x' => $x, 'y' => $y];
    }

    /**
     * Generate and save our heatmap
     *
     * @param string $fileName
     * @return boolean
     */
    public function save(string $fileName) {
        if (!empty($this->backgroundImg)) {
            if (file_exists($this->backgroundImg)) {
                $this->image = imagecreatefromstring(file_get_contents($this->backgroundImg));
            } else {
                throw new \InvalidArgumentException("backgroundImg does not exist.");
            }

            $this->width = imagesx($this->image);
            $this->height = imagesy($this->image);
        } else {
            $this->image = imagecreatetruecolor($this->width, $this->height);
            $white = imagecolorallocate($this->image, 255, 255, 255);
            imagefilledrectangle($this->image, 0, 0, $this->width - 1, $this->height - 1, $white);
        }

        imagesavealpha($this->image, true);

        $this->heatmapAlpha = imagecreatetruecolor($this->width, $this->height);
        $white = imagecolorallocate($this->heatmapAlpha, 255, 255, 255);
        imagefilledrectangle($this->heatmapAlpha, 0, 0, $this->width - 1, $this->height - 1, $white);

        $this->drawGradient();
        $this->drawHeatMapAlpha();
        $this->drawBlur();
        $this->drawHeatMap($fileName);

        imagedestroy($this->heatmapAlpha);

        imagepng($this->image, $fileName, $this->quality);
        imagedestroy($this->image);

        return true;
    }

    /**
     * Create the gradient for our heatmap
     *
     * @todo configurable gradient transitions
     *
     * @return void
     */
    private function drawGradient() {
        if (file_exists($this->cacheDir . $this->gradientFile)) {
            $gradient = imagecreatefrompng($this->cacheDir . $this->gradientFile);

            for ($i = 0; $i < imagesx($gradient); $i++) {
                $index = imagecolorat($gradient, $i, 0);
                $this->gradients[$i] = imagecolorsforindex($gradient, $index);
            }

            imagedestroy($gradient);
        } else {
            $gradient = imagecreatetruecolor(256, 1);
            imagealphablending($gradient, false );
            imagesavealpha($gradient, true);

            $transitions = [
                [
                    'start'       => 0,
                    'end'         => 128,
                    'startColour' => Colours::hex2rgbArray($this->gradientColours[0]),
                    'endColour'   => Colours::hex2rgbArray($this->gradientColours[1])
                ],
                [
                    'start'       => 128,
                    'end'         => 192,
                    'startColour' => Colours::hex2rgbArray($this->gradientColours[1]),
                    'endColour'   => Colours::hex2rgbArray($this->gradientColours[2])
                ],
                [
                    'start'       => 192,
                    'end'         => 240,
                    'startColour' => Colours::hex2rgbArray($this->gradientColours[2]),
                    'endColour'   => Colours::hex2rgbArray($this->gradientColours[3])
                ],
                [
                    'start'       => 240,
                    'end'         => 256,
                    'startColour' => Colours::hex2rgbArray($this->gradientColours[3]),
                    'endColour'   => Colours::hex2rgbArray($this->gradientColours[4])
                ]
            ];

            foreach ($transitions as $transition) {
                $start     = $transition['start'];
                $end       = $transition['end'];
                $steps     = $end - $start;
                $colourOne = $transition['startColour'];
                $colourTwo = $transition['endColour'];

                for ($i = 0; $i < $steps; $i++) {
                    $t = $i / $steps;
                    $r = $colourTwo['red'] * $t + $colourOne['red'] * (1 - $t);
                    $g = $colourTwo['green'] * $t + $colourOne['green'] * (1 - $t);
                    $b = $colourTwo['blue'] * $t + $colourOne['blue'] * (1 - $t);
                    $a = 127 - (($i + $start) / 255 * 127);

                    imagesetpixel($gradient, $i + $start, 0, imagecolorallocatealpha($gradient, $r, $g, $b, $a));

                    $this->gradients[$i + $start] = [
                        'red'   => $r,
                        'green' => $g,
                        'blue'  => $b,
                        'alpha' => $a
                    ];
                }
            }

            imagepng($gradient, $this->cacheDir . $this->gradientFile, 0);
            imagedestroy($gradient);
        }
    }

    /**
     * Create the heatmap
     *
     * @return void
     */
    private function drawHeatMapAlpha() {
        // 5% opaque point
        $black = imagecolorallocatealpha($this->heatmapAlpha, 0, 0, 0, 127 * 0.92);

        // this will create black circles that have some transparency
        // the idea is many of these transparent circles on top of each other
        // will slowly create a more opaque spot
        foreach ($this->points as $point) {
            for ($r = $this->radius; $r > 0; $r--) {
                imagefilledellipse($this->heatmapAlpha, $point['x'], $point['y'], $r, $r, $black);
            }
        }
    }

    /**
     * Gaussian blur our heatap
     *
     * @return void
     */
    private function drawBlur() {
        imagefilter($this->heatmapAlpha, IMG_FILTER_GAUSSIAN_BLUR);
    }

    /**
     * Take the intensities from adding repeated points on the heatmapAlpha, and replace them with a colour
     * To prevent from changing the colour of our rink, we only need to look at points around the points on our graph
     *
     * @return void
     */
    private function drawHeatMap() {
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                // get intensity from heatmapAlpha
                $intensity = imagecolorsforindex($this->heatmapAlpha, imagecolorat($this->heatmapAlpha, $x, $y));

                // our mask colour, continue to next pixel
                if ($intensity['red'] == 255 && $intensity['green'] == 255 && $intensity['blue'] == 255) {
                    continue;
                }

                // using the intensity get the colour
                $gradientColor = $this->gradients[255 - $intensity['red']];

                // create the color
                $color = imagecolorallocatealpha($this->image, $gradientColor['red'], $gradientColor['green'], $gradientColor['blue'], $gradientColor['alpha'] * 0.9);

                // set the color at that pixel
                imagesetpixel($this->image, $x, $y, $color);
            }
        }
    }
}