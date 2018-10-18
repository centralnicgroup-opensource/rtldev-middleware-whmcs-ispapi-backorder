ISPAPI_BACKORDER_MODULE_VERSION := $(shell php -r 'include "ispapibackorder.php"; print $$module_version;')
FOLDER := pkg/whmcs-ispapi-backorder-$(ISPAPI_BACKORDER_MODULE_VERSION)

clean:
	rm -rf $(FOLDER)

buildsources:
	mkdir -p $(FOLDER)/install/modules/addons/ispapibackorder
	cp *.md HISTORY.old LICENSE *.pdf $(FOLDER)
	cp *.php $(FOLDER)/install/modules/addons/ispapibackorder
	cp -a api backend controller crons lang templates $(FOLDER)/install/modules/addons/ispapibackorder
	find $(FOLDER)/install -name "*~" | xargs rm -f
	find $(FOLDER)/install -name "*.bak" | xargs rm -f
	rm -f $(FOLDER)/install/modules/addons/ispapibackorder/crons/batch_test.php

buildlatestzip:
	cp pkg/whmcs-ispapi-backorder.zip ./whmcs-ispapi-backorder-latest.zip # for downloadable "latest" zip by url

zip:
	rm -rf pkg/whmcs-ispapi-backorder.zip
	@$(MAKE) buildsources
	cd pkg && zip -r whmcs-ispapi-backorder.zip whmcs-ispapi-backorder-$(ISPAPI_BACKORDER_MODULE_VERSION)
	@$(MAKE) clean

tar:
	rm -rf pkg/whmcs-ispapi-backorder.tar.gz
	@$(MAKE) buildsources
	cd pkg && tar -zcvf whmcs-ispapi-backorder.tar.gz whmcs-ispapi-backorder-$(ISPAPI_BACKORDER_MODULE_VERSION)
	@$(MAKE) clean

allarchives:
	rm -rf pkg/whmcs-ispapi-backorder.zip
	rm -rf pkg/whmcs-ispapi-backorder.tar
	@$(MAKE) buildsources
	cd pkg && zip -r whmcs-ispapi-backorder.zip whmcs-ispapi-backorder-$(ISPAPI_BACKORDER_MODULE_VERSION) && tar -zcvf whmcs-ispapi-backorder.tar.gz whmcs-ispapi-backorder-$(ISPAPI_BACKORDER_MODULE_VERSION)
	@$(MAKE) buildlatestzip
	@$(MAKE) clean