<?php

namespace MatthewBaggett\UUID;

class UUID
{
    public const LENGTH = 36;

    public static function v3(string $namespace, string $name): ?string
    {
        return self::withNamespace($namespace, $name, 'md5', 3);
    }

    public static function v4(int $seed = null): string
    {
        if ($seed) {
            mt_srand($seed);
        }

        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(
                0,
                0xffff
            ),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(
                0,
                0xffff
            ),
            mt_rand(
                0,
                0xffff
            )
        );
    }

    /**
     * @param array<mixed>|object|string $entity
     *
     * @return string
     */
    public static function v4Hash($entity): ?string
    {
        if (is_object($entity)) {
            if (method_exists($entity, '__toString')) {
                return self::v4HashString($entity->__toString());
            }

            return self::v4HashString(serialize($entity));
        }
        if (is_array($entity)) {
            return self::v4HashString(serialize($entity));
        }
        if (empty($entity)) {
            $entity = '';
        }

        return self::v4HashString($entity);
    }

    public static function v5(string $namespace, string $name): ?string
    {
        return self::withNamespace($namespace, $name, 'sha1', 5);
    }

    public static function isValid(string $uuid): bool
    {
        if (strlen($uuid) != self::LENGTH) {
            return false;
        }

        return preg_match(
            '/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.
            '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i',
            $uuid
        ) === 1;
    }

    /**
     * @param string $namespace
     * @param string $name
     * @param string $crypto
     * @param int    $version
     *
     * @throws UUIDGenerationException
     *
     * @return null|string
     */
    private static function withNamespace(string $namespace, string $name, string $crypto, int $version): ?string
    {
        if (!self::isValid($namespace)) {
            return null;
        }

        // Get hexadecimal components of namespace
        $nhex = str_replace(['-', '{', '}'], '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr((int) hexdec($nhex[$i].$nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = hash($crypto, $nstr.$name);

        switch ($version) {
            case 3:
                $version_byte = 0x3000;

                break;
            case 5:
                $version_byte = 0x5000;

                break;
            default:
                throw new UUIDGenerationException("Version {$version} is not a valid UUID version.");
        }

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            // 32 bits for "time_low"
            substr($hash, 0, 8),
            // 16 bits for "time_mid"
            substr($hash, 8, 4),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 3 or 5
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | $version_byte,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            // 48 bits for "node"
            substr($hash, 20, 12)
        );
    }

    private static function v4HashString(string $string): ?string
    {
        $elements = [];
        if (strlen($string) > 4) {
            $elements = str_split($string, (int) ceil(strlen($string) / 4));
            $elements = array_slice($elements, 0, 4);
            array_walk(
                $elements,
                function (string &$element): void {
                    $element = crc32($element);
                }
            );
            array_unshift($elements, 'phoney');
            unset($elements[0]);
        } else {
            $elements[1] = $string;
            $elements[2] = $string;
            $elements[3] = $string;
            $elements[4] = $string;
        }

        return self::hexToUuid(self::integersToHex($elements));
    }

    //private static function uuidToHex($uuid)
    //{
    //    return str_replace('-', '', $uuid);
    //}

    private static function hexToUuid(string $hex): ?string
    {
        $regex = '/^([\da-f]{8})([\da-f]{4})([\da-f]{4})([\da-f]{4})([\da-f]{12})$/';

        return preg_match($regex, $hex, $matches) ?
        "{$matches[1]}-{$matches[2]}-{$matches[3]}-{$matches[4]}-{$matches[5]}" :
        null;
    }

    /**
     * Return array of 4x 32 bit ints.
     *
     * @param $hex
     *
     * @return array|false
     */
    //private static function hexToIntegers($hex) {
    //    $bin = pack('h*', $hex);
    //    return unpack('L*', $bin);
    //}

    /**
     * @param array<int> $integers
     *
     * @return string
     */
    private static function integersToHex(array $integers): string
    {
        $integers[0] = 'L*';
        ksort($integers);
        $bin = call_user_func_array('pack', $integers);
        $results = unpack('h*', $bin);

        return $results[1];
    }
}
