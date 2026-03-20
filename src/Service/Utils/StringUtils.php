<?php

declare(strict_types=1);

namespace Directive\Service\Utils;

use BadFunctionCallException;

final class StringUtils
{
    public function cleanSpaces(string $var): string
    {
        return (string) preg_replace('/\\s+/', ' ', trim($var));
    }

    public function lettersAndFiguresOnly(string $var): string
    {
        return $this->stripCharacters(
            $this->cleanSpaces($var),
            [[
                'pat' => '/[^A-Za-z_0-9]/',
                'rep' => '',
            ]],
        );
    }

    public function filterForEasyFilenaming(string $var): string
    {
        return $this->stripCharacters(
            $this->cleanSpaces($var),
            [[
                'pat' => '/[^A-Za-z_0-9.-]/',
                'rep' => '',
            ]],
        );
    }

    public function emailValidCharacters(string $var): string
    {
        return $this->stripCharacters(
            $this->cleanSpaces($var),
            [
                ['pat' => '/[^A-Za-z_0-9@.]/', 'rep' => ''],
                ['pat' => '/\\.+/', 'rep' => '.'],
            ],
        );
    }

    /**
     * @param array<array{pat: string, rep: string}> $patterns
     */
    private function stripCharacters(string $var, array $patterns): string
    {
        $v = $var;
        foreach ($patterns as $ent) {
            $v = (string) preg_replace($ent['pat'], $ent['rep'], $v);
        }
        return $v;
    }

    public function encodeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public function decodeHtml(string $text): string
    {
        return htmlspecialchars_decode($text, ENT_QUOTES);
    }

    /**
     * Split a Unicode string into chunks of $length characters.
     *
     * @return array<int, string>
     */
    public function strSplitUnicode(string $str, int $length = 1): array
    {
        $result = preg_split('/(.{' . $length . '})/us', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        return is_array($result) ? $result : [];
    }

    /** @throws BadFunctionCallException when mbstring extension is missing */
    public function strToUtf8(string $str): string
    {
        if (!function_exists('mb_detect_encoding')) {
            throw new BadFunctionCallException('To use strToUtf8(), please activate the PHP mbstring extension.');
        }
        $encoding = mb_detect_encoding($str, mb_detect_order(), true);
        if ($encoding !== false && $encoding !== 'UTF-8') {
            $converted = iconv($encoding, 'UTF-8', $str);
            return $converted !== false ? $converted : $str;
        }
        return $str;
    }

    /**
     * Replace special characters with their ASCII equivalents.
     */
    public function cleanString(string $text): string
    {
        /** @var array<string, string> $utf8 */
        $utf8 = [
            '/[áàâãªä]/u'    => 'a',   '/[ÁÀÂÃÄ]/u'     => 'A',
            '/[ÍÌÎÏ]/u'      => 'I',   '/[íìîï]/u'      => 'i',
            '/[éèêë]/u'      => 'e',   '/[ÉÈÊË]/u'      => 'E',
            '/[óòôõº°ö]/u'  => 'o',   '/[ÓÒÔÕÖ]/u'     => 'O',
            '/[úùûü]/u'      => 'u',   '/[ÚÙÛÜ]/u'      => 'U',
            '/[Ææ]/'         => 'ae',  '/[Œœ]/'          => 'oe',
            '/ç/'            => 'c',   '/Ç/'             => 'C',
            '/ñ/'            => 'n',   '/Ñ/'             => 'N',
            '/\x{0301}/u'  => '',
            '/\x{2013}/'   => '-',
            "/[''\x{2039}\x{203A}\x{201A}]/u" => '_',
            '/[""`\x{00AB}\x{00BB}\x{201E}]/u' => '_',
            '/\x{00A0}/'   => '_',
            "/[#']/"         => '',
        ];
        return (string) preg_replace(array_keys($utf8), array_values($utf8), $text);
    }
}
