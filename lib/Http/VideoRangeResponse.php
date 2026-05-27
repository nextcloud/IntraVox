<?php
declare(strict_types=1);

namespace OCA\IntraVox\Http;

use OCP\AppFramework\Http\Response;

/**
 * Streams a bounded byte-range from a file handle to the client.
 *
 * NC's built-in StreamResponse passes the whole stream via fpassthru() and
 * has no concept of an upper bound — useless for HTTP 206 (Partial Content)
 * which requires exactly Content-Length bytes starting at a given offset.
 *
 * This subclass reads in 64KB chunks until $length bytes have been emitted,
 * then closes the handle. The seek to $start happens once, here, instead of
 * the caller having to know the response will consume the handle.
 */
class VideoRangeResponse extends Response {
	/** @var resource */
	private $handle;

	public function __construct(
		$handle,
		private int $start,
		private int $length,
		private string $mime,
	) {
		parent::__construct();
		$this->handle = $handle;
	}

	public function render(): string {
		// AppFramework expects render() to return a string. For very large
		// videos that's wasteful. We bypass by writing directly to the output
		// buffer and returning an empty string. The Dispatcher will then emit
		// the (zero-byte) "body" — but our Content-Length header was already
		// set by the caller, and our writes have already gone to the wire.
		if ($this->start > 0) {
			if (fseek($this->handle, $this->start) !== 0) {
				fclose($this->handle);
				return '';
			}
		}

		$remaining = $this->length;
		$chunkSize = 65536;
		while ($remaining > 0 && !feof($this->handle)) {
			$read = fread($this->handle, min($chunkSize, $remaining));
			if ($read === false || $read === '') {
				break;
			}
			echo $read;
			// Flush per chunk so the client can start playing while we serve.
			if (ob_get_level() > 0) {
				@ob_flush();
			}
			@flush();
			$remaining -= strlen($read);
		}
		fclose($this->handle);
		return '';
	}
}
