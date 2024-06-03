# TESTIMONIALS

##Login page

The testimonials loaded in login page are a remote json loaded via cdn with the config `ps_accounts.testimonials_url`

The format for the json is :

```json
[
	{
		"en": {
			"sentence": "John's sentences",
			"name": "John Doe",
			"enterprise": "John's enterprise"
		},
		"fr": {
			"sentence": "La phrase de John",
			"name": "John Doe",
			"enterprise": "L'entreprise de John"
		}
	},
  {
    "en": {
      "sentence": "Roger's sentences",
      "name": "Roger Doe",
      "enterprise": "Roger's enterprise"
    },
    "fr": {
      "sentence": "La phrase de Roger",
      "name": "Roger Doe",
      "enterprise": "L'entreprise de Roger"
    }
  }
]
```

The isoCode is return by PrestaShop
