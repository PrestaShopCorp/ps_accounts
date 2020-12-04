<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use Context;
use PrestaShop\Module\PsAccounts\Repository\GoogleTaxonomyRepository;

class GoogleTaxonomyDataProvider implements PaginatedApiDataProviderInterface
{
    /**
     * @var GoogleTaxonomyRepository
     */
    private $googleTaxonomyRepository;
    /**
     * @var Context
     */
    private $context;

    public function __construct(GoogleTaxonomyRepository $googleTaxonomyRepository, Context $context)
    {
        $this->googleTaxonomyRepository = $googleTaxonomyRepository;
        $this->context = $context;
    }

    public function getFormattedData($offset, $limit, $langIso)
    {
        $data = $this->googleTaxonomyRepository->getTaxonomyCategories($offset, $limit, $this->context->shop->id);

        if (!is_array($data)) {
            return [];
        }

        return array_map(function ($googleTaxonomy) {
            $uniqueId = "{$googleTaxonomy['id_category']}-{$googleTaxonomy['id_category']}";
            $googleTaxonomy['taxonomy_id'] = $uniqueId;

            return [
                'id' => $uniqueId,
                'collection' => 'taxonomies',
                'properties' => $googleTaxonomy,
            ];
        }, $data);
    }

    public function getRemainingObjectsCount($offset, $langIso)
    {
        return (int) $this->googleTaxonomyRepository->getRemainingTaxonomyRepositories($offset, $this->context->shop->id);
    }

    public function getFormattedDataIncremental($limit, $langIso)
    {
        return [
            'ids' => [],
            'data' => [],
        ];
    }
}
