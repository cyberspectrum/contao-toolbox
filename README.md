[![Build Status](https://travis-ci.org/cyberspectrum/contao-toolbox.png)](https://travis-ci.org/cyberspectrum/contao-toolbox)
[![Latest Version tagged](http://img.shields.io/github/tag/cyberspectrum/contao-toolbox.svg)](https://github.com/cyberspectrum/contao-toolbox/tags)
[![Latest Version on Packagist](http://img.shields.io/packagist/v/cyberspectrum/contao-toolbox.svg)](https://packagist.org/packages/cyberspectrum/contao-toolbox)
[![Installations via composer per month](http://img.shields.io/packagist/dm/cyberspectrum/contao-toolbox.svg)](https://packagist.org/packages/cyberspectrum/contao-toolbox)

Contao Toolbox
==============

This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex and
pull translations from transifex and convert them back to Contao language files.

It can be used and distributed either as set of seperate files (this git repository) or compiled to a `phar` file.

## How to get it

### via composer

1. Download composer (if not done already)
```
curl -sS https://getcomposer.org/installer | php
```

2. Install the application.
```
php composer create-project --prefer-source cyberspectrum/contao-toolbox
```
**Hint:** At the end of the installation composer will ask you to remove the vcs history:

> Do you want to remove the existing VCS (.git, .svn..) history? [Y,n]?

You must not confirm this question, type `n` to keep the history, otherwise the compiler will not work!

### via plain git cloning
```
git clone https://github.com/discordier/contao-toolbox.git
```

When using Git, update the contao-toolbox repository and all dependencies via composer at the beginning.
```
php composer update
```

#### Optionally compile the `ctb.phar` binary for distribution.

After fetching the source, you can use the shipped compiler to generate a `phar` file to install in a system wide path
for easier usage.
```
./bin/compile-ctb
```

Note: this requires the php.ini setting:
```
[Phar]
phar.readonly = Off
```
See also http://php.net/phar.readonly

## How to use it.

For brevity reasons, we only use the ctb.phar notation in the examples, if you have not compiled the phar file you can
swap each `ctb.phar` by `path/to/ctb` in the following commands.
We furthermore assume that the `ctb.phar` is in the search path of your system, if not please use the full path to the binary
then.

## Commands

The Contao Toolbox currently provides the following commands:

*   `help`
    Displays help for a command
*   `list`
    Lists commands
*   `download-transifex`
    Download xliff translations from transifex.
*   `upload-transifex`
    Upload xliff translations to transifex.
*   `from-xliff`
    Update Contao language files from xliff translations.
*   `to-xliff`
    Update xliff translations from Contao base language.
*   `cleanup-tx`
    Purges the defined .tx folder.

### The `help` command.
Display information about a certain command.

You can issue `ctb.phar help <command>` to get detailed information about a specific command.

NOTE: the output of the help command may be more up to date than this README.md file.

### The `list` command.
List the available commands.

### Common parameters to all commands

All commands (except for help and list) handle certain parameters.

Some of these parameters can be omitted as the tool will then retrieve them from composer.json from the sub key `extra/contao/transifex`

If neither defined via command line or composer.json, the tool will fallback to from the global configuration in the
user's home directory.

The config file name to use is determined as:
If the environment variable `CBT_HOME` has been defined, this is the home directory to use.
If running on Windows, the environment variable `APPDATA` is used and suffixed with `/CBT/config.json`.
If running on any other OS, the environment variable `HOME` is being examined and suffixed with `.config/ctb/config.json`.

##### --working-dir (-d)
If specified, use the given directory as working directory.
This is useful for the location of the `composer.json` file to use as this will always be loaded from the current
working directory.

##### --contao (-c)
Contao language root directory (base to "en","de" etc.).
If this is not given, it will first tried to read it from the composer.json of the current working directory and second
tried to retrieve it from the global configuration.
This usually is something like `system/modules/<extension name>/languages/`

This value will get read from the key `extra/contao/transifex/languages_cto` in composer.json if omitted.

__Example:__
If we have the extension acme-core and are within the contao core root directory, we will pass:
`-c src/system/modules/acme-core/languages`

##### --xliff (-x)
Xliff root directory (base to "en","de" etc.), if empty it will get read from the composer.json.
This can be any path where the xlf files shall be stored locally. Note that this tool will create a subdirectory
for every language in use. Use the command `cleanup-tx` to quickly clean up this folder right from the command line.

This value will get read from the key `extra/contao/transifex/languages_tx` in composer.json if omitted.

##### --projectname (-p)
This is the name of the project on transifex.
This value will get read from the key `extra/contao/transifex/project` in composer.json if omitted.

##### --prefix
The prefix for all language files, if empty it will get read from the composer.json.
This tool provides the possibility to limit the resources on transifex it will take into account by some prefix.
Using this approach allows to store the language files of multiple sub projects within a single transifex project.

This value will get read from the key `extra/contao/transifex/prefix` in composer.json if omitted.

__Example:__
Assume we have the Contao extension "acme" with the sub projects "acme-core" and "acme-more".
We want to have the language file `default.php` from "acme-core" to be handled as `core-default` and the file from
"acme-more" shall get stored as `more-default` on transifex.

We now need to pass `--prefix core` when working with the "acme-core" directory and `--prefix more` when dealing with
the "acme-more" directory.

##### --base-language (-b)  The base language to use. (default: "en")
This defines the language to be used as source language on transifex and in the xlf files.

##### --skip-files (-s)
This option can be used to skip certain language files.

This value will get read from the key `extra/contao/transifex/skip_files` in composer.json if omitted.

__Example:__
Assume we have a file named "skipme.php" in the languages folder that does not have any related data on transifex.
By adding "skipme" to the list of files to skip, this will not be considered.

### Managing translations on transifex.

#### Common parameters to transifex commands

##### --user (-U)
This is the transifex user to be used.
If this is not given, the tool first checks the global configuration for a username in the key `/transifex/user`.
If there is no user provided, the tool checks the environment variable `transifexuser`.
If still no user has been defined, the tool interactively asks on the command line.

##### --pass (-P)
This is the password for the given transifex user.
If this is not given, the tool first checks the global configuration for a password in the key `/transifex/pass`.
If there is no user provided, the tool checks the environment variable `transifexpass`.
If still no password has been defined, the tool interactively asks on the command line.

###### --mode (-m)
This parameter is optional and defaults to: "reviewed".

The download mode to use (reviewed, translated, default).

#### Command `download-transifex`

This command downloads all xlf files for the given languages from transifex.
It takes a single argument consisting of either the keyword `all` or a comma separated list of language keys.

Example 1 (download all available languages):
`ctb.phar download-transifex all`

Example 2 (download the languages German, English and French):
`ctb.phar download-transifex de,en,fr`

#### Command `upload-transifex`

This command uploads all xlf files for the given languages from transifex.
It takes a single argument consisting of either the keyword `all` or a comma separated list of language keys.

Example 1 (download all available languages):
`ctb.phar upload-transifex all`

Example 2 (download the languages German, English and French):
`ctb.phar upload-transifex de,en,fr`

### Commands for transforming the XLIFF (xlf) files to/from Contao language files.

#### Common parameters to transforming commands

###### --cleanup
If this is passed, obsolete files will get removed. All files not present in the corresponding source section or empty
files will get deleted in the destination.

#### Command `from-xliff`

This command will convert the xliff files from the defined transifex folder into the Contao folder for the given
languages.

It takes a single argument consisting of either the keyword `all` (default) or a comma separated list of language keys.

#### Command `to-xliff`

This command will convert the xliff files from the defined Contao folder into files in transifex folder for the given
languages.

It takes a single argument consisting of either the keyword `all` (default) or a comma separated list of language keys.


## Example configuration

### Example `composer.json` except.
```json
{
	"extra":{
		"contao": {
			"transifex": {
				"project": "acme-core",
				"prefix": "core-",
				"languages_cto": "src/system/modules/acme-core/languages",
				"languages_tx": ".tx"
			}
		}
	}
}
```

### example `$HOME/.config/ctb/config.json`
```json
{
	"transifex": {
		"user": "john-doe",
		"pass": "sUp3rPassword!"
	}
}
```

### Common usage

```
# Convert all .php files to .xlf files updating existing ones.
user@host:~/some/project$ ctb.phar to-xliff

# Upload the xlf files to transifex (adding new ones and new language strings to existing ones).
user@host:~/some/project$ ctb.phar upload-transifex

# Download new translation strings from transifex.
user@host:~/some/project$ ctb.phar download-transifex -m translated

# Convert all received xlf files back to php files in their corresponding location.
user@host:~/some/project$ ctb.phar from-xliff

# Finally clean up the ".tx" folder
user@host:~/some/project$ ctb.phar cleanup-tx
```
