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
export default function lazyLoadComponent({
  componentFactory,
  loadingComponent,
  loadingData,
}) {
  let resolveComponent;
  return () => ({
    // We return a promise to resolve a
    // component eventually.
    component: new Promise((resolve) => {
      resolveComponent = resolve;
    }),
    loading: {
      mounted() {
        // We immediately load the component if
        // `IntersectionObserver` is not supported.
        if (!("IntersectionObserver" in window)) {
          componentFactory().then(resolveComponent);
          return;
        }

        const observer = new IntersectionObserver((entries) => {
          // Use `intersectionRatio` because of Edge 15's
          // lack of support for `isIntersecting`.
          // See: https://github.com/w3c/IntersectionObserver/issues/211
          if (entries[0].intersectionRatio <= 0) return;

          // Cleanup the observer when it's not
          // needed anymore.
          observer.unobserve(this.$el);
          // The `componentFactory()` resolves
          // to the result of a dynamic `import()`
          // which is passed to the `resolveComponent()`
          // function.
          componentFactory().then(resolveComponent);
        });
        // We observe the root `$el` of the
        // mounted loading component to detect
        // when it becomes visible.
        observer.observe(this.$el);
      },
      // Here we render the the component passed
      // to this function via the `loading` parameter.
      render(createElement) {
        return createElement(loadingComponent, loadingData);
      },
    },
  });
}
