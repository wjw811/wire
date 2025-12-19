.PHONY: build
build:
	@mkdir -p bin 
	@cp *.yml bin
	go build
	@mv pomo bin

.PHONY: clear
clear: ## Clear the working area and the project
	rm -rf bin/

.PHONY: run
run:
	@cd bin && ./pomo

.PHONY: debug
debug:
	@cd bin && dlv exec ./pomo
