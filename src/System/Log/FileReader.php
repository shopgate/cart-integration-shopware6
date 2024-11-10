<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

class FileReader
{

    /**
     * Retrieves a list of files in the log directory
     */
    public function getDirectoryFiles(string $directory): false|array
    {
        // Fetch files from the directory
        $files = scandir($directory) ?: [];

        // Filter only the files (excluding directories)
        return array_filter($files, function ($file) use ($directory) {
            return is_file($directory . '/' . $file);
        });
    }

    /**
     * Locates the recent most created file name inside the directory
     */
    public function getLatestFile(string $directory): ?string
    {
        // Fetch files from the directory
        $files = $this->getDirectoryFiles($directory);
        // Sort files by modification time
        usort($files, function ($a, $b) use ($directory) {
            return filemtime($directory . '/' . $b) - filemtime($directory . '/' . $a);
        });

        return reset($files);
    }

    /**
     * Gist from https://gist.github.com/lorenzos/1711e81a9162320fde20
     * @author Torleif Berger, Lorenzo Stanco
     * @link http://stackoverflow.com/a/15025877/995958
     * @license http://creativecommons.org/licenses/by/3.0/
     */
    public function tailFile($filepath, $lines = 1, $adaptive = true): string
    {
        // Open file
        $f = @fopen($filepath, 'rb');
        if ($f === false) {
            return '';
        }

        // Sets buffer size, according to the number of lines to retrieve.
        // This gives a performance boost when reading a few lines from the file.
        if (!$adaptive) {
            $buffer = 4096;
        } else {
            $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
        }

        // Jump to last character
        fseek($f, -1, SEEK_END);

        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($f, 1) != "\n") {
            $lines -= 1;
        }

        // Start reading
        $output = '';

        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {
            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);

            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);

            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($f, $seek)) . $output;

            // Jump back to where we started reading
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

            // Decrease our line counter
            $lines -= substr_count($chunk, "\n");
        }

        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {
            // Find first newline and remove all text before that
            $output = substr($output, strpos($output, "\n") + 1);
        }

        // Close file and return
        fclose($f);
        return trim($output);
    }

    public function sequenceSearch(string $filepath, string $sequence): array
    {
        $search = '"sequence":"' . $sequence . '"';
        $start = $this->findSequenceStart($filepath, $search);
        if (false === $start) {
            return [];
        }

        // Open file
        $f = @fopen($filepath, 'rb');
        if ($f === false) {
            return [];
        }

        // Sets buffer size, according to the number of lines to retrieve.
        // This gives a performance boost when reading a few lines from the file.
        $buffer = 4096;

        // Jump to last character
        if ($start) {
            fseek($f, $start);
        } else {
            fseek($f, -1, SEEK_END);
        }

        // Start reading
        $output = '';

        // While we would like more
        do {
            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);

            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);

            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($f, $seek)) . $output;

            // Jump back to where we started reading
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        } while (ftell($f) > 0 && str_contains($chunk, $search));

        // Close file and return
        fclose($f);

        return array_filter(explode("\n", trim($output)), function (string $line) use ($search) {
            return str_contains($line, $search);
        });
    }

    /**
     * Helps locate where the chunk is in the log file with the desired string
     */
    public function findSequenceStart(string $filename, string $searchString): false|int
    {
        $file = fopen($filename, 'r');
        $buffer = 4096; // Read in chunks
        $chunk = '';
        $foundPosition = false;

        fseek($file, 0, SEEK_END);
        $position = ftell($file);

        while ($position > 0) {
            $position -= $buffer;
            if ($position < 0) {
                $buffer += $position;
                $position = 0;
            }

            fseek($file, $position, SEEK_SET);
            $chunk = fread($file, $buffer) . $chunk;

            if (str_contains($chunk, $searchString)) {
                $foundPosition = $position + $buffer;
                break;
            }

            // Keep last part of chunk to ensure overlaps aren't missed
            $chunk = substr($chunk, 0, $buffer);
        }

        fclose($file);
        return $foundPosition;
    }
}
