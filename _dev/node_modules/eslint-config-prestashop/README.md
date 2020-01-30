# eslint-config-prestashop

#### An ESLint [Shareable Config](http://eslint.org/docs/developer-guide/shareable-configs) for [JavaScript Standard Style](http://standardjs.com)

## Install

```bash
npm install eslint-config-prestashop
```

## Usage

Shareable configs are designed to work with the `extends` feature of `.eslintrc` files.
You can learn more about [Shareable Configs](http://eslint.org/docs/developer-guide/shareable-configs) on the official ESLint website.

**If you want to set up the config automatically**, follow these steps in your project directory:

1. `npx eslint --init`
2. Select "Use a popular style guide."
3. Select "Prestashop"
4. Select a config file format.
5. If prompted, confirm the installation of the necessary dependencies.

The above steps will automatically set up an ESLint configuration and install the necessary dependencies for you.

**If you want to set up the config manually**, run the following command:

```bash
npm install --save-dev eslint-config-prestashop eslint-config-airbnb-base eslint-plugin-import
```

Then, add this to your `.eslintrc` file:

```
{
  "extends": "prestashop"
}
```

*Note: We omitted the `eslint-config-` prefix since it is automatically assumed by ESLint.*

You can override settings from the shareable config by adding them directly into your
`.eslintrc` file.

## License

MIT. Copyright (c)
