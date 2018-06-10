# Ownpad — Etherpad and Ethercalc links in Nextcloud

Ownpad is a Nextcloud application that allows to create and open
Etherpad and Ethercalc documents. This application requires to have
access to an instance of Etherpad and/or Ethercalc to work properly.

## Configuration

In order to make Ownpad work, go to the configuration panel (Settings /
Admininstration / Additional Settings) and fill in the necessary data
within the “Ownpad (collaborative documents)” section.

**Set a Etherpad Host:**  
To be able to process the document, a must configure a Host. Additional public host provider [more public Host-Provider](https://github.com/ether/etherpad-lite/wiki/Sites-that-run-Etherpad-Lite)

*Example:*
* Etherpad Host   https://etherpad.wikimedia.org/
* Ethercalc Host  https://ethercalc.org/

Afterwards, the “pad” and/or “calc” items will be available in the “+”
menu from the “File” app.

## Mimetype detection

Unfortunately, apps can’t declare new mimetypes on the fly. To make
Ownpad work properly, you need to add two new mimetypes in the
`mimetypemapping.json` file (at Nextcloud level).

To proceed, just copy `/resources/config/mimetypemapping.dist.json` to
`/config/mimetypemapping.json` (in the `config/` folder at Nextcloud’s
root directory; the file should be stored next to the `config.php`
file). Afterwards add the two following lines just after the “_comment”
lines.

    "pad": ["application/x-ownpad"],
    "calc": ["application/x-ownpad"],

If all other mimetypes are not working properly, just run the
following command:

    sudo -u www-data php occ files:scan --all

## Create access restricted pads

Ownpad supports communication with the Etherpad API for access
restriction (so called *protected pads*). This support is considered
**experimental** due to work in progress; some features are still
missing. See the [TODO.md](TODO.md) for details.

Protected pads need to be accessed via Nextcloud in order to gain access
privileges.

In order for this to work, you’ll need to enter your Etherpad API key
within the Ownpad settings. You can find your API key in the
`APIKEY.txt` file of your Etherpad instance.

In addition you’ll need to host your Etherpad and Nextcloud instances
under the same domain. For example, you can host your Etherpad in
`pad.example.org` and your Nextcloud in `cloud.example.org`. For this
example, you’ll have to set the cookie domain to `example.org` within
the Ownpad settings.

If you want to create *truly* private pads, you have to dedicate an
Etherpad instance for Nextcloud. You will then configure Etherpad to
restrict pad access via sessions and pad creation via the API.
For this, you have to adjust your Etherpad configuration file
(`settings.json`) as following:

    "requireSession" : true,
    "editOnly" : true,
