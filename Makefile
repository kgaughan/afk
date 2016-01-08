install-deps:
	composer install

test:
	vendor/bin/phpunit tests

.PHONY: test
