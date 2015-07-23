<?php

namespace CasperJs\Driver;

/**
 * @author jacopo.nardiello
 */
class DriverTest extends \PHPUnit_Framework_TestCase
{
    public function testDriverWillLoadSimplePage()
    {
        $driver = new CasperJsDriver();
        
        $output = $driver->run('file://' . __DIR__ . '/fixtures/simpleHtml.html');
        $this->assertContains('Pizza with ketchup', $output);
    }
}
