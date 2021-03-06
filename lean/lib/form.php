<?php
namespace lean;

/**
 * HTML form abstraction class
 *
 * getErrors and isValid will trigger a validation once.
 * If different data is populated after, revalidate needs to be called to get correct results
 */
use vitamin\util\Dump;

class Form {

    /**
     * Request method get
     */
    const METHOD_GET = 'get';

    /**
     * Request method post
     */
    const METHOD_POST = 'post';

    /**
     * @var string name of the form
     */
    protected $name;

    /**
     * @var array instances of form\Element
     */
    private $elements = array();

    /**
     * Form action attribute
     *
     * @var string
     */
    private $action = '';

    /**
     * Form method attribute
     *
     * @var string
     */
    private $method = self::METHOD_POST;

    /**
     * @var null|bool
     */
    private $isValid = null;

    /**
     * Default. Everything is encoded
     */
    const ENCTYPE_URLENCODED = 'application/x-www-form-urlencoded';
    /**
     * Like default but with file upload possibility
     */
    const ENCTYPE_FORM = 'multipart/form-data';
    /**
     * Plain text, only spaces are encoded
     */
    const ENCTYPE_PLAIN = 'text/plain';
    const ENCTYPE_DEFAULT = self::ENCTYPE_URLENCODED;

    /**
     * Validation error messages
     *
     * @var array
     */
    private $errors = null;

    /**
     * Encoding type for the form
     *
     * @var string
     */
    private $enctype = 'application/x-www-form-urlencoded';

    /**+
     * @param $name string
     */
    public function __construct($name) {
        $this->name = $name;
        $this->init();
    }

    /**
     * Initialize form elements in descending classes
     */
    protected function init() {

    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getId() {
        return 'form_' . $this->getName();
    }

    /**
     * Set the request method
     *
     * @param string $method
     *
     * @return Form
     */
    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @param string $action
     * @return \lean\Form
     */
    public function setAction($action) {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Add an element to the form
     *
     * @param form\Element $element
     * @return \lean\form\Element
     */
    public function addElement(form\Element $element) {
        $this->elements[$element->getName()] = $element;
        $element->setId($this->name . '_' . $element->getName());
        return $element;
    }

    /**
     * Remove an element from this form
     *
     * @param string $name
     * @return Form
     */
    public function removeElement($name) {
        unset($this->elements[$name]);
        return $this;
    }

    /**
     * @return array
     */
    public function getElements() {
        return $this->elements;
    }

    /**
     * Get an element or null if not existent
     *
     * @param $name
     *
     * @return form\Element|null
     */
    public function getElement($name) {
        return array_key_exists($name, $this->elements)
            ? $this->elements[$name]
            : null;
    }

    /**
     * Magic form element getter
     *
     * @magic
     * @param $name
     * @return form\Element|null
     */
    public function __get($name) {
        return $this->getElement($name);
    }

    /**
     * Populate an array of data to the elements
     *
     * @param array $data
     * @param bool  $names
     */
    public function populate(array $data, $names = false) {
        foreach ($this->elements as $element) {
            if ($names) {
                // keys by name
                if (array_key_exists($element->getName(), $data)) {
                    $element->setValue($data[$element->getName()]);
                }
                else {
                    $element->setValue(null);
                }
            }
            else {
                // keys by id
                if (array_key_exists($element->getId(), $data)) {
                    $element->setValue($data[$element->getId()]);
                }
                else {
                    $element->setValue(null);
                }
            }
        }
    }

    public function open() {
        printf('<form id="%s" action="%s" method="%s" enctype="%s">', $this->getId(), $this->action, $this->method, $this->enctype);
    }

    public function close() {
        echo '</form>';
    }

    /**
     * Display an element
     *
     * @param $name string
     */
    public function display($name) {
        if (!($element = $this->getElement($name))) {
            throw new Exception("Element '$name' not found in form '{$this->name}'");
        }
        $element->display();
    }

    public function displayLabel($name, $elementClasses = true, $attributes = array()) {
        if (!($element = $this->getElement($name))) {
            throw new Exception("Element '$name' not found in form '{$this->name}'");
        }
        $element->displayLabel($elementClasses, $attributes);
    }

    /**
     * Tell if the form is valid. Validate if not done before
     *
     * @return bool|null
     */
    public function isValid() {
        if ($this->isValid === null) {
            $this->isValid = $this->validate();
        }
        return $this->isValid;
    }

    /**
     * Return if every element of the form is valid
     * Fill errors array with element's errors
     *
     * @param array $errors
     * @return bool
     */
    protected function validate() {
        $valid = true;
        foreach ($this->elements as $name => $element) {
            $elementErrors = array();
            if (!$element->isValid($elementErrors)) {
                $this->setErrors($name, $elementErrors);
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * @param       $element
     * @param array $elementErrors
     */
    protected function setErrors($element, array $elementErrors) {
        $this->errors[$element] = $elementErrors;
    }

    /**
     * Revalidate the form
     * Must be called to get correct isValid and errors after repopulating data
     *
     * @return bool
     */
    public function revalidate() {
        return $this->validate();
    }

    /**
     * Set the encoding type for the form
     *
     * @param $type
     */
    public function setEnctype($type) {
        $this->enctype = $type;
    }

    /**
     * Get the encoding type for the form
     */
    public function getEnctype() {
        return $this->enctype;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors() {
        if ($this->errors === null) {
            $this->validate();
        }
        return $this->errors;
    }

    /**
     * Get values of all elements
     *
     * @return array
     */
    public function getData($prefix = true) {
        $data = array();
        $name = $this->getName();
        foreach ($this->elements as $element) {
            $key = $prefix ? $element->getId() : $element->getName();
            $data[$key] = $element->getValue();
        }
        return $data;
    }
}
