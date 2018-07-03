// Copyright (c) 2016 The btcsuite developers
// Copyright (c) 2015-2016 The Decred developers
// Use of this source code is governed by an ISC
// license that can be found in the LICENSE file.

// Sample blocknofify tool compatible with decred
// will call the standard blocknotify yiimp tool on new block event.

package main

import (
	"io/ioutil"
	"log"
	"os/exec"
	"path/filepath"
	"time"

	"github.com/decred/dcrd/chaincfg/chainhash"
	"github.com/decred/dcrrpcclient"
//	"github.com/decred/dcrutil"
)

const (
	processName = "blocknotify"    // set the full path if required
	stratumDest = "yaamp.com:5744" // stratum host:port
	coinId = "1574"                // decred database coin id

	walletUser = "yiimprpc"
	walletPass = "myDecredPassword"

	debug = false
)

func main() {
	// Only override the handlers for notifications you care about.
	// Also note most of these handlers will only be called if you register
	// for notifications.  See the documentation of the dcrrpcclient
	// NotificationHandlers type for more details about each handler.
	ntfnHandlers := dcrrpcclient.NotificationHandlers{
		OnBlockConnected: func(hash *chainhash.Hash, height int32, time time.Time, vb uint16) {

			// Find the process path.
			str := hash.String()
			args := []string{ stratumDest, coinId, str }
			out, err := exec.Command(processName, args...).Output()
			if err != nil {
				log.Printf("err %s", err)
			} else if debug {
				log.Printf("out %s", out)
			}

			if (debug) {
				log.Printf("Block connected: %s %d", hash, height)
			}
		},
	}

	// Connect to local dcrd RPC server using websockets.
	// dcrwHomeDir := dcrutil.AppDataDir("dcrwallet", false)
	// folder := dcrwHomeDir
	folder := ""
	certs, err := ioutil.ReadFile(filepath.Join(folder, "rpc.cert"))
	if err != nil {
		certs = nil
		log.Printf("%s, trying without TLS...", err)
	}

	connCfg := &dcrrpcclient.ConnConfig{
		Host:         "127.0.0.1:15740",
		Endpoint:     "ws", // websocket

		User:         walletUser,
		Pass:         walletPass,

		DisableTLS: (certs == nil),
		Certificates: certs,
	}

	client, err := dcrrpcclient.New(connCfg, &ntfnHandlers)
	if err != nil {
		log.Fatalln(err)
	}

	// Register for block connect and disconnect notifications.
	if err := client.NotifyBlocks(); err != nil {
		log.Fatalln(err)
	}
	log.Println("NotifyBlocks: Registration Complete")

	// Wait until the client either shuts down gracefully (or the user
	// terminates the process with Ctrl+C).
	client.WaitForShutdown()
}
