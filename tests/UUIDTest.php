<?php
namespace Gone\UUID\Tests;

use Gone\UUID\UUID;

class UUIDTest extends \PHPUnit_Framework_TestCase
{
    const UUID_FORMAT = "";
    private $previousEntity;

    /**
     * Tests UUID validation.
     *
     * @param string $uuid
     *   The uuid to check against.
     * @param bool   $is_valid
     *   Whether the uuid is valid or not.
     * @param string $message
     *   The message to display on failure.
     *
     * @dataProvider providerTestValidation
     */
    public function testValidation($uuid, $is_valid, $message)
    {
        $this->assertSame($is_valid, UUID::isValid($uuid), $message);
    }

    /**
     * Dataprovider for UUID instance tests.
     *
     * @return array
     *  An array of arrays containing
     *   - The Uuid to check against.
     *   - (bool) Whether or not the Uuid is valid.
     *   - Failure message.
     */
    public function providerTestValidation()
    {
        return array(
        // These valid UUIDs.
        array('6ba7b810-9dad-11d1-80b4-00c04fd430c8', true, 'Basic FQDN UUID did not validate'),
        array('00000000-0000-0000-0000-000000000000', true, 'Minimum UUID did not validate'),
        array('ffffffff-ffff-ffff-ffff-ffffffffffff', true, 'Maximum UUID did not validate'),
        // These are invalid UUIDs.
        array('0ab26e6b-f074-4e44-9da-601205fa0e976', false, 'Invalid format was validated'),
        array('0ab26e6b-f074-4e44-9daf-1205fa0e9761f', false, 'Invalid length was validated'),
        );
    }

    public function testGeneratedUUIDv3NotUnique()
    {
        $namespace = UUID::v4();
        $name = "test";
        $this->assertEquals(UUID::v3($namespace, $name), UUID::v3($namespace, $name), 'Same UUID v5 was generated twice.');
    }

    public function testGeneratedUUIDv4Unique()
    {
        $this->assertNotEquals(UUID::v4(), UUID::v4(), 'Same UUID was not generated twice.');
    }

    public function testGeneratedUUIDv5NotUnique()
    {
        $namespace = UUID::v4();
        $name = "test";
        $this->assertEquals(UUID::v5($namespace, $name), UUID::v5($namespace, $name), 'Same UUID v5 was generated twice.');
    }

    public function testGenerateUUIDs()
    {
        $namespace = UUID::v4();
        $name = "test";
        $this->assertTrue(UUID::isValid(UUID::v3($namespace, $name)), 'UUID v3 generation works.');
        $this->assertTrue(UUID::isValid(UUID::v4()), 'UUID v4 generation works.');
        $this->assertTrue(UUID::isValid(UUID::v5($namespace, $name)), 'UUID v5 generation works.');
    }

    public function testGeneratedUUIDNamespaces()
    {
        $this->assertFalse(UUID::v3("garbage", "test"));
        $this->assertFalse(UUID::v5("garbage", "test"));
        $this->assertNotFalse(UUID::v3("00000000-0000-0000-0000-000000000000", "test"));
        $this->assertNotFalse(UUID::v5("00000000-0000-0000-0000-000000000000", "test"));
    }

    public function testGeneratedSeededV4()
    {
        $this->assertEquals(UUID::v4(19900601), UUID::v4(19900601));
        $this->assertNotEquals(UUID::v4(19900601), UUID::v4());
    }

    public function providerHashableEntities()
    {
        return array(
        array("631b6d54-eed1-bed3-749f-422cebaed37b", "This is a fairly long string."),
        array("b2337860-ead8-c843-35ca-35787d3fe605", "This is a different string."),
        array("720d14db-d02f-1d79-adbf-e29e28a2ad72", array("This", "is", "an", "array")),
        array("ac2cbd34-ad0b-42e1-79c9-c6b64c4fe1e2", array("This", "is", "another", "array")),
        array("38a20287-3c5f-241b-f1ea-c41f6ba33e58", (object) array("And", "this", "is", "an", "object")),
        array("ddb001b1-85fb-df93-bbf3-a59a3ba48b9b", (object) array("And", "this", "is", "a", "different", "object")),
        array("ccd233bd-a0a2-203d-bb71-52366e7ec7be", (new Stringable())->setValue("Here are some words")),
        array("aeeabe2b-9c9b-692e-aad9-7f036e7ec7be", (new Stringable())->setValue("Different set of words")),
        );
    }

    /**
     * @dataProvider providerHashableEntities
     */
    public function testHashedUUIDs($expectedUuid, $entity)
    {
        $this->assertTrue(UUID::isValid(UUID::v4Hash($entity)));
        $this->assertEquals(UUID::v4Hash($entity), UUID::v4Hash($entity));
        $this->assertNotEquals(UUID::v4Hash($this->previousEntity), UUID::v4Hash($entity));
        $this->assertEquals($expectedUuid, UUID::v4Hash($entity));
        $this->previousEntity = $entity;
    }
}
