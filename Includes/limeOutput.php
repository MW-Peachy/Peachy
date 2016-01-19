<?php

/**
 * lime_output class.
 * @package lime
 */
class lime_output
{
    public $colorizer = null;
    public $base_dir = null;

    /**
     * @param bool|false $force_colors
     * @param null $base_dir
     */
    public function __construct($force_colors = false, $base_dir = null)
    {
        $this->colorizer = new lime_colorizer($force_colors);
        $this->base_dir = $base_dir === null ? getcwd() : $base_dir;
    }

    /**
     * Produces an Echo
     */
    public function diag()
    {
        $messages = func_get_args();
        foreach ($messages as $message) {
            echo $this->colorizer->colorize('# ' . join("\n# ", (array)$message), 'COMMENT') . "\n";
        }
    }

    /**
     * @param $message
     */
    public function comment($message)
    {
        echo $this->colorizer->colorize(sprintf('# %s', $message), 'COMMENT') . "\n";
    }

    /**
     * @param $message
     */
    public function info($message)
    {
        echo $this->colorizer->colorize(sprintf('> %s', $message), 'INFO_BAR') . "\n";
    }

    /**
     * @param $message
     * @param null $file
     * @param null $line
     * @param array $traces
     */
    public function error($message, $file = null, $line = null, $traces = array())
    {
        if ($file !== null) {
            $message .= sprintf("\n(in %s on line %s)", $file, $line);
        }

        // some error messages contain absolute file paths
        $message = $this->strip_base_dir($message);

        $space = $this->colorizer->colorize(str_repeat(' ', 71), 'RED_BAR') . "\n";
        $message = trim($message);
        $message = wordwrap($message, 66, "\n");

        echo "\n" . $space;
        foreach (explode("\n", $message) as $message_line) {
            echo $this->colorizer->colorize(str_pad('  ' . $message_line, 71, ' '), 'RED_BAR') . "\n";
        }
        echo $space . "\n";

        if (count($traces) > 0) {
            echo $this->colorizer->colorize('Exception trace:', 'COMMENT') . "\n";

            $this->print_trace(null, $file, $line);

            foreach ($traces as $trace) {
                if (array_key_exists('class', $trace)) {
                    $method = sprintf('%s%s%s()', $trace['class'], $trace['type'], $trace['function']);
                } else {
                    $method = sprintf('%s()', $trace['function']);
                }

                if (array_key_exists('file', $trace)) {
                    $this->print_trace($method, $trace['file'], $trace['line']);
                } else {
                    $this->print_trace($method);
                }
            }

            echo "\n";
        }
    }

    /**
     * @param string $method
     */
    protected function print_trace($method = null, $file = null, $line = null)
    {
        if (!is_null($method)) {
            $method .= ' ';
        }

        echo '  ' . $method . 'at ';

        if (!is_null($file) && !is_null($line)) {
            printf("%s:%s\n", $this->colorizer->colorize($this->strip_base_dir($file), 'TRACE'),
                $this->colorizer->colorize($line, 'TRACE'));
        } else {
            echo "[internal function]\n";
        }
    }

    /**
     * @param $message
     * @param null $colorizer_parameter
     * @param bool|true $colorize
     */
    public function echoln($message, $colorizer_parameter = null, $colorize = true)
    {
        if ($colorize) {
            $message = preg_replace('/(?:^|\.)((?:not ok|dubious|errors) *\d*)\b/e',
                '$this->colorizer->colorize(\'$1\', \'ERROR\')', $message);
            $message = preg_replace('/(?:^|\.)(ok *\d*)\b/e', '$this->colorizer->colorize(\'$1\', \'INFO\')', $message);
            $message = preg_replace('/"(.+?)"/e', '$this->colorizer->colorize(\'$1\', \'PARAMETER\')', $message);
            $message = preg_replace('/(\->|\:\:)?([a-zA-Z0-9_]+?)\(\)/e',
                '$this->colorizer->colorize(\'$1$2()\', \'PARAMETER\')', $message);
        }

        echo ($colorizer_parameter ? $this->colorizer->colorize($message, $colorizer_parameter) : $message) . "\n";
    }

    /**
     * @param string $message
     */
    public function green_bar($message)
    {
        echo $this->colorizer->colorize($message . str_repeat(' ', 71 - min(71, strlen($message))), 'GREEN_BAR') . "\n";
    }

    /**
     * @param string $message
     */
    public function red_bar($message)
    {
        echo $this->colorizer->colorize($message . str_repeat(' ', 71 - min(71, strlen($message))), 'RED_BAR') . "\n";
    }

    /**
     * @return string
     */
    protected function strip_base_dir($text)
    {
        return str_replace(DIRECTORY_SEPARATOR, '/',
            str_replace(realpath($this->base_dir) . DIRECTORY_SEPARATOR, '', $text));
    }
}
