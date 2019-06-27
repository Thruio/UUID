<?php
namespace Gone\UUID;

class UUID
{
    private static function withNamespace($namespace, $name, $crypto, $version)
    {
        if (!self::isValid($namespace)) {
            return false;
        }

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-','{','}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
        }

        // Calculate hash value
        $hash = hash($crypto, $nstr . $name);

        switch ($version) {
            case '3':
                $version_byte = 0x3000;
                break;
            case '5':
                $version_byte = 0x5000;
                break;
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

    public static function v3($namespace, $name)
    {
        return self::withNamespace($namespace, $name, 'md5', 3);
    }

    public static function v4($seed = null)
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

    private static function v4HashString($string)
    {
        $elements = [];
        if (strlen($string) > 4) {
            $elements = str_split($string, ceil(strlen($string) / 4));
            $elements = array_slice($elements, 0, 4);
            array_walk(
                $elements,
                function (&$element) {
                    $element = crc32($element);
                }
            );
            array_unshift($elements, "phoney");
            unset($elements[0]);
        } else {
            $elements[1] = $string;
            $elements[2] = $string;
            $elements[3] = $string;
            $elements[4] = $string;
        }

        return self::hexToUuid(self::integersToHex($elements));
    }

    public static function v4Hash($entity)
    {
        if (is_object($entity)) {
            if (method_exists($entity, "__toString")) {
                return self::v4HashString($entity->__toString());
            }
            return self::v4HashString(serialize($entity));
        }
        if (is_array($entity)) {
            return self::v4HashString(serialize($entity));
        }
        if (empty($entity)) {
            $entity = "";
        }
        return self::v4HashString($entity);
    }

    public static function v5($namespace, $name)
    {
        return self::withNamespace($namespace, $name, 'sha1', 5);
    }

    public static function isValid($uuid)
    {
        return preg_match(
            '/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.
            '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i',
            $uuid
        ) === 1;
    }

    //private static function uuidToHex($uuid)
    //{
    //    return str_replace('-', '', $uuid);
    //}

    private static function hexToUuid($hex)
    {
        $regex = '/^([\da-f]{8})([\da-f]{4})([\da-f]{4})([\da-f]{4})([\da-f]{12})$/';
        return preg_match($regex, $hex, $matches) ?
        "{$matches[1]}-{$matches[2]}-{$matches[3]}-{$matches[4]}-{$matches[5]}" :
        false;
    }

    /**
     * Return array of 4x 32 bit ints
     *
     * @param  $hex
     * @return array|false
     */
    //private static function hexToIntegers($hex) {
    //    $bin = pack('h*', $hex);
    //    return unpack('L*', $bin);
    //}

    private static function integersToHex(array $integers)
    {
        $args = $integers;
        $args[0] = 'L*';
        ksort($args);
        $bin = call_user_func_array('pack', $args);
        $results = unpack('h*', $bin);
        return $results[1];
    }
}
