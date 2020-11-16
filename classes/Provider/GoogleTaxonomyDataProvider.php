<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Repository\GoogleTaxonomyRepository;

class GoogleTaxonomyDataProvider implements PaginatedApiDataProviderInterface
{
    /**
     * @var GoogleTaxonomyRepository
     */
    private $googleTaxonomyRepository;

    public function __construct(GoogleTaxonomyRepository $googleTaxonomyRepository)
    {
        $this->googleTaxonomyRepository = $googleTaxonomyRepository;
    }

    public function getFormattedData($offset, $limit, $langIso = null)
    {
        $data = $this->googleTaxonomyRepository->getTaxonomyCategories($offset, $limit);

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

    public function getRemainingObjectsCount($offset, $langIso = null)
    {
        return $this->googleTaxonomyRepository->getRemainingTaxonomyRepositories($offset);
    }
}
