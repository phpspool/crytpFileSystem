<?php

namespace Tests\Feature;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Spool\Formwork\DataObjects\Timer;
use Symfony\Component\Yaml\Yaml;

class ConfigTest extends TestCase
{
    public function testConfig()
    {
        $configFile = \CONFIG_PATH . 'db.yml';
        $config = Yaml::parseFile($configFile);
        var_dump($config);
        $this->assertTrue(true);
    }
    public function testTimer()
    {
        $timer = new Timer();
        \usleep(10);
        $lost = $timer->getLost();
        echo "\nLost is: {$lost}\n";
        $timer->reset();
        \usleep(20);
        $lost = $timer->getLost();
        echo "\nLost is: {$lost}\n";
        $this->assertTrue(true);
    }
}
