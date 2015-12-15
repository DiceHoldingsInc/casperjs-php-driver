<?php

/**
 * This file is part of Work Digital's Data Platform.
 *
 * (c) 2015 Work Digital
 */

namespace CasperJs\Input;


/**
 * Class OptionsCliBuilder
 *
 * @author Jacopo Nardiello <jacopo@workdigital.co.uk>
 */
class OptionsCliBuilder
{
    private $options = [];

    /**
     * @param string $optionName
     * @param string $optionValue
     *
     * @return $this
     **/
    public function addOption($optionName, $optionValue)
    {
        $this->options[$optionName] = $optionValue;

        return $this;
    }

    /**
     * @return string
     **/
    public function build()
    {
        $result = '';

        foreach ($this->options as $optionName => $value) {
            $result .= " --{$optionName}='{$value}'";
        }

        return $result;
    }
}
