COMPOSER_VERSION=2.7

.PHONY: build
build:
	docker build \
	--build-arg "COMPOSER_VERSION=${COMPOSER_VERSION}" \
	--pull \
	--tag php-testing-composer:latest \
	--tag php-testing-composer:${COMPOSER_VERSION} \
	--file Dockerfile.composer \
	.
