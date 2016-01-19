<?php

/**
 * lime_coverage class.
 *
 * @extends lime_registration
 * @package lime
 */
class lime_coverage extends lime_registration
{
    public $files = array();
    public $extension = '.php';
    public $base_dir = '';
    public $harness = null;
    public $verbose = false;
    protected $coverage = array();

    /**
     * @param $harness
     * @throws Exception
     */
    public function __construct($harness)
    {
        $this->harness = $harness;

        if (!function_exists('xdebug_start_code_coverage')) {
            throw new Exception('You must install and enable xdebug before using lime coverage.');
        }

        if (!ini_get('xdebug.extended_info')) {
            throw new Exception('You must set xdebug.extended_info to 1 in your php.ini to use lime coverage.');
        }
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        if (!count($this->harness->files)) {
            throw new Exception('You must register some test files before running coverage!');
        }

        if (!count($this->files)) {
            throw new Exception('You must register some files to cover!');
        }

        $this->coverage = array();

        $this->process($this->harness->files);

        $this->output($this->files);
    }

    /**
     * @param $files
     * @throws Exception
     */
    public function process($files)
    {
        if (!is_array($files)) {
            $files = array($files);
        }

        $tmp_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.php';
        foreach ($files as $file) {
            $tmp = <<<EOF
<?php
xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
include('$file');
echo '<PHP_SER>'.serialize(xdebug_get_code_coverage()).'</PHP_SER>';
EOF;
            file_put_contents($tmp_file, $tmp);
            ob_start();
            // see http://trac.symfony-project.org/ticket/5437 for the explanation on the weird "cd" thing
            passthru(sprintf('cd & %s %s 2>&1', escapeshellarg($this->harness->php_cli), escapeshellarg($tmp_file)),
                $return);
            $retval = ob_get_clean();

            if (0 != $return) // test exited without success
            {
                // something may have gone wrong, we should warn the user so they know
                // it's a bug in their code and not symfony's

                $this->harness->output->echoln(sprintf('Warning: %s returned status %d, results may be inaccurate',
                    $file, $return), 'ERROR');
            }

            if (false === $cov = @unserialize(substr($retval, strpos($retval, '<PHP_SER>') + 9,
                    strpos($retval, '</PHP_SER>') - 9))
            ) {
                if (0 == $return) {
                    // failed to serialize, but PHP said it should of worked.
                    // something is seriously wrong, so abort with exception
                    throw new Exception(sprintf('Unable to unserialize coverage for file "%s"', $file));
                } else {
                    // failed to serialize, but PHP warned us that this might have happened.
                    // so we should ignore and move on
                    continue; // continue foreach loop through $this->harness->files
                }
            }

            foreach ($cov as $file => $lines) {
                if (!isset($this->coverage[$file])) {
                    $this->coverage[$file] = $lines;
                    continue;
                }

                foreach ($lines as $line => $flag) {
                    if ($flag == 1) {
                        $this->coverage[$file][$line] = 1;
                    }
                }
            }
        }

        if (file_exists($tmp_file)) {
            unlink($tmp_file);
        }
    }

    /**
     * @param $files
     */
    public function output($files)
    {
        ksort($this->coverage);
        $total_php_lines = 0;
        $total_covered_lines = 0;
        foreach ($files as $file) {
            $file = realpath($file);
            $is_covered = isset($this->coverage[$file]);
            $cov = isset($this->coverage[$file]) ? $this->coverage[$file] : array();
            $covered_lines = array();
            $missing_lines = array();
            $output = null;

            foreach ($cov as $line => $flag) {
                switch ($flag) {
                    case 1:
                        $covered_lines[] = $line;
                        break;
                    case -1:
                        $missing_lines[] = $line;
                        break;
                }
            }

            $total_lines = count($covered_lines) + count($missing_lines);
            if (!$total_lines) {
                // probably means that the file is not covered at all!
                $total_lines = count($this->get_php_lines(file_get_contents($file)));
            }

            $output = $this->harness->output;
            $percent = $total_lines ? count($covered_lines) * 100 / $total_lines : 0;

            $total_php_lines += $total_lines;
            $total_covered_lines += count($covered_lines);

            $relative_file = $this->get_relative_file($file);
            $output->echoln(sprintf("%-70s %3.0f%%", substr($relative_file, -min(70, strlen($relative_file))),
                $percent), $percent == 100 ? 'INFO' : ($percent > 90 ? 'PARAMETER' : ($percent < 20 ? 'ERROR' : '')));
            if ($this->verbose && $is_covered && $percent != 100) {
                $output->comment(sprintf("missing: %s", $this->format_range($missing_lines)));
            }
        }

        $output->echoln(sprintf("TOTAL COVERAGE: %3.0f%%",
            $total_php_lines ? $total_covered_lines * 100 / $total_php_lines : 0));
    }

