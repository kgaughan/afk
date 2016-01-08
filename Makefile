install-deps:
	composer install

test:
	vendor/bin/phpunit tests

lint:
	vendor/bin/parallel-lint fwk tests

docs:
	vendor/bin/phpdoc run -d fwk -i fwk/templates -t _docs --title="AFK"

qa: lint test

.PHONY: test lint qa docs
