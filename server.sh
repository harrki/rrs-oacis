#!/bin/sh

cd `dirname $0`

echo 'Start the server'
php -S localhost:6040 -t src