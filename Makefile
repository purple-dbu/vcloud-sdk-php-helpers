EXT := $$(echo $$OS | egrep ^Windows >/dev/null && echo -n .bat)

DOC := docs

SHOW_COVERAGE = \
	[ -e build/logs/coverage.txt ] \
	&& echo \
	&& echo ======== Code coverage ======== \
	&& cat build/logs/coverage.txt | grep -A3 Summary | tail -n 3 \
	&& echo ===============================


all: dependencies
	make test && make docs


# ==============================================================================
# Dependencies
# ==============================================================================

dependencies: vendor
	# ┌─────────────────────────────┐
	# │ Dependencies are up-to-date │
	# └─────────────────────────────┘

vendor: composer.phar composer.json
	[ -e composer.lock ] && php -d memory_limit=-1 composer.phar update || php -d memory_limit=-1 composer.phar install
	touch vendor

composer.phar:
	curl -s http://getcomposer.org/installer | php
	# ┌──────────────────────────────────────┐
	# │ Downloaded Composer in composer.phar │
	# └──────────────────────────────────────┘


# ==============================================================================
# Cleaning
# ==============================================================================

clean:
	[ ! -d vendor ] || rm -Rf vendor
	[ ! -e composer.lock ] || rm -f composer.lock
	[ ! -e composer.phar ] || rm -f composer.phar
	[ ! -d tests/_files ] || rm -Rf tests/_files
	[ ! -e tests/_files.tar.gz ] || rm -Rf tests/_files.tar.gz
	# ┌─────────┐
	# │ Cleaned │
	# └─────────┘


# ==============================================================================
# Testing
# ==============================================================================

# Linting
lint: dependencies
	vendor/bin/phpcs$(EXT) --standard=PSR1 src/ tests/ \
	&& vendor/bin/phpcs$(EXT) --standard=PSR2 src/ tests/

tests/_files.tar.gz: src tests/Unit
	# ┌──────────────────────────────────────────────┐
	# │ Stubs are out of date in tests/_files.tar.gz │
	# │ Please run `make stubs` to re-generate them  │
	# └──────────────────────────────────────────────┘
	exit 1

tests/_files: tests/_files.tar.gz
	[ -d tests/_files ] || mkdir -p tests/_files
	tar xvfz tests/_files.tar.gz -C tests/_files

# Unit tests (using stubs)
test: dependencies tests/_files
	make lint \
	&& vendor/bin/phpunit$(EXT) \
		--coverage-html build/logs/coverage \
		--coverage-text=build/logs/coverage.txt \
		--coverage-clover=build/logs/clover.xml \
	&& $(SHOW_COVERAGE)

# Integration tests
integration: dependencies
	make lint \
	&& vendor/bin/phpunit$(EXT) \
		--coverage-html build/logs/coverage \
		--coverage-text=build/logs/coverage.txt \
		--configuration phpunit-integration.xml \

# Stubs (tests/_files.tar.gz) generation
stubs: dependencies
	[ ! -d tests/_files ] || rm -Rf tests/_files \
	&& [ ! -e tests/_files.tar.gz ] || rm -Rf tests/_files.tar.gz \
	&& make lint \
	&& vendor/bin/phpunit$(EXT) --configuration phpunit-stubs.xml \
	&& cd tests/_files/ && tar cvfz ../_files.tar.gz *.json \
	&& rm -Rf tests/_files
	# ┌─────────────────┐
	# │ Generated stubs │
	# └─────────────────┘


# ==============================================================================
# Documentation
# ==============================================================================

doc: $(DOC)
	# ┌─────────────────────────────┐
	# │ Documentation is up-to-date │
	# └─────────────────────────────┘

$(DOC): dependencies src
	[ ! -d "$(DOC)" ] || rm -Rf "$(DOC)"
	mkdir -p "$(DOC)"
	vendor/bin/phpdoc.php \
		--directory src/ \
		--target "$(DOC)" \
		--title "vCloud PHP SDK Helpers" \
		--template responsive-twig
	touch docs

publish:
	git add docs && git commit -m "Updated API documentation"
	git subtree push --prefix docs origin gh-pages
