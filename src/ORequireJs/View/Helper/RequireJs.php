<?php

/*
 */

namespace ORequireJs\View\Helper;

use RuntimeException;
use WmMain\View\Helper\RequireJs\Requirement;
use Zend\View\Helper\Placeholder\Container\AbstractStandalone as Container;

class RequireJs extends Container
{

    /**
     * Whether or not a capture has already been started
     *
     * @var bool
     */
    protected $captureStarted = false;

    /**
     * Name or names to capture
     *
     * @var string|array
     */
    protected $captureNameOrNames;

    /**
     * Type of capture (prepend, append)
     *
     * @var string
     */
    protected $captureType;
    protected $dependencies = [];
    
    public function __invoke()
    {
        return $this;
    }

    /**
     * Append a requirement
     *
     * @param string|array $nameOrNames
     * @param string $callback JavaScript callback for the requirement
     * @return self
     */
    public function append($nameOrNames, $dependencies = null, $callback = null)
    {
        return $this->add($nameOrNames, $dependencies, $callback);
    }

    /**
     * 
     * @param type $nameOrNames
     * @param type $dependencies
     * @param type $callback
     * @return type
     */
    public function push($nameOrNames, $dependencies = null, $callback = null)
    {
        return $this->add($nameOrNames, $dependencies, $callback);
    }

    /**
     * 
     * @param string|array $nameOrNames
     * @param string|array $dependencies
     * @param string $callback
     * @return \WmMain\View\Helper\RequireJs
     */
    protected function add($nameOrNames, $dependencies = null, $callback = null)
    {
        $requirement = new RequireJs\Requirement($nameOrNames, $callback);
        if ($dependencies !== null) {
            $this->addDependencies($nameOrNames, $dependencies);
        }
        $this->getContainer()->append($requirement);

        return $this;
    }

    public function addDependency($name, $dependencies)
    {
        if (!isset($this->dependencies[$name])) {
            $this->dependencies[$name] = [];
        }
        $this->dependencies[$name] = array_merge($this->dependencies[$name],
            (array) $dependencies);
    }

    public function addDependencies($nameOrNames, $dependencies)
    {
        if (is_array($nameOrNames) || $nameOrNames instanceof \Traversable) {
            foreach ($nameOrNames as $name) {
                $this->addDependency($name, $dependencies);
            }
        } else {
            $this->addDependency($nameOrNames, $dependencies);
        }
    }

    /**
     * Prepend a requirement
     *
     * @param string|array $nameOrNames
     * @param string $callback JavaScript callback for the requirement
     * @return self
     * @deprecated does not make sense
     */
    public function prepend($nameOrNames, $dependencies = null, $callback = null)
    {
        $requirement = new RequireJs\Requirement($nameOrNames, $callback);
        if ($dependencies !== null) {
            $this->addDependencies($nameOrNames, $dependencies);
        }
        $this->getContainer()->prepend($requirement);

        return $this;
    }

    /**
     * Begin capturing the JavaScript requirement callback to append later
     *
     * @param string|array $nameOrNames
     */
    public function appendAndCaptureCallback($nameOrNames, $dependencies = null)
    {
        return $this->addAndCaptureCallback($nameOrNames, $dependencies);
    }

    /**
     * 
     * @param array|string $nameOrNames
     * @param array|string $dependencies
     */
    public function pushAndCaptureCallback($nameOrNames, $dependencies = null)
    {
        return $this->addAndCaptureCallback($nameOrNames, $dependencies);
    }

    /**
     * Begin capturing the JavaScript requirement callback to append later
     *
     * @param string|array $nameOrNames
     */
    protected function addAndCaptureCallback($nameOrNames, $dependencies = null)
    {
        if ($dependencies !== null) {
            $this->addDependencies($nameOrNames, $dependencies);
        }

        if ($this->captureStarted) {
            throw new RuntimeException('Cannot nest requirejs callback captures');
        }

        $this->captureNameOrNames = $nameOrNames;
        $this->captureType        = 'append';
        ob_start();
        $this->captureStarted     = true;
    }

    /**
     * Stop capturing
     */
    public function stopCapture()
    {
        if (!$this->captureStarted) {
            return;
        }

        if (null === $this->captureNameOrNames) {
            throw new RuntimeException('Capture detected, but no name present; cannot proceed');
        }

        $callback             = ob_get_clean();
        $this->captureStarted = false;

        $callback = trim($callback);
        if (empty($callback)) {
            $callback = null;
        }

        switch ($this->captureType) {
            case 'prepend':
                $this->prepend($this->captureNameOrNames, $callback);
                break;
            case 'append':
            default:
                $this->append($this->captureNameOrNames, $callback);
                break;
        }
        $this->captureNameOrNames = null;
        $this->captureType        = null;
    }

