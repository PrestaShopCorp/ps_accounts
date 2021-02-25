export default {
  path: "onboarding",
  name: "onboarding configuration",
  component: () =>
    import(
      /* webpackChunkName: "settingsOnBoarding" */
      "@/core/settings/pages/OnBoardingApp"
    ),
};
