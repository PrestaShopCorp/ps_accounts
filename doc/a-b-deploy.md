# A-B Deploy (find the right name)

## Deployment strategies

### Proposal #1

| PsAccounts Version | from_version | to_version |
|--------------------|--------------|------------|
| 7.0.1              | \>= 8        | < 9        |
| 7.0.2              | \>= 1.7      | < 9        |
| 7.0.3              | \>= 1.6.1    | < 9        |

### Proposal #2

| PsAccounts Version | from_version | to_version |
|--------------------|--------------|------------|
| 7.0.1              | \>= 8        | < 9        |
| 7.0.2              | \>= 1.7      | < 8        |
| 7.0.3              | \>= 1.6.1    | < 1.7      |
| 7.0.4+             | \>= 1.6.1    | < 9        |

## Reminders

#### 1. We should define an upper version limit to avoid any breaking change from Prestashop (and according to semver) and not `_PS_VERSION_`

#### 2. Find a way to specify both lower AND upper limit when publishing new versions on Addons Marketplace (only a `from_version` is defined for now)

See: `./github/mktp-metadata.json`
