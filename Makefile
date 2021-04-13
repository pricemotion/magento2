all : vendor/autoload.php
.PHONY : all

test : vendor/autoload.php
	vendor/bin/phpunit test
.PHONY : test

watch-test :
	git ls-files | entr -cr $(MAKE) test
.PHONY : watch-test

watch :
	git ls-files | entr -cr find www/var/cache -type f -delete
.PHONY : watch

vendor/autoload.php : composer.json composer.lock
	composer install
	touch $@
