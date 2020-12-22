.DEFAULT_GOAL := help
COMPOSER   = composer

#
### PROJECT
# --------
#

install: ## Install the project
	${COMPOSER} install
.PHONY: install

reset: clean install ## Stop and start a fresh install of the project
.PHONY: reset

clean: ## Stop the project and remove generated files
	rm -rf vendor
.PHONY: clean

#
### TESTS
# -------
#

test: test.composer test.phpcs test.phpunit ## Run all tests
.PHONY: test

test.composer: ## Validate composer.json
	composer validate

test.phpcs: ## Run PHP CS Fixer in dry-run
	composer run -- phpcs --dry-run -v

test.phpcs.fix: ## Run PHP CS Fixer and fix issues if possible
	composer run -- phpcs -v

test.phpunit: ## Run PHPUnit tests
	composer run tests

#
### OTHERS
# --------
#

help: SHELL=/bin/bash
help: ## Dislay this help
	@IFS=$$'\n'; for line in `grep -h -E '^[a-zA-Z_#-]+:?.*?## .*$$' $(MAKEFILE_LIST)`; do if [ "$${line:0:2}" = "##" ]; then \
	echo $$line | awk 'BEGIN {FS = "## "}; {printf "\n\033[33m%s\033[0m\n", $$2}'; else \
	echo $$line | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'; fi; \
	done; unset IFS;
.PHONY: help
