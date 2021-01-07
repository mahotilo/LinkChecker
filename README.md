# [LinkChecker](https://github.com/mahotilo/LinkChecker) - Link Checker plugin for Typesetter CMS

## About
Check links on the current page

- checking results are stored for one month
- sortable results table
- show checked link in page (scroll to and highlight for 10 sec)

## See also 
* [Typesetter Home](http://www.typesettercms.com)
* [Typesetter on GitHub](https://github.com/Typesetter/Typesetter)


## Requirements
* Typesetter CMS

## Manual Installation
1. Download the [master ZIP archive](https://github.com/mahotilo/LinkChecker/archive/master.zip)
2. Upload the extracted folder 'LinkChecker-master' to your server into the /addons directory
3. Install using Typesetter's Admin Toolbox &rarr; Plugins &rarr; Manage &rarr; Available &rarr; LinkChecker


## Demo
### Check report
![image](demo/report.png)

## License
GPL 2, for bundled thirdparty components see the respective subdirectories.

Based on  https://github.com/jakeRPowell/linkChecker

## Version history
1.4.1
- improve link finding

1.4
- drop tablesort.js and use TS integrated tablesorter.js and classes.

1.3
- fix empty records handling

1.2
- extend whitelist for "click to call" links
- show checked link in page on click

1.1
- fix results storage error
- add statistics
- sort elements on status
- add styling
	
1.0
- initial version