    protected $map = [];

    /**
     * 
     * @param type $key
     * @param type $value
     * @param type $scope
     * @return \WmMain\View\Helper\RequireJs
     */
    public function map()
    {
        $args = func_get_args();
        if (count($args) === 3) {
            list($key, $value, $scope) = $args;
        } elseif (count($args) === 2) {
            list($arrayOrKey, $valueOrScope) = $args;
            if (is_array($arrayOrKey)) {
                list($map, $scope) = $args;
            } else {
                list($key, $value) = $args;
                $scope = '*';
                $map   = [
                    $key => $value,
                ];
            }
        } elseif (count($args) === 1) {
            $scope = '*';
            list($map) = $args;
        } else {
            /**
             * Nothing to map
             */
            return $this;
        }
        if (!isset($this->map[$scope])) {
            $this->map[$scope] = [];
        }

        $this->map[$scope] = array_merge($this->map[$scope], $map);
        return $this;
    }

    protected $paths = [];

    /**
     * 
     * @param type $key
     * @param type $value
     * @param type $scope
     * @return \WmMain\View\Helper\RequireJs
     */
    public function paths()
    {
        $args = func_get_args();
        if (count($args) === 2) {
            list($key, $value) = $args;
            if (is_array($key)) {
                throw new \Exception('Unsupported key');
            } else {
                list($key, $value) = $args;
                $paths   = [
                    $key => $value,
                ];
            }
        } elseif (count($args) === 1) {
            list($paths) = $args;
        } else {
            /**
             * Nothing to map
             */
            return $this;
        }
        if (!isset($this->paths)) {
            $this->paths = [];
        }

        $this->paths = array_merge($this->paths, $paths);
        return $this;
    }

    /**
     * Cast to string
     *
     * @return string
     */
    public function toString()
    {
        $script = [];
        $config = [
            'shim' => [
            ],
        ];

        if (!empty($this->map)) {
            $config = array_merge($config,
                [
                'map' => $this->map
            ]);
        }
        if (!empty($this->paths)) {
            $config = array_merge($config, [
                'paths' => $this->paths
            ]);
        }

        if (count($this->dependencies)) {
            foreach ($this->dependencies as $resourceName => $dependent) {
                if (!is_array($dependent)) {
                    $dependent = [$dependent];
                }
                $config['shim'][$resourceName] = ['deps' => array_unique($dependent)];
            }
        }

        $requirements = [];
        $script       = [];

        foreach ($this->getContainer() as $require) {
            /* @var $require Requirement|AbstractContainer */
            if (!$require instanceof RequireJs\Requirement) {
                continue;
            }
            if ($require->getCallback() === null) {
                $requirements = array_merge($requirements, $require->getNames());
            } else {
                $name     = $this->formatName($require->getName());
                $script[] = sprintf('require([%s],%s);', $name,
                    $require->getCallback());
            }
        }
        $script[] = sprintf('require([%s]);',
            $this->formatName(array_unique($requirements)));

        $script = implode(PHP_EOL, $script);

        if (count($this->dependencies)) {
            $script = sprintf('require.config(%s);',
                    \Zend\Json\Json::prettyPrint(\Zend\Json\Json::encode($config),
                        array("indent" => " "))) . '
            ' . $script;
        }
//        $script = sprintf('try{%s}catch(e){}',$script);
        $result = sprintf("<script>\n%s\n</script>", $script);
        $this->cleanup();
        return $result;
    }

    protected function cleanup()
    {
        $this->map          = [];
        $this->dependencies = [];
        $this->deleteContainer();
    }

    /**
     * Format the requirement name
     *
     * @param string|array $name
     * @return string
     */
    protected function formatName($name)
    {
        if (is_string($name)) {
            return sprintf('"%s"', $name);
        }
        $names = [];
        foreach ($name as $module) {
            if (!is_string($module)) {
                continue;
            }
            $names[] = sprintf('"%s"', $module);
        }
        return implode(', ', $names);
    }

    /**
     * Is the specified requirement a duplicate?
     *
     * @param  RequireJs\Requirement $requirement Name of file to check
     * @return bool
     */
    protected function isDuplicate(RequireJs\Requirement $requirement)
    {
        foreach ($this->getContainer() as $item) {
            if ($requirement->getName() == $item->getName()) {
                return true;
            }
        }

        return false;
    }

}
