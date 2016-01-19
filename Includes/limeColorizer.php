<?php

/**
 * lime_colorizer class.
 * @package lime
 */
class lime_colorizer
{
    static public $styles = array();

    protected $colors_supported = false;

    /**
     * @param bool|false $force_colors
     */
    public function __construct($force_colors = false)
    {
        if ($force_colors) {
            $this->colors_supported = true;
        } else {
            // colors are supported on windows with ansicon or on tty consoles
            if (DIRECTORY_SEPARATOR == '\\') {
                $this->colors_supported = false !== getenv('ANSICON');
            } else {
                $this->colors_supported = function_exists('posix_isatty') && @posix_isatty(STDOUT);
            }
        }
    }

    /**
     * @param $name
     * @param array $options
     */
    public static function style($name, $options = array())
    {
        self::$styles[$name] = $options;
    }

    /**
     * @param string $text
     * @param array $parameters
     * @return string
     */
    public function colorize($text = '', $parameters = array())
    {

        if (!$this->colors_supported) {
            return $text;
        }

        static $options = array('bold' => 1, 'underscore' => 4, 'blink' => 5, 'reverse' => 7, 'conceal' => 8);
        static $foreground = array(
            'black' => 30,
            'red' => 31,
            'green' => 32,
            'yellow' => 33,
            'blue' => 34,
            'magenta' => 35,
            'cyan' => 36,
            'white' => 37
        );
        static $background = array(
            'black' => 40,
            'red' => 41,
            'green' => 42,
            'yellow' => 43,
            'blue' => 44,
            'magenta' => 45,
            'cyan' => 46,
            'white' => 47
        );

        !is_array($parameters) && isset(self::$styles[$parameters]) and $parameters = self::$styles[$parameters];

        $codes = array();
        isset($parameters['fg']) and $codes[] = $foreground[$parameters['fg']];
        isset($parameters['bg']) and $codes[] = $background[$parameters['bg']];
        foreach ($options as $option => $value) {
            isset($parameters[$option]) && $parameters[$option] and $codes[] = $value;
        }

        return "\033[" . implode(';', $codes) . 'm' . $text . "\033[0m";
    }
}
