<?php

namespace FuseSource\Tests\Functional;

use FuseSource\Stomp\Exception\StompException;
use FuseSource\Stomp\Frame;
use PHPUnit_Framework_TestCase;
/**
 *
 * Copyright 2005-2006 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Stomp test case.
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConnectionTest extends PHPUnit_Framework_TestCase
{
    function testReadFrameThrowsExceptionIfStreamIsBroken()
    {
        $connection = $this->getMockBuilder('\FuseSource\Stomp\Connection')
            ->setMethods(array('hasDataToRead', '_connect'))
            ->setConstructorArgs(array('tcp://host'))
            ->getMock();

        $fp = tmpfile();

        $connection->expects($this->once())->method('_connect')->will($this->returnValue($fp));
        $connection->expects($this->once())->method('hasDataToRead')->will($this->returnValue(true));

        $connection->connect();
        fclose($fp);
        try {
            $connection->readFrame();
            $this->fail('Expected a exception!');
        } catch (StompException $excpetion) {
            $this->assertEquals('Was not possible to read frame.', $excpetion->getMessage());
        }
    }

    function testReadFrameThrowsExceptionIfErrorFrameIsReceived()
    {
        $connection = $this->getMockBuilder('\FuseSource\Stomp\Connection')
            ->setMethods(array('hasDataToRead', '_connect'))
            ->setConstructorArgs(array('tcp://host'))
            ->getMock();

        $fp = tmpfile();

        fwrite($fp, "ERROR\nmessage:stomp-err-info\n\nbody\x00");
        fseek($fp, 0);

        $connection->expects($this->once())->method('_connect')->will($this->returnValue($fp));
        $connection->expects($this->once())->method('hasDataToRead')->will($this->returnValue(true));

        $connection->connect();

        try {
            $connection->readFrame();
            $this->fail('Expected a exception!');
        } catch (StompException $excpetion) {
            $this->assertEquals('stomp-err-info', $excpetion->getMessage());
        }
        fclose($fp);
    }


    function testWriteFrameThrowsExceptionIfConnectionIsBroken()
    {
        $connection = $this->getMockBuilder('\FuseSource\Stomp\Connection')
            ->setMethods(array('_connect'))
            ->setConstructorArgs(array('tcp://host'))
            ->getMock();



        $name = tempnam(sys_get_temp_dir(), 'stomp');
        $fp = fopen($name, 'r');

        $connection->expects($this->once())->method('_connect')->will($this->returnValue($fp));

        $connection->connect();

        try {
            $connection->writeFrame(new Frame('TEST'));
            $this->fail('Expected a exception!');
        } catch (StompException $excpetion) {
            $this->assertEquals('Was not possible to write frame!', $excpetion->getMessage());
        }
        fclose($fp);
    }
}