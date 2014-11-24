# Yadup - *Yet Another Database Updator Panel*
*Database updator for [Nette/Tracy](https://github.com/nette/tracy) panel*


## How to
1. Use the following [Composer](https://packagist.org/) command in your existing project to add Yadup to it:  

        composer require "meridius/yadup ~1.0"

- Register the extension by adding the following to your `config.neon` (1<sup>st</sup> level):  

        extensions:
        	yadup: Yadup\YadupExtension

- And at last don't forget to create directory for SQL updates files. Default is `%appDir%/sql` as specified below.


## Further configuration
You can tailor the updator to your needs by creating a new section `yadup` in `config.neon` (on the same level as `extensions`). Accepted parameters with their default values are following:

```neon
yadup:
	dbUpdateTable: '_db_update'
	dbConnection: '@nette.database.default'
	definerUser: '' # this definer will apply only on queries that have one already set
	definerHost: ''
	sqlDir: '%appDir%/sql' # directory with sql script files
	sqlExt: '.sql' # extension of sql files; with 'dot'
```
	

## Notes
For updator to work it is setting its own mapping to `Yadup\\*Module\\*Presenter` which shouldn't affect you in any way.

**Full DB update** in used terminology is the one that should contain `DROP DATABASE` or at least `DROP TABLE` to prevent possible incompatibilities with consequent updates.

File naming format for SQL update files is `Y-m-d_H-i-s[_full].sql`