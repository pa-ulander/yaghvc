# Default environment file
ENVIRONMENT_FILE=$(shell pwd)/.env

# Default directory
PROJECT_DIRECTORY=$(shell pwd)

# Available docker containers
CONTAINERS=app db 

#####################################################
# RUNTIME TARGETS			 						#
#####################################################
default: up

# Start all containers
up: post-build-actions prerequisite
	- docker-compose -f docker-compose.yml up -d
# Stop all containers
down:
	- docker-compose -f docker-compose.yml down

# Start & rebuild an individual container
start: prerequisite valid-container
	- docker-compose -f docker-compose.yml up -d --build $(filter-out $@,$(MAKECMDGOALS))

# Stop individual container
stop: prerequisite valid-container
	- docker-compose -f docker-compose.yml down $(filter-out $@,$(MAKECMDGOALS))

# Halts all containers
halt: prerequisite
	- docker-compose -f docker-compose.yml kill

# Restarts & rebuilds all containers
restart: prerequisite
	- docker-compose -f docker-compose.yml kill && docker-compose -f docker-compose.yml up -d

backup:
	- docker-compose exec db bash -c "mysqldump -udb -pdb -hlocalhost db > "/db_backups/backup-$(shell date +%y-%m-%d_%H%M%S).sql""
	- cd ../ && zip -r -q "./backups/backup-$(shell date +%y-%m-%d_%H%M%S).zip" .

#####################################################
# SETUP AND BUILD TARGETS			 				#
#####################################################
# Build and prepare the docker containers and the project
build: prerequisite build-containers build-project update-project post-build-actions launch-dependencies

# Build and launch the containers
build-containers:
	- docker-compose -f docker-compose.yml up -d --build

# Build the project
build-project: prepare-containers

# Update the project and the dependencies
install-project:
	# Update the composer dependencies
	- docker-compose exec app composer --ansi install

# Update the project and the dependencies
update-project:
	# Update the composer dependencies
	- docker-compose exec app composer --ansi update

# Update the project and the dependencies
upgrade-project:
	# Upgrade the composer dependencies
	- docker-compose exec app composer --ansi upgrade

## Run actions after setup
post-build-actions:
    # set permissions
	- docker-compose exec app bash -c "chown -R devuser:devuser ./vendor"
	- docker-compose exec app bash -c "chown -R devuser:devuser ./run"

# Setup development database
setup-db: prompt-continue
	- docker exec -u root -t -i db /bin/bash -c "chown -R mysql:mysql /var/lib/mysql/ && chmod -R 755 /var/lib/mysql/"
	- docker-compose exec app bash -c "php artisan command:setup-database"

## Launch application dependencies
launch-dependencies:
	# Launch startup script(s)
	# - docker-compose exec app bash -c "sh -c '/tmp/run.sh'"

## inspect/view the network
inspect-network:
	- docker network inspect app-network

# Remove the docker containers and deletes project dependencies
clean: post-build-actions prerequisite prompt-continue
	- docker exec -u root -t -i db /bin/bash -c "chown -R devuser:devuser ./docker"
	# Remove the dependencies
	- rm -rf ./vendor
	- rm -rf ./docker/var

	# Remove the docker containers
	- docker-compose down --rmi all --volumes --remove-orphans

	# Remove all unused volumes
	- docker volume prune -f

	# Remove all unused images
	- docker images prune -a

# Echos the container status
status: prerequisite
	- docker-compose -f docker-compose.yml ps


#####################################################
# BASH CLI TARGETS			 						#
#####################################################
# Opens a bash prompt to the app container
bash: prerequisite
	# - docker-compose exec --env COLUMNS=`tput cols` --env LINES=`tput lines` app bash
	# - docker exec -it pp bash -c "sudo -u devuser /bin/bash"
	- docker exec -it app bash -c "sudo -u root /bin/bash"

# Opens a bash prompt to the db container
bash-mysql: prerequisite
	- docker-compose exec --env COLUMNS=`tput cols` --env LINES=`tput lines` db bash
	# - docker exec -it db bash -c "sudo -u devuser /bin/bash"

#################################################
# TEST TARGETS			 						#
#################################################
# Launch unit tests
test-php:
	@echo "Start phpunit tests";
	- docker-compose exec app bash -c "cd /var/www/html && composer test"


#################################################
# INTERNAL TARGETS			 					#
#################################################
# Validates the prerequisites such as environment variables
prerequisite: check-environment
	- @echo "pwd: "$(shell pwd)
-include .env
export ENV_FILE = $(ENVIRONMENT_FILE)

# Validates the environment variables
check-environment:
	@echo "Validating environment";

# Check if docker binary exists
ifeq (, $(shell which docker-compose))
	$(error "No docker-compose in $(PATH), consider installing docker")
endif

# Validate containers
valid-container:
ifeq ($(filter $(filter-out $@,$(MAKECMDGOALS)),$(CONTAINERS)),)
	$(error Invalid container provided "$(filter-out $@,$(MAKECMDGOALS))")
endif

# Prompt to continue
prompt-continue:
	@while [ -z "$$CONTINUE" ]; do \
		read -r -p "Would you like to continue? [y]" CONTINUE; \
	done ; \
	if [ ! $$CONTINUE == "y" ]; then \
        echo "Exiting." ; \
        exit 1 ; \
    fi
%:
	@:
