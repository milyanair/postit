# Post It build. Usage: make dist | make sign KEY=~/.nextcloud/certificates/postit.key
APP     := postit
VERSION := $(shell sed -n 's:.*<version>\(.*\)</version>.*:\1:p' appinfo/info.xml)
BUILD   := build
TARBALL := $(BUILD)/$(APP)-$(VERSION).tar.gz
KEY     ?= $(HOME)/.nextcloud/certificates/$(APP).key

.PHONY: dist sign clean
dist: clean
	mkdir -p $(BUILD)/stage/$(APP)
	tar cf - --exclude=./build --exclude=./.git --exclude=./Makefile --exclude=./APPSTORE-CHECKLIST.md --exclude=./screenshots --exclude=./tools --exclude=./.gitignore . | tar xf - -C $(BUILD)/stage/$(APP)
	tar czf $(TARBALL) -C $(BUILD)/stage $(APP)
	rm -rf $(BUILD)/stage
	@echo "Built $(TARBALL)"
sign: dist
	openssl dgst -sha512 -sign $(KEY) $(TARBALL) | openssl base64 -A > $(TARBALL).sig
	@echo "Signature: $(TARBALL).sig (paste into the app store release form)"
clean:
	rm -rf $(BUILD)/stage $(BUILD)/*.tar.gz $(BUILD)/*.sig
