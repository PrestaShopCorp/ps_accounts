import OnBoardingRouter from "./Settings/OnBoardingRouter";
// import PostSettingsRouter from './Settings/PostSettingsRouter';

export default {
  path: "settings",
  name: "settings",
  redirect: "settings/onboarding",
  component: () =>
    import(
      /* webpackChunkName: "settings" */
      "@/core/settings/pages/SettingsApp"
    ),
  children: [OnBoardingRouter],
};
