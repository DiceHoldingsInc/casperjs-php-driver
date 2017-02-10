<?php

/**
 * @author jacopo.nardiello
 */
class DriverTest extends \PHPUnit_Framework_TestCase
{
    /** @var Casperjs\Driver */
    protected $driver;

    public function setUp()
    {
        $this->driver = new CasperJs\Driver();
    }

    /**
     * @expectedException \Exception
     */
    public function testDriverThrowExceptionOnInvalidCommand()
    {
        $driver = new CasperJs\Driver('unexistantCommand');
    }

    public function testDriverWillLoadSimplePage()
    {
        $fixturePath = 'file://' . __DIR__ . '/fixtures/simpleHtml.html';
        $output = $this->driver->start($fixturePath)
                               ->run();

        $this->assertInstanceOf('\\CasperJs\\Output', $output);
        $this->assertContains('Pizza with ketchup', $output->getHtml());
    }


    public function testDriverShouldUseProxy()
    {
        $driver = $this->getMockBuilder('CasperJs\Driver')
                       ->setMethods(['addOption'])
                       ->getMock();
        $driver->expects($this->atLeastOnce())
               ->method('addOption');

        $output = $driver->start('file://' . __DIR__ . '/fixtures/simpleHtml.html')
                         ->useProxy('1.1.1.1')
                         ->run();
    }

    public function testBrowserInteractionIsBuiltProperly()
    {
        $expected = "
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug',
  colorizerType: 'Dummy'
});

casper.userAgent('AmericanPizzaiolo');
casper.page.customHeaders = {
    'Accept-Language': 'en-US',
    'Some-Empty-Header': '',
    'Some-Header': 'Foo-bar'
};
casper.then(function() {
    casper.evaluate(function() {
        make me a pizza
    });
});
casper.options.stepTimeout = 1000;

casper.options.onResourceRequested = function(casper, requestData, request) {
    if ((/\.css($|\?v=[a-z0-9]+$)/gi).test(requestData.url)) {
        console.log(\"SKIPPING \" + requestData.url);
        request.abort();
    }
}

casper.options.pageSettings.loadImages = false;

casper.options.pageSettings.loadPlugins = false;

casper.then(function () {
    this.viewport(1024, 768);
});
casper.waitForSelector(
    '.selector',
    function () {
        this.echo('found selector \".selector\"');
    },
    function () {
        this.echo('" . CasperJs\Output::TAG_TIMEOUT . " $(.selector) not found after 30000 ms');
    },
    30000
);
casper.wait(
    10000,
    function () {
        this.echo('" . CasperJs\Output::TAG_TIMEOUT . " after waiting 10000 ms');
    }
);
casper.then(function() {
    this.click('.selector');
});";

        $this->driver->setUserAgent('AmericanPizzaiolo')
                     ->setHeaders([
                         'Accept-Language' => ['en-US'],
                         'Some-Empty-Header' => '',
                         'Some-Header' => 'Foo-bar',
                     ])
                     ->evaluate('make me a pizza')
                     ->setStepTimeout(1000)
                     ->setRequestsToSkip(['/\.css($|\?v=[a-z0-9]+$)/gi'])
                     ->disableImageLoading()
                     ->disablePluginLoading()
                     ->setViewPort(1024, 768)
                     ->waitForSelector('.selector', 30000)
                     ->wait(10000)
                     ->click('.selector');

        $this->assertEquals($expected, $this->driver->getScript());
    }

    /**
     * @expectedException \Exception
     */
    public function testCaptureWillThrowExceptionIfFileNotWritable()
    {
        $this->driver->capture('/path/to/file/that/doesnt/exists');
    }

    /**
     * @expectedException \Exception
     */
    public function testCaptureWillThrowExceptionIfFileIsDirectory()
    {
        $this->driver->capture(sys_get_temp_dir());
    }

    /**
     * We must ignore the gzip encoding as this is not currently supported by casperjs/phantomjs. Might be supported
     * with phantom2.0 and the new version of casperjs.
     */
    public function testDriverShouldIgnoreAcceptEncodingHeader()
    {
        $inputHeaders = [
            'Accept-Language' => ['en-US'],
            'Some-Header' => 'Foo-bar',
            'Accept-Encoding' => 'gzip,deflate,sdch',
        ];
        $expectedScript = "
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug',
  colorizerType: 'Dummy'
});


casper.page.customHeaders = {
    'Accept-Language': 'en-US',
    'Some-Header': 'Foo-bar'
};";
        $this->driver->setHeaders($inputHeaders);

        $this->assertEquals($expectedScript, $this->driver->getScript());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage [TIMEOUT] $(.some-non-existent-selector) not found after 100 ms
     */
    public function testDriverShouldThrowExceptionWhenTimingOut()
    {
        $output = $this->driver->start('file://' . __DIR__ . '/fixtures/simpleHtml.html')
                               ->waitForSelector('.some-non-existent-selector', 100)
                               ->run();
    }

    public function testCaptureGeneratesAnImage()
    {
        $path = sys_get_temp_dir() . '/casper-test-image.png';
        $output = $this->driver->start('file://' . __DIR__ . '/fixtures/simpleHtml.html')
            ->capture($path)
            ->run();
        $this->assertFileExists($path);

        unlink($path);
    }

    public function testInteractionWithAcceptLanguage()
    {
        $expected = "
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug',
  colorizerType: 'Dummy'
});


casper.page.customHeaders = {
    'Accept-Language': 'en-GB'
};";

        $this->driver->setAcceptLanguage(['en-GB']);
        $this->assertEquals($expected, $this->driver->getScript());
    }

    public function testInteractionWithClick()
    {
        $expected = "
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug',
  colorizerType: 'Dummy'
});


casper.then(function() {
    this.click('#mySelector');
});";

        $this->driver->click('#mySelector');
        $this->assertEquals($expected, $this->driver->getScript());
    }

    public function testInteractionWithXpathClick()
    {
        $expected = "
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug',
  colorizerType: 'Dummy'
});


var x = require('casper').selectXPath;

casper.then(function() {
    this.click(x('//div[@id=\"mySelector\"]'));
});";

        $this->driver->clickXpath('//div[@id="mySelector"]');
        $this->assertEquals($expected, $this->driver->getScript());
    }
}
