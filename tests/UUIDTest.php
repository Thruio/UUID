<?php

namespace MatthewBaggett\UUID\Tests;

use Gone\UUID\UUID;
use Gone\UUID\UUIDGenerationException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers   \Gone\UUID\UUID
 */
class UUIDTest extends TestCase
{
    public const UUID_FORMAT = '';
    /**
     * @var mixed
     */
    private $previousEntity;

    /**
     * Tests UUID validation.
     *
     * @param string $uuid
     *                         The uuid to check against
     * @param bool   $is_valid
     *                         Whether the uuid is valid or not
     * @param string $message
     *                         The message to display on failure
     *
     * @dataProvider providerTestValidation
     */
    public function testValidation($uuid, $is_valid, $message): void
    {
        $this->assertSame($is_valid, UUID::isValid($uuid), $message);
    }

    /**
     * Dataprovider for UUID instance tests.
     *
     * @return array<array>
     *                      An array of arrays containing
     *                      - The Uuid to check against.
     *                      - (bool) Whether or not the Uuid is valid.
     *                      - Failure message.
     */
    public function providerTestValidation(): array
    {
        return [
            // These valid UUIDs.
            ['6ba7b810-9dad-11d1-80b4-00c04fd430c8', true, 'Basic FQDN UUID did not validate'],
            ['00000000-0000-0000-0000-000000000000', true, 'Minimum UUID did not validate'],
            ['ffffffff-ffff-ffff-ffff-ffffffffffff', true, 'Maximum UUID did not validate'],
            // These are invalid UUIDs.
            ['0ab26e6b-f074-4e44-9da-601205fa0e976', false, 'Invalid format was validated'],
            ['0ab26e6b-f074-4e44-9daf-1205fa0e9761f', false, 'Invalid length was validated'],
        ];
    }

    public function testGeneratedUUIDv3NotUnique(): void
    {
        $namespace = UUID::v4();
        $name = 'test';
        $this->assertEquals(
            UUID::v3($namespace, $name),
            UUID::v3($namespace, $name),
            'Same UUID v5 was generated twice.'
        );
    }

    public function testGeneratedUUIDv4Unique(): void
    {
        $this->assertNotEquals(UUID::v4(), UUID::v4(), 'Same UUID was not generated twice.');
    }

    public function testGeneratedUUIDv5NotUnique(): void
    {
        $namespace = UUID::v4();
        $name = 'test';
        $this->assertEquals(
            UUID::v5($namespace, $name),
            UUID::v5($namespace, $name),
            'Same UUID v5 was generated twice.'
        );
    }

    public function testGenerateUUIDs(): void
    {
        $namespace = UUID::v4();
        $name = 'test';
        $this->assertTrue(UUID::isValid(UUID::v3($namespace, $name)), 'UUID v3 generation works.');
        $this->assertTrue(UUID::isValid(UUID::v4()), 'UUID v4 generation works.');
        $this->assertTrue(UUID::isValid(UUID::v5($namespace, $name)), 'UUID v5 generation works.');
    }

    public function testGeneratedUUIDNamespaces(): void
    {
        $this->assertNull(UUID::v3('garbage', 'test'));
        $this->assertNull(UUID::v5('garbage', 'test'));
        $this->assertNotNull(UUID::v3('00000000-0000-0000-0000-000000000000', 'test'));
        $this->assertNotNull(UUID::v5('00000000-0000-0000-0000-000000000000', 'test'));
    }

    public function testGeneratedSeededV4(): void
    {
        $this->assertEquals(UUID::v4(19900601), UUID::v4(19900601));
        $this->assertNotEquals(UUID::v4(19900601), UUID::v4());
    }

    public function testGeneratedUUIDsInvalidVersion(): void
    {
        $this->expectException(UUIDGenerationException::class);
        $this->expectErrorMessage('Version 6 is not a valid UUID version.');
        $this->invokeMethod(UUID::class, 'withNamespace', [UUID::v4(), 'test', 'sha1', 6]);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param string       $class      class that we will run method on
     * @param string       $methodName Method name to call
     * @param array<mixed> $parameters array of parameters to pass into method
     *
     * @return mixed method return
     */
    public function invokeMethod(string $class, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($class);

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(new $class(), $parameters);
    }

    /**
     * @return array<array>
     */
    public function providerHashableEntities(): array
    {
        return [
            ['631b6d54-eed1-bed3-749f-422cebaed37b', 'This is a fairly long string.'],
            ['b2337860-ead8-c843-35ca-35787d3fe605', 'This is a different string.'],
            ['720d14db-d02f-1d79-adbf-e29e28a2ad72', ['This', 'is', 'an', 'array']],
            ['ac2cbd34-ad0b-42e1-79c9-c6b64c4fe1e2', ['This', 'is', 'another', 'array']],
            ['78029fcf-1090-48ef-cc59-7e7047238b5e', (object) ['And', 'this', 'is', 'an', 'object']],
            ['9f94de43-e71b-39eb-92dc-2bc177442cca', (object) ['And', 'this', 'is', 'a', 'different', 'object']],
            ['ccd233bd-a0a2-203d-bb71-52366e7ec7be', (new Stringable())->setValue('Here are some words')],
            ['aeeabe2b-9c9b-692e-aad9-7f036e7ec7be', (new Stringable())->setValue('Different set of words')],
        ];
    }

    /**
     * @dataProvider providerHashableEntities
     *
     * @param string $expectedUuid
     * @param mixed  $entity
     */
    public function testHashedUUIDs(string $expectedUuid, $entity): void
    {
        $this->assertTrue(UUID::isValid(UUID::v4Hash($entity)));
        $this->assertEquals(UUID::v4Hash($entity), UUID::v4Hash($entity));
        $this->assertNotEquals(UUID::v4Hash($this->previousEntity), UUID::v4Hash($entity));
        $this->assertEquals($expectedUuid, UUID::v4Hash($entity));
        $this->previousEntity = $entity;
    }
}
