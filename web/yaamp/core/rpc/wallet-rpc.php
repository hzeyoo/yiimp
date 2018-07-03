<?php
/**
 * Common yiimp Wallet RPC object
 */
class WalletRPC {

	public $type = 'Bitcoin';
	protected $rpc;
	protected $rpc_wallet;

	// cache
	protected $account;
	protected $accounts;
	protected $coin;
	protected $info;
	protected $height = 0;

	// Information and debugging
	public $error;
	// public $status;
	// public $raw_response;
	// public $response;

	function __construct($userOrCoin, $pw='', $host='localhost', $port=8332, $url=null)
	{
		if (is_object($userOrCoin)) {

			$coin = $userOrCoin;
			switch ($coin->rpcencoding) {
			case 'GETH':
				$this->type = 'Ethereum';
				$this->account = empty($coin->account) ? $coin->master_wallet : $coin->account;
				$this->rpc = new Ethereum($coin->rpchost, $coin->rpcport);
				break;
			case 'XMR':
				$this->type = 'CryptoNote';
				$this->rpc = new CryptoRPC($coin->rpchost, $coin->rpcport, $coin->rpcuser, $coin->rpcpasswd);
				// for now, assume the wallet in on localhost, and daemon set in the db
				$this->rpc_wallet = new CryptoRPC("127.0.0.1", $coin->rpcport, $coin->rpcuser, $coin->rpcpasswd);
				$this->coin = $coin;
				break;
			default:
				$this->type = 'Bitcoin';
				$this->rpc = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport, $url);
			}

		} else {
			// backward compat
			$user = $userOrCoin;
			$this->rpc = new Bitcoin($user, $pw, $host, $port, $url);
		}
	}

	function __call($method, $params)
	{
		if ($this->type == 'Ethereum') {
			if (!isset($this->accounts)) {
				$this->accounts = $this->rpc->eth_accounts();
				$this->error = $this->rpc->error;
			}
			if (!is_array($this->accounts)) {
				// if wallet is stopped
				return false;
			}
			// convert common methods used by yiimp
			switch ($method) {
			case 'getaccountaddress':
				if (!empty($params[0]))
					return $params[0];
				return $this->account;
			case 'getinfo':
				if (!isset($this->info)) {
					$info = array();
					$info['accounts'] = array();
					$balances = 0;

					foreach ($this->accounts as $addr) {
						// web3.fromWei(eth.getBalance("0x..."), "ether")
						$balance = (double) $this->rpc->eth_getBalance($addr,'latest', true);
						$balance /= 1e18;
						$balances += $balance;
						$info['accounts'][$addr] = $balance;
					}
					$info['balance'] = $balances;
					$this->height = $this->height ? $this->height : $this->rpc->eth_blockNumber();
					$info['blocks'] = $this->height;
					$info['gasprice'] = (double) $this->rpc->eth_gasPrice();
					$info['gasprice'] /= 1e18;
					$info['connections'] = $this->rpc->net_peerCount();
					$info['version'] = $this->rpc->web3_clientVersion();
					$this->info = $info;
				}
				return $this->info;
			case 'getdifficulty':
				$this->height = $this->height ? $this->height : $this->rpc->eth_blockNumber();
				$this->error = $this->rpc->error;
				$block = $this->rpc->eth_getBlockByNumber($this->height);
				$difficulty = objSafeVal($block, 'difficulty', 0);
				return $this->rpc->decode_hex($difficulty);
			case 'getmininginfo':
				$info = array();
				$this->height = $this->height ? $this->height : $this->rpc->eth_blockNumber();
				$info['blocks'] = $this->height;
				$block = $this->rpc->eth_getBlockByNumber($info['blocks']);
				$difficulty = objSafeVal($block, 'difficulty', 0);
				$info['difficulty'] = $this->rpc->decode_hex($difficulty);
				$info['generate'] = $this->rpc->eth_mining();
				$info['errors'] = '';
				$this->error = $this->rpc->error;
				return $info;
			case 'getblock':
				$hash = arraySafeVal($params,0);
				$block = $this->rpc->eth_getBlockByHash($hash);
				$this->error = $this->rpc->error;
				return $block;
			case 'getblockhash':
				$n = arraySafeVal($params,0);
				$block = $this->rpc->eth_getBlockByNumber($n);
				$this->error = $this->rpc->error;
				return $block->hash;
			case 'gettransaction':
			case 'getrawtransaction':
				$txid = arraySafeVal($params,0,'');
				$tx = $this->rpc->eth_getTransactionByHash($txid);
				$this->error = $this->rpc->error;
				return $tx;
			case 'getwork':
				return false; //$this->rpc->eth_getWork(); auto enable miner!
			// todo...
			case 'getpeerinfo':
				$peers = array();
				return $peers;
			case 'listtransactions':
				$txs = array();
				return $txs;
			case 'listsinceblock':
				$txs = array();
				return $txs;
			default:
				$res = $this->rpc->ether_request($method,$params);
				$this->error = $this->rpc->error;
				return $res;
			}
		}

		// XMR & BBR
		else if ($this->type == 'CryptoNote')
		{
			// convert some rpc methods used by yiimp (and handle wallet rpc, very limited)
			switch ($method) {
			case "getinfo":
				$res = $this->rpc->getinfo();
				$res["blocks"] = arraySafeVal($res,"height");
				$res["connections"] = arraySafeVal($res,"white_peerlist_size");
				$balances = $this->rpc_wallet->getbalance();
				$res["balance"] = arraySafeVal($balances,"unlocked_balance") / 1e12;
				$res["pending"] = arraySafeVal($balances,"balance",0) / 1e12 - $res["balance"];
				$ver = arraySafeVal($res,"mi");
				$res["version"] = (int) sprintf("%02d%02d%02d%02d", // BBR
					arraySafeVal($ver,"ver_major"), arraySafeVal($ver,"ver_minor"), arraySafeVal($ver,"ver_revision"), arraySafeVal($ver,"build_no"));
				$this->error = $this->rpc_wallet->error.$this->rpc->error;
				unset($res["mi"]);
				break;
			case "getmininginfo":
				$res = $this->rpc->getinfo();
				$res["networkhps"] = arraySafeVal($res,"current_network_hashrate_50");
				unset($res["current_network_hashrate_50"]);
				unset($res["current_network_hashrate_350"]);
				unset($res["mi"]);
				unset($res["grey_peerlist_size"]);
				unset($res["white_peerlist_size"]);
				unset($res["incoming_connections_count"]);
				unset($res["outgoing_connections_count"]);
				unset($res["max_net_seen_height"]);
				unset($res["synchronization_start_height"]);
				unset($res["transactions_cnt_per_day"]);
				unset($res["transactions_volume_per_day"]);
				unset($res["mi"]);
				$data = $this->rpc->getlastblockheader();
				$header = arraySafeVal($data,"block_header");
				$res["reward"] = (double) arraySafeVal($header,"reward") / 1e12;
				$this->error = $this->rpc->error;
				break;
			case "getnetworkinfo":
				$res = $this->rpc->getinfo();
				$res["connections"] = arraySafeVal($res,"white_peerlist_size");
				$res["networkhps"] = arraySafeVal($res,"current_network_hashrate_50");
				unset($res["current_network_hashrate_50"]);
				unset($res["current_network_hashrate_350"]);
				unset($res["mi"]);
				$this->error = $this->rpc->error;
				break;
			case 'getaccountaddress':
				$res = $this->rpc_wallet->getaddress();
				$res = objSafeVal($res, "address");
				$this->error = $this->rpc_wallet->error;
				break;
			case 'getblocktemplate':
				$gbt_params = array(
					"wallet_address" => $this->coin->master_wallet,
					"reserve_size"   => 8, // extra data
					"alias_details"  => null,
				);
				$res = $this->rpc->getblocktemplate($gbt_params);
				$data = $this->rpc->getlastblockheader();
				$header = arraySafeVal($data,"block_header");
				$res["coinbase"] = (double) arraySafeVal($header,"reward") / 1e4;
				$res["reward"] = (double) arraySafeVal($header,"reward") / 1e12;
				$this->error = $this->rpc->error;
				break;
			case "getbalance":
				$res = $this->rpc_wallet->getbalance();
				$this->error = $this->rpc_wallet->error;
				$res = arraySafeVal($res,"unlocked_balance") / 1e12;
				break;
			case "getbalances":
				$res = $this->rpc_wallet->getbalance();
				$this->error = $this->rpc_wallet->error;
				break;
			case 'listtransactions':
				$res = $this->rpc_wallet->get_bulk_payments();
				$this->error = $this->rpc_wallet->error;
				break;
			case 'getaddress':
				$res = $this->rpc_wallet->getaddress();
				$this->error = $this->rpc_wallet->error;
				break;
			case 'get_bulk_payments':
				$res = $this->rpc_wallet->get_bulk_payments();
				$this->error = $this->rpc_wallet->error;
				break;
			case 'get_payments':
				$named_params = array(
					"payment_id"=>arraySafeVal($params, 0)
				);
				$res = $this->rpc_wallet->get_payments($named_params);
				$this->error = $this->rpc_wallet->error;
				break;
			case 'get_transfers': // deprecated ?
				$res = $this->rpc_wallet->get_transfers();
				$this->error = $this->rpc_wallet->error;
				break;
			case 'incoming_transfers': // deprecated ?
				$res = $this->rpc_wallet->incoming_transfers();
				$this->error = $this->rpc_wallet->error;
				break;
			case 'sendtoaddress':
				// 3rd param is "payment id"
				$destination = array(
					"address"=>arraySafeVal($params, 0),
					"amount"=> (double) arraySafeVal($params, 1) * 1e12,
				);
				$named_params = array(
					"mixin"=>0,
					"destinations" => array((object)$destination),
					"payment_id" => arraySafeVal($params, 2),
				);
				$res = $this->rpc_wallet->transfer($named_params);
				$this->error = $this->rpc_wallet->error;
				break;
			case 'sendmany':
				$destinations = array();
				foreach ($params as $dest) {
					foreach ($dest as $addr => $amount) {
						$data = array("amount" => (double) $amount * 1e12, "address"=>$addr);
						$destinations[] = (object) $data;
					}
				}
				$named_params = array(
					"mixin"=>arraySafeVal($params, 0, 0),
					"destinations"=>$destinations,
				);
				$res = $this->rpc_wallet->transfer($named_params);
				$this->error = $this->rpc_wallet->error;
				break;
			case 'transfer':
			case 'transfer_original':
				$destination = array(
					"address"=> arraySafeVal($params, 1),
					"amount"=> (double) arraySafeVal($params, 2, 0) * 1e12,
					// also: "fee" "unlock_time"
				);
				$destinations = array();
				$destinations[] = (object)$destination;
				$named_params = array(
					"mixin" => arraySafeVal($params, 0, 0),
					"destinations" => $destinations,
					"payment_id" => arraySafeVal($params, 3),
				);
				$res = $this->rpc_wallet->transfer($named_params);
				$this->error = $this->rpc_wallet->error;
				break;
			case 'reset':
				$res = $this->rpc_wallet->reset();
				$this->error = $this->rpc_wallet->error;
				break;
			case 'store':
				$res = $this->rpc_wallet->store();
				$this->error = $this->rpc_wallet->error;
				break;
			default:
				// default to daemon
				$res = $this->rpc->__call($method,$params);
				$this->error = $this->rpc->error;
			}

			return $res;
		}

		// Bitcoin RPC
		$res = $this->rpc->__call($method,$params);
		$this->error = $this->rpc->error;
		return $res;
	}

	function __get($prop)
	{
		return $this->rpc->$prop;
	}

	function __set($prop, $value)
	{
		//debuglog("wallet set $prop ".json_encode($value));
		$this->rpc->$prop = $value;
	}

}
