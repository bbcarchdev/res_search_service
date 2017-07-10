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

use PHPUnit\Framework\TestCase;

use res\libres\RESLicence;

final class RESLicenceTest extends TestCase
{
    public function testLicenceShortForm()
    {
        $licenceUri = 'https://creativecommons.org/licenses/by/2.5/';
        $expected = 'CC BY 2.5';
        $actual = RESLicence::getShortForm($licenceUri);
        $this->assertEquals($expected, $actual);
    }

    public function testLicenceShortFormNoMatch()
    {
        $licenceUri = 'http://foo.bar/licence1';
        $expected = '';
        $actual = RESLicence::getShortForm($licenceUri);
        $this->assertEquals($expected, $actual);
    }
}
