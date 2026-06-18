<?php

namespace App\Support\WordPress;

class SqlInsertParser
{
    /**
     * @return list<array<string, string|null>>
     */
    public static function parseTableRows(string $sql, string $tableName): array
    {
        $pattern = '/INSERT\s+INTO\s+`?'.preg_quote($tableName, '/').'`?\s*(?:\(([^)]+)\)\s*)?VALUES\s*(.+?);/is';
        $rows = [];

        if (! preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            return $rows;
        }

        foreach ($matches as $match) {
            $columns = isset($match[1]) && trim($match[1]) !== ''
                ? self::parseColumnList($match[1])
                : null;

            foreach (self::parseTuples($match[2]) as $values) {
                if ($columns === null) {
                    $rows[] = array_map(
                        fn ($value) => $value === null ? null : (string) $value,
                        $values,
                    );

                    continue;
                }

                $associative = [];

                foreach ($columns as $index => $column) {
                    $associative[$column] = isset($values[$index]) && $values[$index] !== null
                        ? (string) $values[$index]
                        : null;
                }

                $rows[] = $associative;
            }
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private static function parseColumnList(string $columnList): array
    {
        return array_map(
            fn (string $column): string => trim($column, " `\t\n\r\0\x0B"),
            explode(',', $columnList),
        );
    }

    /**
     * @return list<list<string|int|null>>
     */
    private static function parseTuples(string $valuesClause): array
    {
        $tuples = [];
        $length = strlen($valuesClause);
        $position = 0;

        while ($position < $length) {
            while ($position < $length && $valuesClause[$position] !== '(') {
                $position++;
            }

            if ($position >= $length) {
                break;
            }

            $position++;
            $tuple = [];

            while ($position < $length) {
                self::skipWhitespace($valuesClause, $position);

                if ($position < $length && $valuesClause[$position] === ')') {
                    $position++;
                    $tuples[] = $tuple;
                    break;
                }

                $tuple[] = self::parseValue($valuesClause, $position);

                self::skipWhitespace($valuesClause, $position);

                if ($position < $length && $valuesClause[$position] === ',') {
                    $position++;

                    continue;
                }
            }
        }

        return $tuples;
    }

    /**
     * @return string|int|null
     */
    private static function parseValue(string $input, int &$position): string|int|null
    {
        self::skipWhitespace($input, $position);

        if ($position >= strlen($input)) {
            return null;
        }

        $char = $input[$position];

        if ($char === "'") {
            return self::parseQuotedString($input, $position);
        }

        if (str_starts_with(substr($input, $position), 'NULL')) {
            $position += 4;

            return null;
        }

        $start = $position;

        while ($position < strlen($input) && ! in_array($input[$position], [',', ')'], true)) {
            $position++;
        }

        $value = trim(substr($input, $start, $position - $start));

        return is_numeric($value) ? (int) $value : $value;
    }

    private static function parseQuotedString(string $input, int &$position): string
    {
        $position++;
        $value = '';

        while ($position < strlen($input)) {
            $char = $input[$position];

            if ($char === '\\' && $position + 1 < strlen($input)) {
                $value .= $input[$position + 1];
                $position += 2;

                continue;
            }

            if ($char === "'") {
                if ($position + 1 < strlen($input) && $input[$position + 1] === "'") {
                    $value .= "'";
                    $position += 2;

                    continue;
                }

                $position++;

                break;
            }

            $value .= $char;
            $position++;
        }

        return $value;
    }

    private static function skipWhitespace(string $input, int &$position): void
    {
        while ($position < strlen($input) && ctype_space($input[$position])) {
            $position++;
        }
    }
}
