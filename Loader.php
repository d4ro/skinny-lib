<?php

namespace Skinny;

use Skinny\DataObject\Store;

/**
 * Description of Loader
 *
 * @author Daro
 */
class Loader {

    const CAPACITY = 100;

    protected $_loaders;
    protected $_names;
    protected $_paths;

    /**
     * Konstruktor klasy
     * @param Store $paths
     */
    public function __construct($paths) {
        $this->_loaders = array();
        $this->_paths = $paths;
    }

    /**
     * Wstawia w kolejkę loaderów podaną instancję loadera z podaną nazwą i priorytetem w kolejce.
     * @param \Skinny\Loader\LoaderInterface $loader
     * @param string $name
     * @param integer $priority
     * @throws \InvalidArgumentException istnieje loader o podanej nazwie w kolejce
     * @throws \OverflowException podany priorytet został umieszczony w kolejce maksymalną ilość razy
     */
    public function putLoader(Loader\LoaderInterface $loader, $name, $priority = 5) {
        if (is_array($loader)) {
            foreach ($loader as $key => $instance) {
                $this->putLoader($instance, $name . $key, $priority);
            }
            return;
        }

        if (isset($this->_names[$name])) {
            throw new \InvalidArgumentException('Loader named "' . $name . '" has already been put.');
        }

        $p = $priority * self::CAPACITY;
        for ($i = $p; $i < $p + self::CAPACITY; $i++) {
            if (!isset($this->_loaders[$i])) {
                $this->_loaders[$i] = $loader;
                $this->_names[$name] = $i;
                return;
            }
        }

        throw new \OverflowException('Loader named "' . $name . '" cannot be put with priority ' . $priority . '. Container is full.');
    }

    /**
     * Rejestruje podany loader o ile nie został wcześniej zarejestrowany.
     * Jeżeli nazwa loadera nie zostanie podana, zostaną zarejestrowane wszystkie niezajestrowane loadery
     * w kolejności dodania do kolejki z uwzględnieniem priorytetu (niższe wartości priorytetu wpierw).
     * @param string $name
     * @throws \InvalidArgumentException loader o podanej nazwie nie został znaleziony
     */
    public function register($name = null) {
        ksort($this->_loaders);

        if (null === $name) {
            foreach ($this->_loaders as $loader) {
                if (!$loader->isRegistered()) {
                    $loader->register();
                }
            }
        } else {
            $names = (array) $name;
            foreach ($names as $loader) {
                if (isset($this->_names[$loader])) {
                    if (!$this->_loaders[$this->_names[$loader]]->isRegistered()) {
                        $this->_loaders[$this->_names[$loader]]->register();
                    }
                } else {
                    throw new \InvalidArgumentException('Loader named "' . $loader . '" has not been put to the container.');
                }
            }
        }
    }

    public function initLoaders($loaders, $priority = 5) {
        $loaders = (array) $loaders;
        /* include_once __DIR__ . '/Loader/Standard.php';
          if (isset($loaders['standard'])) {
          $class = new Loader\Standard($this->_paths, $loaders['standard']);
          unset($loaders['standard']);
          } else {
          $class = new Loader\Standard($this->_paths);
          }
          $this->putLoader($class, 'standard', $priority); */

        foreach ($loaders as $name => $config) {
            switch ($name) {
                case 'standard':
                    $path = 'Loader/Standard.php?\Skinny\Loader\Standard';
                    break;
                case 'namespace':
                    $path = 'Loader/NSpace.php?\Skinny\Loader\NSpace';
                    break;
                case 'prefix':
                    $path = 'Loader/Prefix.php?\Skinny\Loader\Prefix';
                    break;
                default:
                    $path = $name;
                    break;
            }
            $parts = explode('?', $path);
            if ($parts > 1) {
                $file = array_shift($parts);
                require_once $file;
            }
            $class_name = array_shift($parts);
            $class = new $class_name($this->_paths, $config);
            $this->putLoader($class, $name, $priority);
        }
    }

}
