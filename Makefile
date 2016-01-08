install-deps:
	composer install

test:
	vendor/bin/phpunit tests

lint:
	vendor/bin/parallel-lint fwk tests

qa: lint test

.PHONY: test lint qa
