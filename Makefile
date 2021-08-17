VENDOR_ANALYSIS := vendor-bin/analysis/vendor

.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
.PHONY: help

static-analysis: | psalm ## runs static analysis
.PHONY: static-analysis

psalm: | vendor-bin ## runs psalm
	php ./vendor/bin/psalm
.PHONY: psalm

ecs-dry: | vendor-bin ## checks codestyle with ecs
	php ./vendor/bin/ecs check src tests
.PHOY: ecs-dry

ecs-fix: | vendor-bin ## fixes codestyle with ecs
	php ./vendor/bin/ecs check --fix src tests
.PHOY: ecs-dry

test: | vendor ## runs phpunit
	php ./vendor/bin/phpunit --configuration phpunit.xml.dist
.PHONY: test

test-coverage: | vendor ## runs phpunit with coverage
	php -d pcov.enabled=1 -d pcov.directory=./src ./vendor/bin/phpunit \
       --configuration phpunit.xml.dist \
       --coverage-clover build/artifacts/phpunit.clover.xml \
       --coverage-html build/artifacts/phpunit-coverage-html
.PHONY: test-coverage

test-coverage-cobertura: | vendor ## runs phpunit with coverage
	php -d pcov.enabled=1 -d pcov.directory=./src ./vendor/bin/phpunit \
		--configuration phpunit.xml.dist \
		--coverage-cobertura build/artifacts/cobertura-coverage.xml
.PHONY: test-coverage-cobertura

vendor:
	composer install --no-interaction --optimize-autoloader --no-suggest --no-scripts --no-progress

vendor-bin: | vendor $(VENDOR_ANALYSIS) ## installs static analysis tools
.PHONY: vendor-bin

$(VENDOR_ANALYSIS):
	composer bin analysis install --prefer-dist --no-progress