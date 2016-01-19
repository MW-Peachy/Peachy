<?php

/**
 * Unit test library.
 *
 * @package    lime
 * @author     Fabien Potencier <fabien.potencier@gmail.com>
 * @version    SVN: $Id$
 */
class lime_test
{
    const EPSILON = 0.0000000001;

    protected $test_nb = 0;
    protected $output = null;
    protected $results = array();
    protected $options = array();

    static protected $all_results = array();

    /**
     * Constructor for the lime.php class
     *
     * @param null $plan
     * @param array $options
     */
    public function __construct($plan = null, $options = array())
    {
        // for BC
        if (!is_array($options)) {
            $options = array('output' => $options);
        }

        $this->options = array_merge(array(
            'force_colors' => false,
            'output' => null,
            'verbose' => false,
            'error_reporting' => false,
        ), $options);

        $this->output = $this->options['output'] ? $this->options['output'] : new lime_output($this->options['force_colors']);

        $caller = $this->find_caller(debug_backtrace());
        self::$all_results[] = array(
            'file' => $caller[0],
            'tests' => array(),
            'stats' => array(
                'plan' => $plan,
                'total' => 0,
                'failed' => array(),
                'passed' => array(),
                'skipped' => array(),
                'errors' => array()
            ),
        );

        $this->results = &self::$all_results[count(self::$all_results) - 1];

        null !== $plan and $this->output->echoln(sprintf("1..%d", $plan));

        set_error_handler(array($this, 'handle_error'));
        set_exception_handler(array($this, 'handle_exception'));
    }

    static public function reset()
    {
        self::$all_results = array();
    }

    /**
     * @return array
     */
    static public function to_array()
    {
        return self::$all_results;
    }

