# Web monitoring tool

A simple tool to monitore you web sites !


## Configuration

Simply edit the [sites.json](sites.json) file.

The "domains" section contains the root tlds, one per section.
```
    "domains": [
        {
            "name": "domain.tld",
```
**name:** the root tld

The "subdomains" one contains the second level tld (aka: you subdomains)
```
            "subdomains": [
                {
                    "name": "www",
                    "port": "443",
                    "https": {
                        "certstate": "ok",
                        "expiry": 1549062018
                    },
                    "state": "online",
                    "lastcheck": 1542118232,
                    "contact": "admin@domain.tld",
                    "check": "\/",
                    "responsecode": 200
                },
```

**Here is what to parameter:**

**name:** the subdomain name. If null, this means "no subdomain"

**port:** the port used to reach the site

**https:** if the site is using HTTPS

**contact:** the mail address used to contact human when the state change

**check:** the page to check

## Running the scanner

You need to call the scan.php file to update the data status.
For example using cron:
```0 * * * * /usr/bin/php -f /var/www/monitor.domain.tld/scan.php >/dev/null```

Note: the mail functionnality is not yet implemented

## Such wow

![Screenshot](https://github.com/SilouFr/Web-monitoring/blob/master/screenshot.jpg)

Credits:

html -> @Yashn37

php -> @Silou_Atien (me)
