#!/bin/bash
echo "déployement en test"
tar -cvzf ../wims-loader.tgz --exclude='.env.local' .
ssh loic.villanne1@wims 'rm /home/F20U000a/wims-loader.tgz'
scp ../wims-loader.tgz loic.villanne1@wims:/home/F20U000a/
#rm ../wims-loader.tgz
ssh loic.villanne1@wims 'sudo rm -Rf /var/www/wims-loader/* /var/www/wims-loader/.*'
ssh loic.villanne1@wims 'sudo tar -xvzf wims-loader.tgz -C /var/www/wims-loader/'
ssh loic.villanne1@wims 'sudo cp ~/.env.local /var/www/wims-loader/'
ssh loic.villanne1@wims 'sudo chown wims:wims -R /var/www/wims-loader/'
ssh loic.villanne1@wims 'sudo chown wims:wims -R /var/www/wims-loader/'
ssh loic.villanne1@wims 'sudo -u wims /var/www/wims-loader/bin/console cache:clear'