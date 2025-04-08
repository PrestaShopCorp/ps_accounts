<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Service\OAuth2;

class CachedFile
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var int|null lifetime in seconds
     */
    private $ttl;

    /**
     * @param string $filename
     * @param int|null $ttl TTL in seconds or null (never expires)
     *
     * @throws \Exception
     */
    public function __construct($filename, $ttl = null)
    {
        $this->filename = $filename;
        $this->ttl = $ttl;

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
     * @param int|null $ttl
     *
     * @return void
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
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
