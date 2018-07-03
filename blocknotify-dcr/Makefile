OUT_GO=blocknotify-dcr
OUTPUT=blocknotify-dcr

all:
	rm -f $(OUT_GO)
	go build

install: all
	strip -s $(OUT_GO)
	killall -9 $(OUTPUT) || echo ok
	mv $(OUT_GO) ../bin/$(OUTPUT)
	sh -c "../bin/$(OUTPUT) &"

