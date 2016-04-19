<?php
// https://developer.github.com/webhooks/
// https://help.github.com/articles/about-webhooks/
// http://requestb.in/

// ruby -rsecurerandom -e 'puts SecureRandom.hex(20)'
define('HOOK_SECRET', '');
$notify_file = '/home/glen/www/spool/file-sync.json';

set_exception_handler(function($e) {
	header('HTTP/1.1 500 Internal Server Error');
	error_log(basename(__FILE__, '.php') . ': '. $e->getMessage());
	die("Error on line {$e->getLine()}: " . htmlspecialchars($e->getMessage()));
});

// https://developer.github.com/v3/activity/events/types/#pushevent
// X-Hub-Signature: sha1=ffd1a5f14b30eaaaca1e84499c372743302bc47d
function get_payload() {
	if (!isset($_SERVER['HTTP_X_GITHUB_EVENT'])) {
		throw new InvalidArgumentException('No event');
	}
	$eventType = $_SERVER['HTTP_X_GITHUB_EVENT'];
	if ($eventType != 'push') {
		throw new InvalidArgumentException("Invalid Event: $eventType");
	}

	if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
		throw new InvalidArgumentException("HTTP header 'X-Hub-Signature' is missing");
	}
	if (!extension_loaded('hash')) {
		throw new InvalidArgumentException("Missing 'hash' extension to check the secret code validity.");
	}

	# https://developer.github.com/v3/repos/hooks/#create-a-hook
	# The value of this header is computed as the HMAC hex digest of the body, using the secret as the key.
	list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2);
	if (!in_array($algo, hash_algos(), true)) {
		throw new Exception("Hash algorithm '$algo' is not supported.");
	}

	$rawPost = file_get_contents('php://input');
	if ($hash !== hash_hmac($algo, $rawPost, HOOK_SECRET)) {
		throw new InvalidArgumentException("Hook secret ($algo) does not match");
	}

	if (!isset($_POST['payload'])) {
		throw new InvalidArgumentException('No payload');
	}

	return json_decode($_POST['payload']);
}

$payload = get_payload();
