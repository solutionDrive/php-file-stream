<?php
namespace pastuhov\FileStream;

/**
 * Class FileStream.
 * @package pastuhov\FileStream
 */
class FileStream
{
    /**
     * Base file name.
     *
     * Example:
     * export.yml
     * sitemap{count}.xml
     *
     * @var string
     */
    protected $fileName;

    /**
     * Temporary directory path.
     *
     * Example: /path/to/tmp
     *
     * @var string
     */
    protected $tmpDir;

    /**
     * Destination directory path.
     *
     * Example: /path/to/public
     *
     * @var string
     */
    protected $dstDir;

    /**
     * File header.
     *
     * Any file will be started from that string.
     *
     * @var string
     */
    protected $header;

    /**
     * File footer.
     *
     * Any file will be ended at that string.
     *
     * @var string
     */
    protected $footer;

    /**
     * Max possible writes to one file.
     * @var string
     */
    protected $maxCount;

    /**
     * File name count placeholder.
     * @var string
     */
    protected $countPlaceHolder = '{count}';

    /**
     * Current writes count.
     * @var int
     */
    protected $currentCount = 0;

    /**
     * Current files count.
     * @var int
     */
    protected $currentFileCount = 0;

    /**
     * @param string $fileName Base file name
     * @param string $tmpDir Temporary directory path
     * @param string $dstDir Destination directory path
     * @param string|null $header File header
     * @param string|null $footer File footer
     * @param bool|false $maxCount Max possible writes to one file
     * @throws \Exception
     */
    public function __construct($fileName, $tmpDir, $dstDir, $header = null, $footer = null, $maxCount = false)
    {
        $this->fileName = $fileName;
        $this->tmpDir = $this->checkPath($tmpDir);
        $this->dstDir = $this->checkPath($dstDir);
        $this->header = $header;
        $this->footer = $footer;
        $this->maxCount = $maxCount;

        if ($this->maxCount !== false && strpos($this->fileName, $this->countPlaceHolder) === false) {
            throw new \Exception('File name ' . $this->countPlaceHolder . ' placeholder is needed');
        }
    }

    private $_handle = false;

    /**
     * Get file handle.
     * @return bool|resource
     * @throws \Exception
     */
    public function getHandle()
    {
        if ($this->_handle === false) {
            $fileName = $this->getFileName();
            $this->_handle = fopen($this->tmpDir . '/' . $fileName, 'w');

            if (!$this->_handle) {
                throw new \Exception('Cannot open file ' . $fileName);
            }

            if ($this->header !== null) {
                $this->write($this->header, false);
            }
        }

        return $this->_handle;
    }

    /**
     * Binary-safe file handle write.
     * @param string $string contents
     * @param bool $count
     * @throws \Exception
     */
    public function write($string, $count = true)
    {
        $fileName = $this->getFileName();
        $handle = $this->getHandle();

        if (fwrite($handle, $string) === false) {
            throw new \Exception('Cannot write to file ' . $fileName);
        }

        if ($count) {
            $this->currentCount++;
            if ($this->currentCount === $this->maxCount) {
                $this->closeHandle();
            }
        }
    }

    /**
     * Close file handle.
     * @throws \Exception
     */
    public function closeHandle()
    {
        if ($this->footer !== null) {
            $this->write($this->footer, false);
        }

        $handle = $this->getHandle();
        fclose($handle);
        $this->_handle = false;

        $fileName = $this->getFileName();
        rename($this->tmpDir . '/' . $fileName, $this->dstDir . '/' . $fileName);

        $this->currentFileCount++;
        $this->currentCount = 0;
    }

    /**
     * Base file name with replaced count placeholder.
     * @return string Base file name with replaced placeholder
     */
    protected function getFileName()
    {
        $fileName = $this->fileName;

        if ($this->maxCount !== false) {
            $fileName = strtr($fileName, [$this->countPlaceHolder => $this->currentFileCount]);
        }

        return $fileName;
    }

    /**
     * Filter and validate path.
     * @param string $path Ex.: /var/to/dir/
     * @return string  Ex.: /var/to/dir
     */
    protected function checkPath($path)
    {
        $path = rtrim($path, '/');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }

    /**
     * Destruct.
     */
    public function __destruct()
    {
        $this->closeHandle();
    }
}
