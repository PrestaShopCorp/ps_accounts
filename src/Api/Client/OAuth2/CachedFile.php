<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\OAuth2;

class CachedFile
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var int lifetime in seconds
     */
    private $ttl;

    /**
     * @param string $filename
     * @param int|null $ttl
     *
     * @throws \Exception
     */
    public function __construct($filename, $ttl = null)
    {
        $this->filename = $filename;
        $this->ttl = (int) $ttl;

        $this->initDirectory();
        $this->assertReadable();
        $this->assertWritable();
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        if (file_exists($this->filename)) {
            if ($this->ttl === null) {
                return false;
            }

            return time() - filemtime($this->filename) > $this->ttl;
        }

        return true;
    }

    /**
     * @return false|string
     */
    public function read()
    {
        return file_get_contents($this->filename);
    }

    /**
     * @param mixed $content
     *
     * @return void
     */
    public function write($content)
    {
        file_put_contents($this->filename, $content);
    }

    /**
     * @return void
     */
    public function clear()
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return int|null
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @return bool
     */
    protected function initDirectory()
    {
        if (!file_exists(dirname($this->filename))) {
            return mkdir(dirname($this->filename), 0755, true);
        }

        return true;
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function assertReadable()
    {
        if (!is_readable($this->filename) && !is_readable(dirname($this->filename))) {
            throw new \Exception('File "' . $this->filename . '" is not readable.');
        }
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function assertWritable()
    {
        if (!is_writable($this->filename) && !is_writeable(dirname($this->filename))) {
            throw new \Exception('File "' . $this->filename . '" is not writable.');
        }
    }
}
