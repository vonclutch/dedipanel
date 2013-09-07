#!/bin/bash

if [ `id -u` -ne 0 ]; then
	echo "Vous devez lancer ce script avec les droits root."
	exit 1;
fi

verify_packet () {
	# V�rifie que tous les packets n�cessaires sont install�s
	if [ `dpkg-query -W --showformat='${Status}\n' $1 | grep 'install ok installed' | wc -l` -ge 1 ]; then
		echo 1
	else
		echo 0
	fi
}

case "$1" in
    install)
        # Dl de la derniere maj du panel
        git clone http://github.com/NiR-/dedipanel.git $2
        cd $2
		
        # Copie du fichier de config et des htaccess
        cp app/config/parameters.yml.dist app/config/parameters.yml
		cp web/.htaccess.dist web/.htaccess
		cp .htaccess.dist .htaccess
		
		# Ajout de la config apache du panel
		if [ ! -e /etc/apache2/conf.d/dedipanel ]; then
			echo -e "<Directory /var/www/>\n
					AllowOverride All\n
				</Directory>" > /etc/apache2/conf.d/dedipanel
			
			service apache2 restart
		fi
		
		# T�l�chargement des d�pendances
		curl -s https://getcomposer.org/installer | php
		php composer.phar install --optimize-autoloader --prefer-dist
		
		# Vidage du cache et installation des assets
		php app/console cache:clear --env=prod --no-warmup
		php app/console cache:clear --env=installer --no-warmup
		php app/console assets:install --env=installer
		
		# Modif des droits et du propri�taire
        chmod 775 ./
		chown -R www-data:www-data ./
		
		echo "Il ne vous reste plus qu'� indiquer votre adresse IP personnelle dans le fichier $2/installer_whitelist.txt afin d'acc�der � l'installateur en ligne."

        exit ${?}
    ;;
	
    update)
        cd $2
		
        # On dl les derniers commits (sans merger)
        git fetch --all
        # Puis on remet automatiquement le depot local a jour
        git reset --hard origin/master
		
		# Mise � jour de composer et mise � jour des d�pendances
		php composer.phar self-update
		php composer.phar update --optimize-autoloader --prefer-dist
		
		# T�l�chargement des d�pendances
		curl -s https://getcomposer.org/installer | php
		php composer.phar install --optimize-autoloader --prefer-dist
		
		# Vidage du cache et installation des assets
		php app/console cache:clear --env=prod --no-warmup
		php app/console cache:clear --env=installer --no-warmup
		php app/console assets:install --env=installer
		
		# Modif des droits et du propri�taire
        chmod 775 ./
		chown -R www-data:www-data ./
		
		echo "Il ne vous reste plus qu'� indiquer votre adresse IP personnelle dans le fichier $2/installer_whitelist.txt afin d'acc�der � l'installateur en ligne."

        exit ${?}
    ;;
	
	verify)
		# Tableau contenant la liste des erreurs
		errors=()
		
		# V�rifie que tous les packets n�cessaires sont install�s
		packets=('git' 'sqlite' 'mysql-server' 'php5-sqlite' 'apache2' 'php5' 'php5-mysql' 'curl' 'php5-intl' 'php-apc' 'phpmyadmin')
		failed=()
		
		for packet in "${packets[@]}"; do
			if [ $(verify_packet $packet) -eq 0 ]; then
				failed=("${failed[@]}" $packet)
				
				if [[ ! ${errors[*]} =~ "packet" ]]; then
					errors=("${errors[@]}" "packet")
				fi
			fi
		done
		
		if [ ${#failed[@]} -ge 1 ]; then
			echo "Packets n�cessaires: ${packets[@]}."
			echo "Packets manquants: ${failed[@]}."
		fi
		
		# V�rifie que le mode rewrite d'apache est activ�
		if [ ! -e /etc/apache2/mods-enabled/rewrite.load ]; then
			errors=("${errors[@]}" "mod_rewrite")
			echo "Le mode rewrite d'apache doit �tre activ� (a2enmod rewrite && service apache2 restart)."
		fi
		
		# V�rifie la pr�sence de suhosin.executor.include.whitelist dans la config de php
		if [ -z "`sed -ne '/^suhosin.executor.include.whitelist/p' /etc/php5/cli/php.ini`" ]; then
			errors=("${errors[@]}" "suhosin_phar")
			echo "Vous devez la ligne suivante au fichier /etc/php5/cli/php.ini : suhosin.executor.include.whitelist = phar"
		fi
		
		# V�rifie s'il y a eu des erreurs d'enregistr�es
		if [ ${#errors[@]} -ge 1 ]; then
			echo "Veuillez effectuer les op�rations n�cessaire afin d'installer le panel."
		else
			echo "Votre serveur est correctement configur�. Vous pouvez y installer le panel."
		fi
	;;
	
    *)
        echo "Usage: $0 [install dir|update dir|verify]"
        exit ${?}
    ;;
esac
