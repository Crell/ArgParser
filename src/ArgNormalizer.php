<?php

declare(strict_types=1);

namespace Crell\ArgParser;

class ArgNormalizer
{
    public function parseArgv(array $argv): array
    {
        $ret = [];

        $i = 0;
        while ($i < count($argv)) {
            if (str_starts_with($argv[$i], '--')) {
                // It's a long-form argument.
                $entry = substr($argv[$i], 2);

                [$name, $value] = $this->getNameValue($entry);

                $ret = $this->updateResult($ret, $name, $value);
            } elseif (str_starts_with($argv[$i], '-')) {
                // It's a short-form argument.
                $entry = substr($argv[$i], 1);

                [$name, $value] = $this->getNameValue($entry);

                $ret = $this->updateResult($ret, $name, $value);
            } else {
                // Anything that's not an explicit switch gets ignored.
                // This is mostly so that we can have a "command" parameter first
                // that gets handled separately.
            }
            $i++;
        }

        return $ret;
    }

    private function updateResult(array $ret, string $name, mixed $value): array
    {
        if (isset($ret[$name])) {
            if (is_array($ret[$name])) {
                $ret[$name][] = $value;
            } else {
                $ret[$name] = [$ret[$name], $value];
            }
        } else {
            $ret[$name] = $value;
        }
        return $ret;
    }

    private function getNameValue(string $value): array
    {
        if (str_contains($value, '=')) {
            return \explode('=', $value);
        }

        // Use boolean true to indicate that, yep, the switch was present.
        return [$value, true];
    }
}
