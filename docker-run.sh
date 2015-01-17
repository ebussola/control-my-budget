#!/bin/bash

docker run -it -v "$(pwd)":/var/www -w /var/www controlmybudget "$@"