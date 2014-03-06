
install_dir=/usr/local/bin
install_target=$(install_dir)/ixmon

files=src/ixmon.app \
src/classes/ix_spider.class.php \
src/classes/monitor.class.php  \
src/classes/ncurses.class.php  \
src/classes/Services_JSON.php  \
src/classes/util.func.php

all: ixmon

ixmon: ixmon-build 
	@echo "ixmon built... now run 'make test'"

ixmon-build: clean
	@$(foreach var,$(files), cat $(var) >> .ixmon.build ;) 
	@chmod 755 .ixmon.build

install: test
	@if [[ `whoami` != "root" ]] ;  \
	then echo "Please run make install as root to install to "$(install_target); \
	exit 1; \
	else echo "installing to "$(install_dir) ; \
	fi;
	@if [[ -f .ixmon.build ]] ;  \
	then cp .ixmon.build $(install_target); \
	else echo "can't find ixmon.build" ; \
	fi;
	

test: ixmon-build 
	@echo "testing build... "
	@echo
	@if [[ `php -l .ixmon.build` == "No syntax errors"* ]] ;  \
	then echo 'Looks good... now run 'make install' '; \
	else \
	$(foreach var,$(files), echo `php -n $(var)`;) exit 1; \
	fi;
	@mv .ixmon.build ixmon
	@echo
	

clean:
	@rm -f .ixmon.build
	@rm -f ixmon
