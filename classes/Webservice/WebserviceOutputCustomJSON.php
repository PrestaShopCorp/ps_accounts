<?php

namespace PrestaShop\Module\PsAccounts\Webservice;

use WebserviceOutputInterface;

/**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
class WebserviceOutputCustomJSON implements WebserviceOutputInterface
{
    /**
     * @var string
     */
    public $docUrl = '';

    /**
     * @var array
     */
    public $languages = [];

    /**
     * @var string
     */
    protected $wsUrl;

    /**
     * @var mixed
     */
    protected $schemaToDisplay;

    /**
     * @var array Json contents
     */
    protected $content = [];

    /**
     * WebserviceOutputCustomJSON constructor.
     *
     * @param array $languages
     */
    public function __construct($languages = [])
    {
        $this->languages = $languages;
    }

    /**
     * @param $schema
     *
     * @return $this
     */
    public function setSchemaToDisplay($schema)
    {
        if (is_string($schema)) {
            $this->schemaToDisplay = $schema;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSchemaToDisplay()
    {
        return $this->schemaToDisplay;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setWsUrl($url)
    {
        $this->wsUrl = $url;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWsUrl()
    {
        return $this->wsUrl;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return 'application/json';
    }

    /**
     * @param $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @param $message
     * @param null $code
     *
     * @return string
     */
    public function renderErrors($message, $code = null)
    {
        $this->content['errors'][] = ['code' => $code, 'message' => $message];

        return '';
    }

    /**
     * @param $content
     *
     * @return false|string
     */
    public function overrideContent($content)
    {
        $content = json_encode($this->content, JSON_UNESCAPED_UNICODE);

        return (false !== $content) ? $content : '';
    }

    /**
     * @param $obj
     * @param $params
     * @param $assoc_name
     * @param bool $closed_tags
     *
     * @return string
     */
    public function renderAssociationHeader($obj, $params, $assoc_name, $closed_tags = false)
    {
        return '';
    }

    /**
     * @param $obj
     * @param $params
     * @param $assoc_name
     *
     * @return void
     */
    public function renderAssociationFooter($obj, $params, $assoc_name)
    {
        // TODO: Implement renderAssociationFooter() method.
    }

    /**
     * @return string
     */
    public function renderErrorsHeader()
    {
        return '';
    }

    /**
     * @return string
     */
    public function renderErrorsFooter()
    {
        return '';
    }

    public function renderField($field)
    {
        // TODO: Implement renderField() method.
    }

    public function renderNodeHeader($obj, $params, $more_attr = null)
    {
        // TODO: Implement renderNodeHeader() method.
    }

    public function renderNodeFooter($obj, $params)
    {
        // TODO: Implement renderNodeFooter() method.
    }
}
