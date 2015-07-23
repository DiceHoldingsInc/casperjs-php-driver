<?php

namespace CasperJs\Driver;

/**
 * @author jacopo.nardiello
 */
class OptionsCliBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testWillReturnParamsString()
    {
        $builder = new OptionsCliBuilder();

        $builder->addOption('someOption', 'someValue')
                ->addOption('proxy', '1.1.1.1');

        $this->assertEquals(" --someOption='someValue' --proxy='1.1.1.1'", $builder->build());
    }
}
