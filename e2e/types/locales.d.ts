type LocaleContent = Record<string, string | string[]>;
type PageLocale = Record<string, LocaleContent>;
type Locales = Record<string, PageLocale>;

export {LocaleContent, PageLocale, Locales};
