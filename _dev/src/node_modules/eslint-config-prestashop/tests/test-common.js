import eslint from 'eslint';
import fs from 'fs';
import path from 'path';
import test from 'tape';

const {CLIEngine} = eslint;
const cli = new CLIEngine({
  useEslintrc: false,
  configFile: '.eslintrc',
});

const directoryPath = path.join(__dirname, './fixtures');
fs.readdirSync(directoryPath).forEach((name) => {
  test(`test eslint config to validate ${name}`, (t) => {
    t.equal(
      cli.executeOnText(
        fs.readFileSync(path.join(directoryPath, name)).toString(),
      ).errorCount,
      0,
    );

    t.end();
  });
});
