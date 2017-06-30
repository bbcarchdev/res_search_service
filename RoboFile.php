<?php
/*
 * Copyright 2017 BBC
 *
 * Author: Elliot Smith <elliot.smith@bbc.co.uk>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use \Robo\Tasks as RoboTasks;

class RoboFile extends RoboTasks
{
    private function getPHPUnitTask()
    {
        return $this->taskExec(__DIR__ . '/vendor/bin/phpunit')
                    ->option('bootstrap', './vendor/autoload.php')
                    ->arg('tests/unit');
    }

    function test()
    {
        $this->getPHPUnitTask()
             ->run();
    }

    function cov()
    {
        $this->getPHPUnitTask()
             ->option('whitelist', 'lib')
             ->option('coverage-html', 'build/cov')
             ->run();

        $this->say("Coverage report is in build/cov/index.html");
    }

    function server()
    {
        $this->taskServer(8888)
             ->dir('.')
             ->run();
    }
}
