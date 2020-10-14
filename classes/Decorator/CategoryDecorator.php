<?php

namespace PrestaShop\Module\PsAccounts\Decorator;

class CategoryDecorator
{
    /**
     * @param array $categories
     *
     * @return void
     */
    public function decorateCategories(array &$categories)
    {
        foreach ($categories as &$category) {
            $this->castPropertyValues($category);
            $this->formatDescription($category);
        }
    }

    /**
     * @param array $category
     *
     * @return void
     */
    private function castPropertyValues(array &$category)
    {
        $category['id_category'] = (int) $category['id_category'];
        $category['id_parent'] = (int) $category['id_parent'];
    }

    /**
     * @param array $category
     *
     * @return void
     */
    private function formatDescription(array &$category)
    {
        $category['description'] = md5($category['description']);
    }
}