    /**
     * @param null $results
     * @return string
     */
    static public function to_xml($results = null)
    {
        if (is_null($results)) {
            $results = self::$all_results;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->appendChild($testsuites = $dom->createElement('testsuites'));

        $failures = 0;
        $errors = 0;
        $skipped = 0;
        $assertions = 0;

        foreach ($results as $result) {
            $testsuites->appendChild($testsuite = $dom->createElement('testsuite'));
            $testsuite->setAttribute('name', basename($result['file'], '.php'));
            $testsuite->setAttribute('file', $result['file']);
            $testsuite->setAttribute('failures', count($result['stats']['failed']));
            $testsuite->setAttribute('errors', count($result['stats']['errors']));
            $testsuite->setAttribute('skipped', count($result['stats']['skipped']));
            $testsuite->setAttribute('tests', $result['stats']['plan']);
            $testsuite->setAttribute('assertions', $result['stats']['plan']);

            $failures += count($result['stats']['failed']);
            $errors += count($result['stats']['errors']);
            $skipped += count($result['stats']['skipped']);
            $assertions += $result['stats']['plan'];

            foreach ($result['tests'] as $test) {
                $testsuite->appendChild($testcase = $dom->createElement('testcase'));
                $testcase->setAttribute('name', $test['message']);
                $testcase->setAttribute('file', $test['file']);
                $testcase->setAttribute('line', $test['line']);
                $testcase->setAttribute('assertions', 1);
                if (!$test['status']) {
                    $testcase->appendChild($failure = $dom->createElement('failure'));
                    $failure->setAttribute('type', 'lime');
                    if (isset($test['error'])) {
                        $failure->appendChild($dom->createTextNode($test['error']));
                    }
                }
            }
        }

        $testsuites->setAttribute('failures', $failures);
        $testsuites->setAttribute('errors', $errors);
        $testsuites->setAttribute('tests', $assertions);
        $testsuites->setAttribute('assertions', $assertions);
        $testsuites->setAttribute('skipped', $skipped);

        return $dom->saveXml();
    }

    /**
     *
     */
    public function __destruct()
    {
        $plan = $this->results['stats']['plan'];
        $passed = count($this->results['stats']['passed']);
        $failed = count($this->results['stats']['failed']);
        $total = $this->results['stats']['total'];
        is_null($plan) and $plan = $total and $this->output->echoln(sprintf("1..%d", $plan));

        if ($total > $plan) {
            $this->output->red_bar(sprintf("# Looks like you planned %d tests but ran %d extra.", $plan,
                $total - $plan));
        } elseif ($total < $plan) {
            $this->output->red_bar(sprintf("# Looks like you planned %d tests but only ran %d.", $plan, $total));
        }

        if ($failed) {
            $this->output->red_bar(sprintf("# Looks like you failed %d tests of %d.", $failed, $passed + $failed));
        } else {
            if ($total == $plan) {
                $this->output->green_bar("# Looks like everything went fine.");
            }
        }

        flush();
    }

    /**
     * Tests a condition and passes if it is true
     *
     * @param mixed $exp condition to test
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function ok($exp, $message = '')
    {
        $this->update_stats();

        if ($result = (boolean)$exp) {
            $this->results['stats']['passed'][] = $this->test_nb;
        } else {
            $this->results['stats']['failed'][] = $this->test_nb;
        }
        $this->results['tests'][$this->test_nb]['message'] = $message;
        $this->results['tests'][$this->test_nb]['status'] = $result;
        $this->output->echoln(sprintf("%s %d%s", $result ? 'ok' : 'not ok', $this->test_nb,
            $message = $message ? sprintf('%s %s', 0 === strpos($message, '#') ? '' : ' -', $message) : ''));

        if (!$result) {
            $this->output->diag(sprintf('    Failed test (%s at line %d)',
                str_replace(getcwd(), '.', $this->results['tests'][$this->test_nb]['file']),
                $this->results['tests'][$this->test_nb]['line']));
        }

        return $result;
    }

    /**
     * Compares two values and passes if they are equal (==)
     *
     * @param mixed $exp1 left value
     * @param mixed $exp2 right value
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function is($exp1, $exp2, $message = '')
    {
        if (is_object($exp1) || is_object($exp2)) {
            $value = $exp1 === $exp2;
        } else {
            if (is_float($exp1) && is_float($exp2)) {
                $value = abs($exp1 - $exp2) < self::EPSILON;
            } else {
                $value = $exp1 == $exp2;
            }
        }

        if (!$result = $this->ok($value, $message)) {
            $this->set_last_test_errors(array(
                sprintf("           got: %s", var_export($exp1, true)),
                sprintf("      expected: %s", var_export($exp2, true))
            ));
        }

        return $result;
    }

    /**
     * Compares two values and passes if they are not equal
     *
     * @param mixed $exp1 left value
     * @param mixed $exp2 right value
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function isnt($exp1, $exp2, $message = '')
    {
        if (!$result = $this->ok($exp1 != $exp2, $message)) {
            $this->set_last_test_errors(array(
                sprintf("      %s", var_export($exp2, true)),
                '          ne',
                sprintf("      %s", var_export($exp2, true))
            ));
        }

        return $result;
    }

    /**
     * Tests a string against a regular expression
     *
     * @param string $exp value to test
     * @param string $regex the pattern to search for, as a string
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function like($exp, $regex, $message = '')
    {
        if (!$result = $this->ok(preg_match($regex, $exp), $message)) {
            $this->set_last_test_errors(array(
                sprintf("                    '%s'", $exp),
                sprintf("      doesn't match '%s'", $regex)
            ));
        }

        return $result;
    }

    /**
     * Checks that a string doesn't match a regular expression
     *
     * @param string $exp value to test
     * @param string $regex the pattern to search for, as a string
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function unlike($exp, $regex, $message = '')
    {
        if (!$result = $this->ok(!preg_match($regex, $exp), $message)) {
            $this->set_last_test_errors(array(
                sprintf("               '%s'", $exp),
                sprintf("      matches '%s'", $regex)
            ));
        }

        return $result;
    }

    /**
     * Compares two arguments with an operator
     *
     * @param mixed $exp1 left value
     * @param string $op operator
     * @param mixed $exp2 right value
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function cmp_ok($exp1, $op, $exp2, $message = '')
    {
        $result = false;
        $php = sprintf("\$result = \$exp1 $op \$exp2;");
        // under some unknown conditions the sprintf() call causes a segmentation fault
        // when placed directly in the eval() call
        eval($php);

        if (!$this->ok($result, $message)) {
            $this->set_last_test_errors(array(
                sprintf("      %s", str_replace("\n", '', var_export($exp1, true))),
                sprintf("          %s", $op),
                sprintf("      %s", str_replace("\n", '', var_export($exp2, true)))
            ));
        }

        return $result;
    }

    /**
     * Checks the availability of a method for an object or a class
     *
     * @param mixed $object an object instance or a class name
     * @param string|array $methods one or more method names
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function can_ok($object, $methods, $message = '')
    {
        $result = true;
        $failed_messages = array();
        foreach ((array)$methods as $method) {
            if (!method_exists($object, $method)) {
                $failed_messages[] = sprintf("      method '%s' does not exist", $method);
                $result = false;
            }
        }

        !$this->ok($result, $message);

        !$result and $this->set_last_test_errors($failed_messages);

        return $result;
    }

    /**
     * Checks the type of an argument
     *
     * @param mixed $var variable instance
     * @param string $class class or type name
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function isa_ok($var, $class, $message = '')
    {
        $type = is_object($var) ? get_class($var) : gettype($var);
        if (!$result = $this->ok($type == $class, $message)) {
            $this->set_last_test_errors(array(sprintf("      variable isn't a '%s' it's a '%s'", $class, $type)));
        }

        return $result;
    }

    /**
     * Checks that two arrays have the same values
     *
     * @param mixed $exp1 first variable
     * @param mixed $exp2 second variable
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function is_deeply($exp1, $exp2, $message = '')
    {
        if (!$result = $this->ok($this->test_is_deeply($exp1, $exp2), $message)) {
            $this->set_last_test_errors(array(
                sprintf("           got: %s", str_replace("\n", '', var_export($exp1, true))),
                sprintf("      expected: %s", str_replace("\n", '', var_export($exp2, true)))
            ));
        }

        return $result;
    }

    /**
     * is_ignore_nl function.
     *
     * @access public
     * @param mixed $exp1
     * @param mixed $exp2
     * @param string $message (default: '')
     * @return boolean
     */
    public function is_ignore_nl($exp1, $exp2, $message = '')
    {
        return $this->is(
            str_replace("\n", '', $exp1),
            str_replace("\n", '', $exp2),
            $message
        );
    }

    /**
     * Sortcut for {@link is}(), performs a strict check.
     *
     * @access public
     * @param mixed $exp1
     * @param mixed $exp2
     * @param string $message (default: '')
     * @return boolean
     */
    public function is_strict($exp1, $exp2, $message = '')
    {
        if (is_float($exp1) && is_float($exp2)) {
            $value = abs($exp1 - $exp2) < self::EPSILON;
        } else {
            $value = $exp1 === $exp2;
        }

        if (!$result = $this->ok($value, $message)) {
            $this->set_last_test_errors(array(
                sprintf("           got: (%s) %s", gettype($exp1), var_export($exp1, true)),
                sprintf("      expected: (%s) %s", gettype($exp2), var_export($exp2, true))
            ));
        }

        return $result;
    }

    /**
     * Sortcut for {@link isnt}(), performs a strict check.
     *
     * @access public
     * @param mixed $exp1
     * @param mixed $exp2
     * @param string $message . (default: '')
     * @return boolean
     */
    public function isnt_strict($exp1, $exp2, $message = '')
    {
        if (!$result = $this->ok($exp1 !== $exp2, $message)) {
            $this->set_last_test_errors(array(
                sprintf("      (%s) %s", gettype($exp1), var_export($exp1, true)),
                '          ne',
                sprintf("      (%s) %s", gettype($exp2), var_export($exp2, true))
            ));
        }

        return $result;
    }

    /**
     * Always passes--useful for testing exceptions
     *
     * @param string $message display output message
     *
     * @return boolean
     */
    public function pass($message = '')
    {
        return $this->ok(true, $message);
    }

    /**
     * Always fails--useful for testing exceptions
     *
     * @param string $message display output message
     *
     * @return false
     */
    public function fail($message = '')
    {
        return $this->ok(false, $message);
    }

    /**
     * Outputs a diag message but runs no test
     *
     * @param string $message display output message
     *
     * @return void
     */
    public function diag($message)
    {
        $this->output->diag($message);
    }

    /**
     * Counts as $nb_tests tests--useful for conditional tests
     *
     * @param string $message display output message
     * @param integer $nb_tests number of tests to skip
     *
     * @return void
     */
    public function skip($message = '', $nb_tests = 1)
    {
        for ($i = 0; $i < $nb_tests; $i++) {
            $this->pass(sprintf("# SKIP%s", $message ? ' ' . $message : ''));
            $this->results['stats']['skipped'][] = $this->test_nb;
            array_pop($this->results['stats']['passed']);
        }
    }

    /**
     * Counts as a test--useful for tests yet to be written
     *
     * @param string $message display output message
     *
     * @return void
     */
    public function todo($message = '')
    {
        $this->pass(sprintf("# TODO%s", $message ? ' ' . $message : ''));
        $this->results['stats']['skipped'][] = $this->test_nb;
        array_pop($this->results['stats']['passed']);
    }

    /**
     * Validates that a file exists and that it is properly included
     *
     * @param string $file file path
     * @param string $message display output message when the test passes
     *
     * @return boolean
     */
    public function include_ok($file, $message = '')
    {
        if (!$result = $this->ok((@include($file)) == 1, $message)) {
            $this->set_last_test_errors(array(sprintf("      Tried to include '%s'", $file)));
        }

        return $result;
    }

    /**
     * @param $var1
     * @param $var2
     * @return bool
     */
    private function test_is_deeply($var1, $var2)
    {
        if (gettype($var1) != gettype($var2)) {
            return false;
        }

        if (is_array($var1)) {
            ksort($var1);
            ksort($var2);

            $keys1 = array_keys($var1);
            $keys2 = array_keys($var2);
            if (array_diff($keys1, $keys2) || array_diff($keys2, $keys1)) {
                return false;
            }
            $is_equal = true;
            foreach ($var1 as $key => $value) {
                $is_equal = $this->test_is_deeply($var1[$key], $var2[$key]);
                if ($is_equal === false) {
                    break;
                }
            }

            return $is_equal;
        } else {
            return $var1 === $var2;
        }
    }

    /**
     * @param $message
     */
    public function comment($message)
    {
        $this->output->comment($message);
    }

    /**
     * @param $message
     */
    public function info($message)
    {
        $this->output->info($message);
    }

    /**
     * @param string $message
     */
    public function error($message, $file = null, $line = null, array $traces = array())
    {
        $this->output->error($message, $file, $line, $traces);

        $this->results['stats']['errors'][] = array(
            'message' => $message,
            'file' => $file,
            'line' => $line,
        );
    }

    protected function update_stats()
    {
        ++$this->test_nb;
        ++$this->results['stats']['total'];

        list($this->results['tests'][$this->test_nb]['file'], $this->results['tests'][$this->test_nb]['line']) = $this->find_caller(debug_backtrace());
    }

    /**
     * @param array $errors
     */
    protected function set_last_test_errors(array $errors)
    {
        $this->output->diag($errors);

        $this->results['tests'][$this->test_nb]['error'] = implode("\n", $errors);
    }

    /**
     * @param $traces
     * @return array
     */
    protected function find_caller($traces)
    {
        // find the first call to a method of an object that is an instance of lime_test
        $t = array_reverse($traces);
        foreach ($t as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof lime_test) {
                return array($trace['file'], $trace['line']);
            }
        }

        // return the first call
        $last = count($traces) - 1;

        return array($traces[$last]['file'], $traces[$last]['line']);
    }

    /**
     * @param $code
     * @param $message
     * @param $file
     * @param $line
     * @param $context
     * @return bool
     *
     * @FIXME:  This method has inconsistent return types
     */
    public function handle_error($code, $message, $file, $line, $context)
    {
        if (!$this->options['error_reporting'] || ($code & error_reporting()) == 0) {
            return false;
        }

        switch ($code) {
            case E_WARNING:
                $type = 'Warning';
                break;
            default:
                $type = 'Notice';
                break;
        }

        $trace = debug_backtrace();
        array_shift($trace); // remove the handle_error() call from the trace

        $this->error($type . ': ' . $message, $file, $line, $trace);
    }

    /**
     * @param Exception $exception
     * @return bool
     */
    public function handle_exception(Exception $exception)
    {
        $this->error(get_class($exception) . ': ' . $exception->getMessage(), $exception->getFile(),
            $exception->getLine(), $exception->getTrace());

        // exception was handled
        return true;
    }
}