    /**
     * @param $content
     * @return array
     */
    public static function get_php_lines($content)
    {
        if (is_readable($content)) {
            $content = file_get_contents($content);
        }

        $tokens = token_get_all($content);
        $php_lines = array();
        $current_line = 1;
        $in_class = false;
        $in_function = false;
        $in_function_declaration = false;
        $end_of_current_expr = true;
        $open_braces = 0;
        foreach ($tokens as $token) {
            if (is_string($token)) {
                switch ($token) {
                    case '=':
                        if (false === $in_class || (false !== $in_function && !$in_function_declaration)) {
                            $php_lines[$current_line] = true;
                        }
                        break;
                    case '{':
                        ++$open_braces;
                        $in_function_declaration = false;
                        break;
                    case ';':
                        $in_function_declaration = false;
                        $end_of_current_expr = true;
                        break;
                    case '}':
                        $end_of_current_expr = true;
                        --$open_braces;
                        if ($open_braces == $in_class) {
                            $in_class = false;
                        }
                        if ($open_braces == $in_function) {
                            $in_function = false;
                        }
                        break;
                }

                continue;
            }

            list($id, $text) = $token;

            switch ($id) {
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                    ++$open_braces;
                    break;
                case T_WHITESPACE:
                case T_OPEN_TAG:
                case T_CLOSE_TAG:
                    $end_of_current_expr = true;
                    $current_line += count(explode("\n", $text)) - 1;
                    break;
                case T_COMMENT:
                case T_DOC_COMMENT:
                    $current_line += count(explode("\n", $text)) - 1;
                    break;
                case T_CLASS:
                    $in_class = $open_braces;
                    break;
                case T_FUNCTION:
                    $in_function = $open_braces;
                    $in_function_declaration = true;
                    break;
                case T_AND_EQUAL:
                case T_BREAK:
                case T_CASE:
                case T_CATCH:
                case T_CLONE:
                case T_CONCAT_EQUAL:
                case T_CONTINUE:
                case T_DEC:
                case T_DECLARE:
                case T_DEFAULT:
                case T_DIV_EQUAL:
                case T_DO:
                case T_ECHO:
                case T_ELSEIF:
                case T_EMPTY:
                case T_ENDDECLARE:
                case T_ENDFOR:
                case T_ENDFOREACH:
                case T_ENDIF:
                case T_ENDSWITCH:
                case T_ENDWHILE:
                case T_EVAL:
                case T_EXIT:
                case T_FOR:
                case T_FOREACH:
                case T_GLOBAL:
                case T_IF:
                case T_INC:
                case T_INCLUDE:
                case T_INCLUDE_ONCE:
                case T_INSTANCEOF:
                case T_ISSET:
                case T_IS_EQUAL:
                case T_IS_GREATER_OR_EQUAL:
                case T_IS_IDENTICAL:
                case T_IS_NOT_EQUAL:
                case T_IS_NOT_IDENTICAL:
                case T_IS_SMALLER_OR_EQUAL:
                case T_LIST:
                case T_LOGICAL_AND:
                case T_LOGICAL_OR:
                case T_LOGICAL_XOR:
                case T_MINUS_EQUAL:
                case T_MOD_EQUAL:
                case T_MUL_EQUAL:
                case T_NEW:
                case T_OBJECT_OPERATOR:
                case T_OR_EQUAL:
                case T_PLUS_EQUAL:
                case T_PRINT:
                case T_REQUIRE:
                case T_REQUIRE_ONCE:
                case T_RETURN:
                case T_SL:
                case T_SL_EQUAL:
                case T_SR:
                case T_SR_EQUAL:
                case T_SWITCH:
                case T_THROW:
                case T_TRY:
                case T_UNSET:
                case T_UNSET_CAST:
                case T_USE:
                case T_WHILE:
                case T_XOR_EQUAL:
                    $php_lines[$current_line] = true;
                    $end_of_current_expr = false;
                    break;
                default:
                    if (false === $end_of_current_expr) {
                        $php_lines[$current_line] = true;
                    }
            }
        }

        return $php_lines;
    }

    /**
     * @param $content
     * @param $cov
     * @return array
     */
    public function compute($content, $cov)
    {
        $php_lines = self::get_php_lines($content);

        // we remove from $cov non php lines
        foreach (array_diff_key($cov, $php_lines) as $line => $tmp) {
            unset($cov[$line]);
        }

        return array($cov, $php_lines);
    }

    /**
     * @param $lines
     * @return string
     */
    public function format_range($lines)
    {
        sort($lines);
        $formatted = '';
        $first = -1;
        $last = -1;
        foreach ($lines as $line) {
            if ($last + 1 != $line) {
                if ($first != -1) {
                    $formatted .= $first == $last ? "$first " : "[$first - $last] ";
                }
                $first = $line;
                $last = $line;
            } else {
                $last = $line;
            }
        }
        if ($first != -1) {
            $formatted .= $first == $last ? "$first " : "[$first - $last] ";
        }

        return $formatted;
    }
}
