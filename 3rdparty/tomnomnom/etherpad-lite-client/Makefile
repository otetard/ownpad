test:
	./vendor/bin/phpunit --colors --verbose

dev-deps:
	composer install --dev

client:
	php ./tools/generate.php > ./EtherpadLite/Client.php

.PHONY: all test clean
