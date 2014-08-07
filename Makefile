all:
	@echo "make composer"
	@echo "make clean-asset"

composer:
	curl -sS https://getcomposer.org/installer | php

server:
	php -S localhost:8080 -t public

server-127:
	php -S 127.0.0.1:8080 -t public

clean-asset:
	rm -rf cache/asset/*
	rm -rf public/static/asset/*

	touch cache/asset/.gitkeep
	touch public/static/asset/.gitkeep
