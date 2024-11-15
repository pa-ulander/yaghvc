# Default environment file
ENVIRONMENT_FILE=$(shell pwd)/.env

# Default directory
PROJECT_DIRECTORY=$(shell pwd)

# Available docker containers
CONTAINERS=yagvc-app db 

#####################################################
# RUNTIME TARGETS			 						#
#####################################################
default: up

##@ Start all containers
up: post-build-actions prerequisite
	- npx kill-port 3306
	- npx kill-port 443
	- npx kill-port 80
	- docker-compose -f docker-compose.yml up -d

##@ Stop all containers
down:
	- docker-compose -f docker-compose.yml down

##@ Start individual container: $make start container=<name>
start: prerequisite
	- docker-compose -f docker-compose.yml up -d $(container)

##@ Stop individual container
stop: prerequisite
	- docker-compose -f docker-compose.yml down $(container)

##@ Halts all containers
halt: prerequisite
	- docker-compose -f docker-compose.yml kill

##@ Restarts & rebuilds all containers
restart: prerequisite
	- docker-compose -f docker-compose.yml kill && docker-compose -f docker-compose.yml up -d

##@ Backup database
backup:
	- docker-compose exec db bash -c "mysqldump -udb -pdb -hlocalhost db > "/db_backups/backup-$(shell date +%y-%m-%d_%H%M%S).sql""
	- cd ../ && zip -r -q "./backups/backup-$(shell date +%y-%m-%d_%H%M%S).zip" .

#####################################################
# SETUP AND BUILD TARGETS			 				#
#####################################################

##@ Build and prepare the docker containers and the project
build: prerequisite build-containers build-project update-project post-build-actions launch-dependencies

##@ Build and launch the containers
build-containers:
	- docker-compose -f docker-compose.yml up -d --build

##@ Delete and rebuild all from scratch (clean)
rebuild: clean prerequisite rebuild-containers build-project update-project post-build-actions launch-dependencies
rebuild-containers:
	- docker-compose -f docker-compose.yml build --no-cache && docker-compose up -d --build

##@ Build the project
build-project: prepare-containers

##@ Install project and dependencies
install-project:
	# Update the composer dependencies
	- docker-compose exec yagvc-app composer --ansi install

##@ Update project and dependencies
update-project:
	# Update the composer dependencies
	- docker-compose exec yagvc-app composer --ansi update

##@ Upgrade project and dependencies
upgrade-project:
	# Update the composer dependencies
	- docker-compose exec yagvc-app composer --ansi upgrade

##@ Run actions after setup
post-build-actions:
    # set permissions
	- docker-compose exec yagvc-app bash -c "chown -R $(DEVUSER):$(DEVUSER) ."

##@ Setup development database
setup-db: prompt-continue
	- docker exec -it -u root db /bin/bash -c "chown -R mysql:mysql /var/lib/mysql/ && chmod -R 755 /var/lib/mysql/"
	- docker exec -it -u root yagvc-app /bin/bash -c "php artisan command:setup-database"

##@ Launch application dependencies
launch-dependencies:
	# Launch startup script(s)
	# - docker-compose exec yagvc-app bash -c "sh -c '/tmp/run.sh'"

##@ Inspect/view network
inspect-network:
	- docker network inspect backend-network

##@ Remove the docker containers and deletes project dependencies
clean: post-build-actions prerequisite prompt-continue
	- docker exec -u root -t -i db /bin/bash -c "chown -R $(DEVUSER):$(DEVUSER) ./docker"
	# Remove the dependencies
	- rm -rf ./vendor
	- rm -rf ./docker/var

	# Remove the docker containers
	- docker-compose down --rmi all --volumes --remove-orphans

	# Remove all unused volumes
	- docker volume prune -f

	# Remove all unused images
	- docker images prune -a

##@ View containers status
status: prerequisite
	- docker-compose -f docker-compose.yml ps


#####################################################
# BASH CLI TARGETS			 						#
#####################################################

##@ Opens a bash prompt to the yagvc-app container
bash: prerequisite
	# - docker-compose exec --env COLUMNS=`tput cols` --env LINES=`tput lines` yagvc-app bash
	# - docker exec -it yagvc-app bash -c "sudo -u root /bin/bash"
	- docker exec -it yagvc-app bash -c "sudo -u $(DEVUSER) /bin/bash"

##@ Opens a bash prompt to the db container
bash-db: prerequisite
	- docker-compose exec --env COLUMNS=`tput cols` --env LINES=`tput lines` db bash
	# - docker exec -it db bash -c "sudo -u $(DEVUSER) /bin/bash"

#################################################
# TEST TARGETS			 						#
#################################################

##@ Launch unit tests
test-php:
	@echo "Start phpunit tests";
	docker-compose exec yagvc-app bash -c "cd /var/www/html && composer test"


#################################################
# INTERNAL TARGETS			 					#
#################################################

##@ Validates the prerequisites such as environment variables
prerequisite: check-environment
	@echo "pwd: "$(shell pwd)
include .env
export ENV_FILE = $(ENVIRONMENT_FILE)

##@ Validates the environment variables
check-environment:
	@echo "Validating environment";
ifeq (, $(shell which docker-compose))
	$(error "No docker-compose in $(PATH), consider installing docker")
endif

##@ Validate containers
validate-containers:
ifeq ($(filter $(filter-out $@,$(MAKECMDGOALS)),$(CONTAINERS)),)
	$(error Invalid container provided "$(filter-out $@,$(MAKECMDGOALS))")
endif

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


##@ Show help
help:
	@printf "\033[1;33mUsage:\033[0m\n  make \033[36m<target>\033[0m\n"
	@printf "%-30s %s\n" "Target" "Description"
	@printf "%-30s %s\n" "------" "-----------"
	@awk 'BEGIN {FS = ":.*?##@ |^##@ "} /^##@ / {getline x; split(x, a, ":"); \
	printf "\033[36m%-30s\033[0m %s\n", a[1], $$2}' \
	$(MAKEFILE_LIST)
