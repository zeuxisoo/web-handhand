# HandHand

A toy porject for my friend faster way to build simple secord hand website.

## Installation

Install dependencies

	php composer.phar install

Update settings

	cp config/default.php.sample config/default.php
	vim config/default.php
	
Migrate database

	php vendor/bin/phpmig migrate

Run website

	make server

## Locale

Generate locale directory

	php finger locale:create en_US
	
Update message file in all locale directories

	php finger locale:update

Clear messages in `all` or `target` locale directory

	php finger locale:clear
	php finger locale:clear [en_US]

Remove locale directory

	php finger locale:remove en_US

## License

BSD