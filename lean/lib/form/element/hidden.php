<?php
namespace lean\form\element;

/**
 * input[type="text"] form element
 */ class Hidden extends \lean\form\Element {

    /**
     * @return Text
     */
    public function display() {
        printf('<input %1$s type="hidden" name="%2$s" id="%2$s" value="%3$s">', $this->getAttributeString(), $this->getId(), htmlspecialchars($this->getValue()));
        return $this;
    }
}