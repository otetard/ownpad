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

**Set a Etherpad Host:** To be able to process the document, you must
configure a Host. [Find more public providers at the Etherpad-Lite
wiki](https://github.com/ether/etherpad-lite/wiki/Sites-that-run-Etherpad-Lite)

*Example:*
* Etherpad Host   https://etherpad.wikimedia.org/
* Ethercalc Host  https://ethercalc.net/

Note that most browsers will only display the content if both
Nextcloud and Etherpad/Ethercalc are served via HTTPS.

Afterwards, the “pad” and/or “calc” items will be available in the “+”
menu from the “File” app.

## Pad mapping and token policy

Ownpad keeps a persistent server-side mapping between Nextcloud
`.pad` files and Etherpad pads. This mapping is used during open/create
flows to keep file-to-pad relations consistent and to support future
lifecycle work (for example delete/restore).

### New `.pad` files

When Ownpad creates a new `.pad` file, it writes:

- `URL=...`
- `OwnpadToken=...`

and stores a matching binding in the database.

### Existing `.pad` files without token/binding

Existing files are **not** imported automatically during update. Admins
can control global handling of tokenless `.pad` files in settings:

- `none`: only token-based pads are allowed (strict).
- `unprotected`: tokenless pads are allowed only for unprotected pads.
- `all`: tokenless pads are allowed for all pad types.

Warning: if tokenless access is allowed broadly, possession of the pad
URL may be sufficient to open protected pads even without access to the
corresponding `.pad` file.

Access model summary:

- Access to a `.pad` file in Nextcloud does **not** automatically grant
  access to every Etherpad resource.
- For token-based pads, Ownpad validates the file-to-pad mapping on the
  server side before opening.
- If tokenless mode is set to `all`, users who know the Etherpad URL
  may be able to open protected pads even without access to the related
  `.pad` file in Nextcloud.

### Backfill from admin settings

Admins can import existing `.pad` files into the mapping table from
settings:

- run a dry-run first to review results,
- run backfill to persist mappings,
- resolve grouped conflicts (same target pad referenced by multiple
  `.pad` files) via provided actions.

### Update from older versions (<= 0.13.2)

The mapping database table is created automatically during
installation/update migration.
To include already existing `.pad` files, admins need to open Ownpad
admin settings and run the backfill action once.

Recommended upgrade path:

1. Upgrade the app.
2. In admin settings, choose a temporary tokenless policy (`unprotected`
   or `all`) if needed for transition.
3. Run backfill (dry-run first, then execute).
4. Resolve reported conflicts.
5. Switch to stricter policy (`none` or `unprotected`) after migration.

## Mimetype Detection

### Automatic Configuration

Ownpad now automatically tries to register MIME types by editing
`/config/mimetypemapping.json` and `/config/mimetypealiases.json`
files. To do so, the `config` directory should be editable by the user
used to execute the PHP code.

### Manual Configuration

This step can also be done manually.

First, you should add the following content in the `/config/mimetypealiases.json` file:

```json
{
    "application/x-ownpad": "pad",
    "application/x-ownpad-calc": "calc"
}
```

Then, you should add the following content in the `/config/mimetypemapping.json` file:

```json
{
    "pad": ["application/x-ownpad"],
    "calc": ["application/x-ownpad-calc"]
}
```

For the [snap-distribution of
Nextcloud](https://github.com/nextcloud/nextcloud-snap) the template
file can be found under
`/snap/nextcloud/current/htdocs/resources/config/mimetypemapping.dist.json`
and the active config-folder by default is
`/var/snap/nextcloud/current/nextcloud/config/`.

Then you should copy the MIME type icons from Ownpad to the Nextcloud core:

```
cp apps/ownpad/img/{calc,pad}.svg core/img/filetypes
```

Finally, you should update the `mimetypelist.js` file using the following command:

```
php occ maintenance:mimetype:update-js
```

If all other mimetypes are not working properly, just run the
following command:

    sudo -u www-data php occ files:scan --all

For the snap-distribution that is

    sudo nextcloud.occ files:scan --all

## Create access restricted pads

### HTTP Auth

Basic HTTP auth enabled on the Etherpad webserver is compatible with
Ownpad. If this is used then the user will simply be prompted to enter
login credentials by their browser when they try to access a pad from
within Nextcloud.

### Etherpad-managed Authentication

Ownpad supports communication with the Etherpad API for access
restriction (so called *protected pads*).

Protected pads need to be accessed via Nextcloud in order to gain access
privileges.

In order for this to work, you’ll need to enter your Etherpad API
credentials (either the API key for Etherpad 1.x or the client
ID/client secret for Etherpad 2.x). Please refer to the next section
to find out how to configure Etherpad.

In addition you’ll need to host your Etherpad and Nextcloud instances
under the same domain. For example, you can host your Etherpad in
`pad.example.org` and your Nextcloud in `cloud.example.org`. For this
example, you’ll have to set the cookie domain to `example.org` within
the Ownpad settings.

If you want to create *truly* private pads, you have to dedicate an
Etherpad instance for Nextcloud **running both with HTTPS**. You will
then configure Etherpad to restrict pad access via sessions and pad
creation via the API.  For this, you have to adjust your Etherpad
configuration file (`settings.json`) as following:

```json
{
    # …
    "requireSession" : true,
    "editOnly" : true,
}
```

#### Etherpad Authentication

If you are using Etherpad 1.x, then authentication is using a single
API key secret. You can find your API key in the `APIKEY.txt` file of
your Etherpad instance. This API key should be put in Ownpad settings.

If you are using Etherpad 2.x (at least 2.0.3 is required), then, you
should first configure your Etherpad’s `settings.json` file to add a
new service account. You should give that account admin
credentials. You should add the following snippet (you should adjust
`client_id` and `client_secret` to strong values):

```json
{
  # …
  "sso": {
    # …
    "clients": [
      # …
      {
        "client_id": "client_id",
        "redirect_uris": [],
        "response_types": [],
        "grant_types": ["client_credentials"],
        "client_secret": "client_secret",
        "extraParams": [
          {
            "name": "admin",
            "value": "true"
          }
        ]
      }
    ]
  }
}
```

Then, you should push that secrets in Ownpad configuration after
having enabled the OAuth2 authentication mode.

## License

The code is licensed under the AGPLv3 which can be found as the file
[COPYING](COPYING) in the source code repository.
