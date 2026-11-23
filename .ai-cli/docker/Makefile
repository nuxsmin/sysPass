# AI-session compatibility target.
#
# `scripts/docker/Makefile` is the single shared image build entry point for
# contributors, CI and AI sessions. This file only delegates so the Dockerfile,
# build context and recipe stay in one place.
COMPOSER_VERSION ?= 2.7

.PHONY: build
build:
	$(MAKE) -C ../../scripts/docker build COMPOSER_VERSION="$(COMPOSER_VERSION)"
