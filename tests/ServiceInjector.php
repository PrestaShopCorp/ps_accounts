<?php

namespace PrestaShop\Module\PsAccounts\Tests;

class ServiceInjector
{
    /**
     * @var mixed
     */
    private $instance;

    /**
     * @var \ReflectionClass
     */
    private $mirror;

    /**
     * @var string[]
     */
    private $uses;

    /**
     * @var \Closure
     */
    private $builder;

    /**
     * @param mixed $instance
     * @param \Closure $builder
     *
     * @throws \ReflectionException
     */
    public function __construct($instance, \Closure $builder)
    {
        $this->instance = $instance;
        $this->builder = $builder;

        $this->mirror = new \ReflectionClass($this->instance);
        $this->uses = $this->extractClassUses($this->mirror);
    }

    /**
     * @param array $services
     *
     * @return void
     */
    public  function buildServices(array $services)
    {
        array_walk($services, function ($class) {
            if (is_array($class)) {
                $propName = array_keys($class)[0];
                $class = $class[$propName];
            } else {
                $propName = $this->lcClassName($class);
            }
            $builder = $this->builder;
            $builder($propName, $class);
        });
    }

    /**
     * @param string $tag
     *
     * @return void
     */
    public function resolveServices($tag = 'inject')
    {
        $props = $this->mirror->getProperties();
        $classes = [];
        foreach ($props as $prop) {
            $tags = $this->extractPropertyTags($prop);
            if (isset($tags[$tag]) && isset($tags['var'])) {
                $class = $this->evalWithUses($tags['var'] . '::class;', 'class', $this->uses);
                $classes[] = [$prop->name => $class];
            }
        }
        $this->buildServices($classes);
    }

    /**
     * @param $className
     *
     * @return string
     */
    public function lcClassName($className)
    {
        return lcfirst(preg_replace('/^.*\\\\/', '', $className));
    }

    /**
     * @param string $expr
     * @param string $name
     * @param array $uses
     *
     * @return mixed
     */
    protected function evalWithUses($expr, $name, array $uses)
    {
        $sep = ";\nuse ";
        eval($sep . implode($sep, $uses) . ";\n" . '$' . $name . '=' . $expr);
        return $$name;
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return array
     */
    protected function extractClassUses(\ReflectionClass $class)
    {
        // FIXME support aliases (as...)
        if (preg_match('/(.*)class/ms', file_get_contents($class->getFileName()), $uses)) {
            if (preg_match_all('/use\s+(\S+);/', $uses[1], $matches)) {
                return $matches[1];
            }
        }

        return [];
    }

    /**
     * @param \ReflectionProperty $prop
     *
     * @return array
     */
    protected function extractPropertyTags(\ReflectionProperty $prop)
    {
        if (preg_match_all('/@([\w\-_0-9]+)\s+([^\s*]+)?/', $prop->getDocComment(), $matches)) {
            return array_combine($matches[1], $matches[2]);
        }
        return [];
    }
}
