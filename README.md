#Fleet-Yo!#
Fleet management and tracking tool for EVE Online.
Copyright 2017 Snitch Ashor of BRGF.

#Requirements#
+ php 5.5+
+ php-curl
+ MySQL
+ php-mysqli
+ For certain features (cookies), site should be running via ssl

#Installation#
1. Create a Database for fleet-yo.
2. Import schema.sql from the SQL subfolder
3. Download required SDE tables in sql format from https://www.fuzzwork.co.uk/dump/latest/ 
	You need the following tables:

	+ invGroups.sql
	+ invMarketGroups.sql
	+ invTypes.sql
	+ mapDenormalize.sql
	+ mapSolarSystems.sql
4. Go to https://developers.eveonline.com/ and register an ap with the following scopes:
	+ esi-fleets.read_fleet.v1
	+ esi-fleets.write_fleet.v1
	+ esi-location.read_location.v1
	+ esi-location.read_ship_type.v1
	+ esi-ui.write_waypoint.v1
	+ esi-universe.read_structures.v1

	The callback url should be http(s)://<domain>/<fleet-yo path>/login.php
5. Rename config.php.sample to config.php and edit it. Fill in the database and developer app credentials and put a random string for the salt. This one is used to add some security to authentication cookies. If you want to restict access to you fleet-yo instance just add FC_PILOTS, CORPS or ALLYS. if you want to keep track of what you added you can use associative arrays like array("Snitch" => 90976676,)

Done.

#Version history#

+ 0.1b First public release
