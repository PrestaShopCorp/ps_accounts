<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Api\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostFile;
use Link;
use PrestaShop\AccountsAuth\Api\Client\GenericClient;
use PrestaShop\AccountsAuth\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Exception\FirebaseException;

/**
 * Construct the client used to make call to Segment API
 */
class SegmentClient extends GenericClient
{
    public function __construct(Link $link, Client $client = null)
    {
        parent::__construct();

        $this->setLink($link);
        $psAccountsService = new PsAccountsService();
        $token = $psAccountsService->getFirebaseIdToken();

        if (!$token) {
            throw new FirebaseException('you must have admin token', 500);
        }

        if (null === $client) {
            $client = new Client([
                'base_url' => $_ENV['SEGMENT_PROXY_API_URL'],
                'defaults' => [
                    'timeout' => 60,
                    'exceptions' => $this->catchExceptions,
                    'headers' => [
                        'Authorization' => "Bearer $token",
                    ],
                ],
            ]);
        }

        $this->setClient($client);
    }

    /**
     * @param string $jobId
     * @param string $compressedData
     *
     * @return array
     */
    public function upload($jobId, $compressedData)
    {
        $this->setRoute($_ENV['SEGMENT_PROXY_API_URL'] . "/v0/upload/$jobId");

        $file = new PostFile(
            'file',
            $compressedData,
            'file.gz'
        );

        return $this->post([
            'headers' => [
                'Content-Type' => 'binary/octet-stream',
                'Content-Encoding' => 'gzip',
            ],
            'body' => [
                'file' => $file,
            ],
        ]);
    }
}
