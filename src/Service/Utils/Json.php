<?php

declare(strict_types=1);

namespace Directive\Service\Utils;

use Directive\Exception\UtilsException;

final class Json
{
    /**
     * @return array<mixed>
     * @throws UtilsException
     */
    public function convertStringToArray(string $json, string $replace = '\\\\'): array
    {
        $result = json_decode(str_replace('\\\\', $replace, $json), true);
        $this->checkError(__METHOD__);
        if (!is_array($result)) {
            throw new UtilsException(__METHOD__ . '(): decoded value is not an array');
        }
        return $result;
    }

    /**
     * @throws UtilsException
     */
    public function convertStringToObject(string $json, string $replace = '\\\\'): mixed
    {
        $result = json_decode(str_replace('\\\\', $replace, $json));
        $this->checkError(__METHOD__);
        return $result;
    }

    /**
     * @param array<mixed> $arr
     * @throws UtilsException
     */
    public function convertArrayToString(array $arr): string
    {
        $result = json_encode($arr);
        $this->checkError(__METHOD__);
        return $result !== false ? $result : '{}';
    }

    /**
     * @param array<mixed> $arr
     * @throws UtilsException
     */
    public function convertArrayToObject(array $arr): mixed
    {
        return $this->convertStringToObject($this->convertArrayToString($arr));
    }

    /**
     * @return array<mixed>
     * @throws UtilsException
     */
    public function convertObjectToArray(object $obj): array
    {
        $result = json_decode($this->convertObjectToString($obj), true);
        $this->checkError(__METHOD__);
        if (!is_array($result)) {
            throw new UtilsException(__METHOD__ . '(): decoded value is not an array');
        }
        return $result;
    }

    /** @throws UtilsException */
    public function convertObjectToString(object $obj): string
    {
        $result = json_encode($obj);
        $this->checkError(__METHOD__);
        return $result !== false ? $result : '{}';
    }

    private function checkError(string $method): void
    {
        if (json_last_error() > JSON_ERROR_NONE) {
            throw new UtilsException($method . '(): JSON error: ' . json_last_error_msg());
        }
    }
}
