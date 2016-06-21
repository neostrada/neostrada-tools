# Neostrada Tools
This addon contains a set of tools which enables your customers to manage DNS records and domain redirection. This README will guide you through the
necessary steps to install this addon.

## Installation
Follow the steps below to install this addon.

- Download the addon and copy the `ntools` directory to `/modules/addons/`.
- Login to your account at Neostrada and obtain the API key and API secret. You can find them on the `API` page.
- Login to your WHMCS administrator account and go to `Setup > Addon Modules`.
- Search for `Neostrada Tools` and click on `Activate`.
- Click on `Configure`, enter the API key and API secret and click on `Save Changes`.

## Usage
Below you'll find the available pages to manage DNS records and domain redirection. Please note that `DOMAIN_ID` should be the ID of the domain name as it's known in WHMCS. The domain name has to be in your account at Neostrada in order to manage its DNS records and redirect.

### Manage DNS records
Go to the following page to manage DNS records:

`/index.php?m=ntools&a=dns&d=DOMAIN_ID`

### Manage domain redirection
Go to the following page to manage domain redirection:

`/index.php?m=ntools&a=redirect&d=DOMAIN_ID`