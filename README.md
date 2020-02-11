# Ownpad — Etherpad and Ethercalc _links_ in Nextcloud

Ownpad is a Nextcloud application that allows to create and open
Etherpad and Ethercalc documents. This application requires to have
access to an instance of Etherpad and/or Ethercalc to work properly.

Note that the documents are only stored with your Etherpad/Ethercalc
service provider; no copy is kept on Nextcloud. As documents are
created this way Nextcloud is not responsible for the documents
security, e.g. anyone with access to the Etherpad/Ethercalc service
can access your documents.

## Configuration

In order to make Ownpad work, go to the configuration panel (Settings /
Admininstration / Additional Settings) and fill in the necessary data
within the “Ownpad (collaborative documents)” section.

**Set a Etherpad Host:**  
To be able to process the document, you must configure a Host. [Find more public providers at the Etherpad-Lite wiki](https://github.com/ether/etherpad-lite/wiki/Sites-that-run-Etherpad-Lite)

*Example:*
* Etherpad Host   https://etherpad.wikimedia.org/
* Ethercalc Host  https://ethercalc.org/

Note that most browsers will only display the content if both Nextcloud and Etherpad/Ethercalc are served via HTTPS.

Afterwards, the “pad” and/or “calc” items will be available in the “+”
menu from the “File” app.

## Mimetype detection

Unfortunately, apps can’t declare new mimetypes on the fly. To make
Ownpad work properly, you need to add two new mimetypes in the
`mimetypemapping.json` file (at Nextcloud level).

To proceed, just copy `/resources/config/mimetypemapping.dist.json` to
`/config/mimetypemapping.json` (in the `config/` folder at Nextcloud’s
root directory; the file should be stored next to the `config.php`
file).

For the [snap-distribution of Nextcloud](https://github.com/nextcloud/nextcloud-snap) the template file can be found under `/snap/nextcloud/current/htdocs/resources/config/mimetypemapping.dist.json` and the active config-folder by default is `/var/snap/nextcloud/current/nextcloud/config/`.

Afterwards add the two following lines just after the “_comment”
lines.

    "pad": ["application/x-ownpad"],
    "calc": ["application/x-ownpad"],

If all other mimetypes are not working properly, just run the
following command:

    sudo -u www-data php occ files:scan --all

For the snap-distribution that is

    sudo nextcloud.occ files:scan --all

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
Etherpad instance for Nextcloud **running both with HTTPS**. You will then configure Etherpad to
restrict pad access via sessions and pad creation via the API.
For this, you have to adjust your Etherpad configuration file
(`settings.json`) as following:

    "requireSession" : true,
    "editOnly" : true,
